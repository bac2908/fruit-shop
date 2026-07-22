<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\WelcomeVoucherService;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Laravel\Socialite\Facades\Socialite;
use Throwable;

class AuthController extends Controller
{
    public function showLoginForm()
    {
        if (Auth::check() && Auth::user()->isAdmin()) {
            return redirect()->route('admin.dashboard');
        }

        return view('auth.login');
    }

    public function showRegisterForm()
    {
        if (Auth::check()) {
            return redirect()->route('home');
        }

        return view('auth.register');
    }

    public function showForgotPasswordForm()
    {
        if (Auth::check()) {
            return redirect()->route('home');
        }

        return view('auth.forgot-password');
    }

    public function login(Request $request, WelcomeVoucherService $welcomeVouchers)
    {
        $this->normalizeLoginInput($request);

        $credentials = $request->validate([
            'email' => ['required', 'string', 'email', 'max:255'],
            'password' => ['required', 'string'],
            'remember' => ['nullable', 'boolean'],
        ], [
            'email.required' => 'Vui lòng nhập email.',
            'email.email' => 'Email không hợp lệ.',
            'password.required' => 'Vui lòng nhập mật khẩu.',
        ]);

        $email = Str::lower(trim($credentials['email']));
        $user = User::query()->whereRaw('LOWER(email) = ?', [$email])->first();

        if ($this->isLocked($user)) {
            throw ValidationException::withMessages([
                'email' => 'Tài khoản đang bị khóa tạm thời. Vui lòng thử lại sau.',
            ]);
        }

        if (! Auth::attempt(['email' => $email, 'password' => $credentials['password']], $request->boolean('remember'))) {
            $this->recordFailedLogin($request, $email, $user);
            $this->writeSecurityAuditLog($request, 'login_failed', $user, ['email' => $email]);

            throw ValidationException::withMessages([
                'email' => 'Email hoặc mật khẩu không đúng.',
            ]);
        }

        $user = $request->user();

        if ($user->isSuspended()) {
            $this->writeSecurityAuditLog($request, 'login_blocked_suspended', $user);
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            throw ValidationException::withMessages([
                'email' => 'Tài khoản đang tạm ngưng. Vui lòng liên hệ cửa hàng để được hỗ trợ.',
            ]);
        }

        $request->session()->regenerate();
        $welcomeVouchers->assignTo($user);
        $this->resetFailedLoginState($user);
        $this->markSuccessfulLogin($request, $user);
        $this->writeSecurityAuditLog($request, 'login_success', $user);

        return $this->redirectAfterLogin($request, $user, 'Đăng nhập thành công.');
    }

    public function register(Request $request, WelcomeVoucherService $welcomeVouchers)
    {
        $this->normalizeRegisterInput($request);

        $validated = $request->validate([
            'name' => ['required', 'string', 'min:2', 'max:100', 'not_regex:/[<>]/'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'phone' => ['nullable', 'string', 'max:13', 'regex:/^(0[0-9]{9}|\+84[0-9]{9})$/'],
            'address' => ['nullable', 'string', 'max:255', 'not_regex:/[<>]/'],
            'password' => [
                'required',
                'string',
                'min:8',
                'max:72',
                'confirmed',
                'regex:/[a-z]/',
                'regex:/[A-Z]/',
                'regex:/[0-9]/',
            ],
            'terms' => ['accepted'],
        ], [
            'name.required' => 'Vui lòng nhập họ và tên.',
            'name.min' => 'Họ tên phải có ít nhất 2 ký tự.',
            'name.max' => 'Họ tên không được vượt quá 100 ký tự.',
            'name.not_regex' => 'Họ tên không được chứa ký tự HTML.',
            'email.required' => 'Vui lòng nhập email.',
            'email.email' => 'Email không hợp lệ.',
            'email.unique' => 'Email này đã được sử dụng.',
            'phone.regex' => 'Số điện thoại cần theo định dạng 0xxxxxxxxx hoặc +84xxxxxxxxx.',
            'address.max' => 'Địa chỉ giao hàng không được vượt quá 255 ký tự.',
            'address.not_regex' => 'Địa chỉ không được chứa ký tự HTML.',
            'password.required' => 'Vui lòng nhập mật khẩu.',
            'password.min' => 'Mật khẩu phải có ít nhất 8 ký tự.',
            'password.max' => 'Mật khẩu không được vượt quá 72 ký tự.',
            'password.confirmed' => 'Mật khẩu xác nhận không khớp.',
            'password.regex' => 'Mật khẩu phải có chữ hoa, chữ thường và số.',
            'terms.accepted' => 'Bạn cần đồng ý với Điều khoản sử dụng và Chính sách bảo mật để tiếp tục.',
        ]);

        $user = DB::transaction(function () use ($request, $validated, $welcomeVouchers) {
            $phone = $validated['phone'] ?? null;
            $address = $validated['address'] ?? null;
            $payload = [
                'name' => trim((string) $validated['name']),
                'email' => Str::lower(trim((string) $validated['email'])),
                'phone' => $phone ? trim((string) $phone) : null,
                'address' => $address ? trim((string) $address) : null,
                'password' => Hash::make($validated['password']),
                'role' => 'customer',
            ];

            if (Schema::hasColumn('users', 'password_changed_at')) {
                $payload['password_changed_at'] = now();
            }

            $user = User::query()->create($payload);
            $welcomeVouchers->assignTo($user);

            if (Schema::hasTable('password_history')) {
                DB::table('password_history')->insert([
                    'user_id' => $user->id,
                    'password_hash' => $payload['password'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            $this->writeSecurityAuditLog($request, 'customer_registered', $user);

            return $user;
        });

        Auth::login($user);
        $request->session()->regenerate();
        $this->markSuccessfulLogin($request, $user);

        try {
            event(new Registered($user));
        } catch (Throwable $exception) {
            Log::error('Unable to send registration verification email.', [
                'user_id' => $user->id,
                'exception' => get_class($exception),
                'message' => $exception->getMessage(),
            ]);
        }

        return $this->redirectAfterLogin(
            $request,
            $user,
            'Tài khoản đã được tạo. Vui lòng xác minh email để tiếp tục mua hàng.'
        );
    }

    public function sendPasswordResetLink(Request $request)
    {
        $this->normalizeResetEmailInput($request);

        $validated = $request->validate([
            'email' => ['required', 'string', 'email', 'max:255'],
        ], [
            'email.required' => 'Vui lòng nhập email.',
            'email.email' => 'Email không hợp lệ.',
        ]);

        $email = Str::lower(trim((string) $validated['email']));
        $user = User::query()->whereRaw('LOWER(email) = ?', [$email])->first();
        $this->writeSecurityAuditLog($request, 'password_reset_requested', $user, ['email' => $email]);

        try {
            $status = Password::sendResetLink(['email' => $email]);
        } catch (Throwable $exception) {
            return back()
                ->withInput($request->only('email'))
                ->withErrors([
                    'email' => 'Chưa gửi được email đặt lại mật khẩu. Vui lòng kiểm tra cấu hình mail hoặc thử lại sau.',
                ]);
        }

        if ($status === Password::RESET_THROTTLED) {
            return back()
                ->withInput($request->only('email'))
                ->withErrors([
                    'email' => 'Bạn vừa yêu cầu đặt lại mật khẩu. Vui lòng chờ một lúc rồi thử lại.',
                ]);
        }

        return back()->with(
            'status',
            'Nếu email tồn tại trong hệ thống, chúng tôi đã gửi liên kết đặt lại mật khẩu. Vui lòng kiểm tra hộp thư của bạn.'
        );
    }

    public function showResetPasswordForm(Request $request, string $token)
    {
        if (Auth::check()) {
            return redirect()->route('home');
        }

        return view('auth.reset-password', [
            'token' => $token,
            'email' => Str::lower(trim((string) $request->query('email'))),
        ]);
    }

    public function resetPassword(Request $request)
    {
        $this->normalizeResetEmailInput($request);

        $validated = $request->validate([
            'token' => ['required', 'string'],
            'email' => ['required', 'string', 'email', 'max:255'],
            'password' => [
                'required',
                'string',
                'min:8',
                'max:72',
                'confirmed',
                'regex:/[a-z]/',
                'regex:/[A-Z]/',
                'regex:/[0-9]/',
            ],
        ], [
            'email.required' => 'Vui lòng nhập email.',
            'email.email' => 'Email không hợp lệ.',
            'password.required' => 'Vui lòng nhập mật khẩu mới.',
            'password.min' => 'Mật khẩu phải có ít nhất 8 ký tự.',
            'password.max' => 'Mật khẩu không được vượt quá 72 ký tự.',
            'password.confirmed' => 'Mật khẩu xác nhận không khớp.',
            'password.regex' => 'Mật khẩu phải có chữ hoa, chữ thường và số.',
        ]);

        $status = Password::reset(
            [
                'email' => $validated['email'],
                'password' => $validated['password'],
                'password_confirmation' => $request->input('password_confirmation'),
                'token' => $validated['token'],
            ],
            function (User $user, string $password) use ($request) {
                $passwordHash = Hash::make($password);
                $payload = [
                    'password' => $passwordHash,
                    'remember_token' => Str::random(60),
                ];

                if (Schema::hasColumn('users', 'password_changed_at')) {
                    $payload['password_changed_at'] = now();
                }

                if (Schema::hasColumn('users', 'force_password_change')) {
                    $payload['force_password_change'] = false;
                }

                if (Schema::hasColumn('users', 'failed_login_attempts')) {
                    $payload['failed_login_attempts'] = 0;
                }

                if (Schema::hasColumn('users', 'locked_until')) {
                    $payload['locked_until'] = null;
                }

                $user->forceFill($payload)->save();

                if (Schema::hasTable('password_history')) {
                    DB::table('password_history')->insert([
                        'user_id' => $user->id,
                        'password_hash' => $passwordHash,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }

                event(new PasswordReset($user));
                $this->writeSecurityAuditLog($request, 'password_reset_completed', $user);
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            return redirect()
                ->route('login')
                ->with('success', 'Mật khẩu đã được đặt lại. Bạn có thể đăng nhập bằng mật khẩu mới.');
        }

        return back()
            ->withInput($request->only('email'))
            ->withErrors([
                'email' => 'Liên kết đặt lại mật khẩu không hợp lệ hoặc đã hết hạn.',
            ]);
    }

    public function redirectToGoogle()
    {
        if (! $this->googleOAuthConfigured()) {
            return redirect()
                ->route('login')
                ->with('error', 'Chưa cấu hình Google OAuth. Vui lòng thêm GOOGLE_CLIENT_ID và GOOGLE_CLIENT_SECRET trong file .env.');
        }

        return Socialite::driver('google')->redirect();
    }

    public function handleGoogleCallback(Request $request, WelcomeVoucherService $welcomeVouchers)
    {
        if ($request->filled('error')) {
            return redirect()
                ->route('login')
                ->with('error', 'Bạn đã hủy đăng nhập Google hoặc Google không cấp quyền cho website.');
        }

        if (! $this->googleOAuthConfigured()) {
            return redirect()
                ->route('login')
                ->with('error', 'Chưa cấu hình Google OAuth. Vui lòng thêm GOOGLE_CLIENT_ID và GOOGLE_CLIENT_SECRET trong file .env.');
        }

        try {
            $googleUser = Socialite::driver('google')->user();
        } catch (Throwable $exception) {
            Log::warning('Google login callback failed.', [
                'message' => $exception->getMessage(),
                'exception' => get_class($exception),
            ]);

            return redirect()
                ->route('login')
                ->with('error', 'Không thể đăng nhập bằng Google lúc này. Vui lòng thử lại hoặc dùng email và mật khẩu.');
        }

        $googleId = (string) $googleUser->getId();
        $email = Str::lower(trim((string) $googleUser->getEmail()));

        if ($googleId === '' || $email === '') {
            return redirect()
                ->route('login')
                ->with('error', 'Tài khoản Google chưa cung cấp đủ email để đăng nhập.');
        }

        $existingUser = User::query()
            ->where('google_id', $googleId)
            ->orWhereRaw('LOWER(email) = ?', [$email])
            ->first();

        if ($this->isLocked($existingUser)) {
            return redirect()
                ->route('login')
                ->with('error', 'Tài khoản đang bị khóa tạm thời. Vui lòng thử lại sau.');
        }

        if ($existingUser && $existingUser->isSuspended()) {
            $this->writeSecurityAuditLog($request, 'google_login_blocked_suspended', $existingUser, ['email' => $email]);

            return redirect()
                ->route('login')
                ->with('error', 'Tài khoản đang tạm ngưng. Vui lòng liên hệ cửa hàng để được hỗ trợ.');
        }

        $user = DB::transaction(function () use ($request, $existingUser, $googleUser, $googleId, $email, $welcomeVouchers) {
            $user = $existingUser;
            $isNewUser = ! $user;
            $displayName = trim((string) ($googleUser->getName() ?: Str::before($email, '@')));

            $payload = [
                'name' => $user && $user->name ? $user->name : $displayName,
                'email' => $email,
                'google_id' => $googleId,
                'auth_provider' => 'google',
                'email_verified_at' => optional($user)->email_verified_at ?: now(),
                'role' => optional($user)->role ?: 'customer',
            ];

            if (Schema::hasColumn('users', 'avatar_url') && $googleUser->getAvatar() && (! $user || ! $user->avatar_url)) {
                $payload['avatar_url'] = $googleUser->getAvatar();
            }

            if ($isNewUser) {
                $payload['password'] = Hash::make(Str::random(48));

                if (Schema::hasColumn('users', 'password_changed_at')) {
                    $payload['password_changed_at'] = now();
                }

                $user = User::query()->create($payload);
                $welcomeVouchers->assignTo($user);
                $this->writeSecurityAuditLog($request, 'google_customer_registered', $user, ['email' => $email]);

                return $user;
            }

            $user->forceFill($payload)->save();
            $welcomeVouchers->assignTo($user);
            $this->writeSecurityAuditLog($request, 'google_account_linked', $user, ['email' => $email]);

            return $user;
        });

        Auth::login($user, true);
        $request->session()->regenerate();
        $this->resetFailedLoginState($user);
        $this->markSuccessfulLogin($request, $user);
        $this->writeSecurityAuditLog($request, 'google_login_success', $user, ['email' => $email]);

        return $this->redirectAfterLogin($request, $user, 'Đăng nhập Google thành công.');
    }

    public function logout(Request $request)
    {
        $this->writeSecurityAuditLog($request, 'logout', $request->user());

        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home');
    }

    private function isLocked(?User $user): bool
    {
        if (! $user || ! Schema::hasColumn('users', 'locked_until')) {
            return false;
        }

        return $user->locked_until && now()->lessThan($user->locked_until);
    }

    private function recordFailedLogin(Request $request, string $email, ?User $user): void
    {
        if (Schema::hasTable('failed_login_attempts')) {
            DB::table('failed_login_attempts')->insert([
                'email' => $email,
                'ip_address' => $request->ip(),
                'user_agent' => substr((string) $request->userAgent(), 0, 1000),
                'attempt_time' => now(),
            ]);
        }

        if (! $user || ! Schema::hasColumn('users', 'failed_login_attempts')) {
            return;
        }

        $attempts = (int) $user->failed_login_attempts + 1;
        $payload = ['failed_login_attempts' => $attempts];

        if ($attempts >= 5 && Schema::hasColumn('users', 'locked_until')) {
            $payload['locked_until'] = now()->addMinutes(15);
        }

        $user->forceFill($payload)->save();
    }

    private function resetFailedLoginState(User $user): void
    {
        $payload = [];

        if (Schema::hasColumn('users', 'failed_login_attempts')) {
            $payload['failed_login_attempts'] = 0;
        }

        if (Schema::hasColumn('users', 'locked_until')) {
            $payload['locked_until'] = null;
        }

        if (! empty($payload)) {
            $user->forceFill($payload)->save();
        }
    }

    private function redirectAfterLogin(Request $request, User $user, ?string $successMessage = null)
    {
        $intendedUrl = session()->pull('url.intended');

        if ($user->isAdmin()) {
            $response = $intendedUrl
                ? redirect()->to($intendedUrl)
                : redirect()->route('admin.dashboard');

            return $successMessage ? $response->with('success', $successMessage) : $response;
        }

        if (! $user->hasVerifiedEmail()) {
            return redirect()
                ->route('verification.notice')
                ->with('success', $successMessage ?: 'Vui lòng xác minh email để tiếp tục.');
        }

        if ($intendedUrl) {
            $path = parse_url($intendedUrl, PHP_URL_PATH) ?: '';

            if (! Str::startsWith($path, '/admin')) {
                $response = redirect()->to($intendedUrl);

                return $successMessage ? $response->with('success', $successMessage) : $response;
            }
        }

        $response = redirect()->route('home');

        return $successMessage ? $response->with('success', $successMessage) : $response;
    }

    private function markSuccessfulLogin(Request $request, User $user): void
    {
        $payload = [];

        if (Schema::hasColumn('users', 'last_login_at')) {
            $payload['last_login_at'] = now();
        }

        if (Schema::hasColumn('users', 'last_login_ip')) {
            $payload['last_login_ip'] = $request->ip();
        }

        if ($payload !== []) {
            $user->forceFill($payload)->save();
        }

        $request->session()->put(
            'auth_session_version',
            Schema::hasColumn('users', 'session_version') ? max(1, (int) $user->session_version) : 1
        );
    }

    private function normalizeRegisterInput(Request $request): void
    {
        $phone = $request->input('phone');

        if (is_string($phone)) {
            $phone = preg_replace('/[\s().-]+/', '', trim($phone));
            $phone = $phone === '' ? null : $phone;
        }

        $request->merge([
            'name' => $this->cleanTextInput($request->input('name')),
            'email' => Str::lower(trim((string) $request->input('email'))),
            'phone' => $phone,
            'address' => $this->cleanTextInput($request->input('address'), true),
        ]);
    }

    private function cleanTextInput($value, bool $nullable = false): ?string
    {
        $cleaned = preg_replace('/\s+/u', ' ', trim((string) $value));

        if ($nullable && $cleaned === '') {
            return null;
        }

        return $cleaned;
    }

    private function normalizeLoginInput(Request $request): void
    {
        $request->merge([
            'email' => Str::lower(trim((string) $request->input('email'))),
        ]);
    }

    private function normalizeResetEmailInput(Request $request): void
    {
        $request->merge([
            'email' => Str::lower(trim((string) $request->input('email'))),
        ]);
    }

    private function googleOAuthConfigured(): bool
    {
        return filled(config('services.google.client_id'))
            && filled(config('services.google.client_secret'))
            && filled(config('services.google.redirect'));
    }

    private function writeSecurityAuditLog(Request $request, string $action, ?User $user = null, array $metadata = []): void
    {
        if (! Schema::hasTable('security_audit_log')) {
            return;
        }

        DB::table('security_audit_log')->insert([
            'user_id' => optional($user)->id,
            'action' => $action,
            'ip_address' => $request->ip(),
            'user_agent' => substr((string) $request->userAgent(), 0, 1000),
            'metadata' => json_encode($metadata),
            'created_at' => now(),
        ]);
    }
}
