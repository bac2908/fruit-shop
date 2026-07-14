<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

class EmailVerificationController extends Controller
{
    public function notice(Request $request)
    {
        if ($request->user()->hasVerifiedEmail()) {
            return redirect()->route('home');
        }

        return view('auth.verify-email', [
            'email' => $request->user()->getEmailForVerification(),
        ]);
    }

    public function verify(EmailVerificationRequest $request): RedirectResponse
    {
        if (!$request->user()->hasVerifiedEmail()) {
            $request->fulfill();
        }

        return redirect()
            ->route('home')
            ->with('success', 'Email đã được xác minh. Tài khoản của bạn đã sẵn sàng mua hàng.');
    }

    public function resend(Request $request): RedirectResponse
    {
        if ($request->user()->hasVerifiedEmail()) {
            return redirect()->route('home');
        }

        try {
            $request->user()->sendEmailVerificationNotification();
        } catch (Throwable $exception) {
            Log::error('Unable to resend verification email.', [
                'user_id' => $request->user()->id,
                'exception' => get_class($exception),
                'message' => $exception->getMessage(),
            ]);

            return back()->withErrors([
                'email' => 'Chưa thể gửi email xác minh lúc này. Vui lòng kiểm tra cấu hình mail hoặc thử lại sau.',
            ]);
        }

        return back()->with('status', 'Link xác minh mới đã được gửi. Vui lòng kiểm tra cả hộp thư rác.');
    }
}
