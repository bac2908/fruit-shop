<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\SecurityAuditService;
use App\Services\SettingService;
use App\Services\TotpService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class TwoFactorController extends Controller
{
    private const PENDING_SECRET_KEY = 'admin_2fa_pending_secret';

    public function setup(Request $request, TotpService $totp, SettingService $settings): View|RedirectResponse
    {
        $user = $this->admin($request);
        if ($user->hasTwoFactorAuthentication()
            && (int) $request->session()->get('admin_2fa_user_id') !== (int) $user->id) {
            return redirect()->route('admin.2fa.challenge');
        }

        $secret = $user->hasTwoFactorAuthentication()
            ? null
            : $request->session()->get(self::PENDING_SECRET_KEY);

        if (! $user->hasTwoFactorAuthentication() && ! $secret) {
            $secret = $totp->generateSecret();
            $request->session()->put(self::PENDING_SECRET_KEY, $secret);
        }

        return view('admin.security.two-factor-setup', [
            'secret' => $secret,
            'provisioningUri' => $secret
                ? $totp->provisioningUri($secret, $user->email, (string) $settings->get('store_name', 'FruitShop'))
                : null,
        ]);
    }

    public function confirm(
        Request $request,
        TotpService $totp,
        SecurityAuditService $audit
    ): RedirectResponse {
        $user = $this->admin($request);
        abort_if($user->hasTwoFactorAuthentication(), 409, '2FA đã được kích hoạt.');

        $validated = $request->validate(['code' => ['required', 'digits:6']]);
        $secret = (string) $request->session()->get(self::PENDING_SECRET_KEY);
        $step = $secret !== '' ? $totp->verify($secret, $validated['code']) : null;

        if ($step === null) {
            throw ValidationException::withMessages(['code' => 'Mã xác thực không đúng hoặc đã hết hạn.']);
        }

        $recoveryCodes = $totp->recoveryCodes();
        $user->forceFill([
            'two_factor_secret' => $secret,
            'two_factor_recovery_codes' => collect($recoveryCodes)
                ->map(fn (string $code) => Hash::make($totp->normalizeRecoveryCode($code)))
                ->all(),
            'two_factor_confirmed_at' => now(),
            'two_factor_last_used_step' => $step,
        ])->save();

        $request->session()->forget(self::PENDING_SECRET_KEY);
        $request->session()->put('admin_2fa_user_id', $user->id);
        $this->audit($audit, $request, 'admin_two_factor_enabled');

        return redirect()->route('admin.2fa.setup')
            ->with('success', 'Đã bật xác thực hai lớp.')
            ->with('two_factor_recovery_codes', $recoveryCodes);
    }

    public function challenge(Request $request): View|RedirectResponse
    {
        $user = $this->admin($request);
        if (! $user->hasTwoFactorAuthentication()) {
            return redirect()->route('admin.dashboard');
        }
        if ((int) $request->session()->get('admin_2fa_user_id') === (int) $user->id) {
            return redirect()->intended(route('admin.dashboard'));
        }

        return view('admin.security.two-factor-challenge');
    }

    public function verifyChallenge(
        Request $request,
        TotpService $totp,
        SecurityAuditService $audit
    ): RedirectResponse {
        $user = $this->admin($request);
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:30'],
        ]);

        $value = trim($validated['code']);
        $accepted = false;
        $step = null;

        if (preg_match('/^\d{6}$/', $value)) {
            $step = $totp->verify((string) $user->two_factor_secret, $value);
            $accepted = $step !== null && ($user->two_factor_last_used_step === null || $step > $user->two_factor_last_used_step);
        } else {
            $accepted = $this->consumeRecoveryCode($user, $totp, $value);
        }

        if (! $accepted) {
            $this->audit($audit, $request, 'admin_two_factor_failed');
            throw ValidationException::withMessages([
                'code' => 'Mã xác thực không đúng, đã dùng hoặc đã hết hạn.',
            ]);
        }

        if ($step !== null) {
            $user->forceFill(['two_factor_last_used_step' => $step])->save();
        }

        $request->session()->regenerate();
        $request->session()->put('admin_2fa_user_id', $user->id);
        $this->audit($audit, $request, 'admin_two_factor_passed');

        return redirect()->intended(route('admin.dashboard'));
    }

    public function disable(
        Request $request,
        TotpService $totp,
        SecurityAuditService $audit
    ): RedirectResponse {
        $user = $this->admin($request);
        $validated = $request->validate([
            'current_password' => ['required', 'current_password'],
            'code' => ['required', 'digits:6'],
        ]);

        if ($totp->verify((string) $user->two_factor_secret, $validated['code']) === null) {
            throw ValidationException::withMessages(['code' => 'Mã xác thực không đúng hoặc đã hết hạn.']);
        }

        $user->forceFill([
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
            'two_factor_confirmed_at' => null,
            'two_factor_last_used_step' => null,
        ])->save();

        $request->session()->forget('admin_2fa_user_id');
        $this->audit($audit, $request, 'admin_two_factor_disabled');

        return redirect()->route('admin.settings')->with('success', 'Đã tắt xác thực hai lớp.');
    }

    private function consumeRecoveryCode(User $user, TotpService $totp, string $value): bool
    {
        $normalized = $totp->normalizeRecoveryCode($value);
        if (strlen($normalized) !== 10) {
            return false;
        }

        $codes = collect($user->two_factor_recovery_codes ?? []);
        $matchedKey = $codes->search(fn (string $hash) => Hash::check($normalized, $hash));
        if ($matchedKey === false) {
            return false;
        }

        $user->forceFill([
            'two_factor_recovery_codes' => $codes->except($matchedKey)->values()->all(),
        ])->save();

        return true;
    }

    private function admin(Request $request): User
    {
        $user = $request->user();
        abort_unless($user && $user->isAdmin() && ! $user->isSuspended(), 403);

        return $user;
    }

    private function audit(SecurityAuditService $audit, Request $request, string $action): void
    {
        $audit->record($action, [
            'user_id' => $request->user()->id,
            'ip_address' => $request->ip(),
            'user_agent' => mb_substr((string) $request->userAgent(), 0, 1000),
        ]);
    }
}
