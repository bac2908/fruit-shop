<?php

namespace App\Http\Controllers;

use App\Models\ContactMessage;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ContactController extends Controller
{
    public function show()
    {
        $productName = trim((string) request('product', ''));
        $suggestedMessage = $productName !== ''
            ? 'Tôi muốn được tư vấn sản phẩm: ' . $productName
            : '';

        return view('pages.contact', [
            'suggestedMessage' => $suggestedMessage,
        ]);
    }

    public function store(Request $request)
    {
        $request->merge([
            'name' => $this->cleanTextInput($request->input('name')),
            'email' => Str::lower($this->cleanTextInput($request->input('email'))),
            'phone' => $this->normalizeVietnamPhone($request->input('phone')),
            'message' => $this->cleanMessage($request->input('message')),
        ]);

        $validated = $request->validate([
            'name' => ['required', 'string', 'min:2', 'max:120', 'not_regex:/[<>]/'],
            'email' => ['required', 'email', 'max:160'],
            'phone' => ['nullable', 'string', 'regex:/^\+84[0-9]{9}$/'],
            'message' => ['required', 'string', 'min:10', 'max:2000', 'not_regex:/[<>]/'],
        ], [
            'name.required' => 'Vui lòng nhập họ tên.',
            'name.min' => 'Họ tên cần có ít nhất 2 ký tự.',
            'name.max' => 'Họ tên không được vượt quá 120 ký tự.',
            'name.not_regex' => 'Họ tên không được chứa ký tự HTML.',
            'email.required' => 'Vui lòng nhập email.',
            'email.email' => 'Email không hợp lệ.',
            'email.max' => 'Email không được vượt quá 160 ký tự.',
            'phone.regex' => 'Số điện thoại cần theo định dạng 0xxxxxxxxx hoặc +84xxxxxxxxx.',
            'message.required' => 'Vui lòng nhập nội dung cần tư vấn.',
            'message.min' => 'Nội dung cần có ít nhất 10 ký tự.',
            'message.max' => 'Nội dung không được vượt quá 2000 ký tự.',
            'message.not_regex' => 'Nội dung không được chứa ký tự HTML.',
        ]);

        ContactMessage::query()->create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?? null,
            'message' => $validated['message'],
            'status' => ContactMessage::STATUS_NEW,
            'ip_address' => $request->ip(),
            'user_agent' => Str::limit((string) $request->userAgent(), 1000, ''),
        ]);

        return redirect()
            ->route('contact.page')
            ->with('success', 'Cảm ơn bạn đã liên hệ. Shop sẽ phản hồi trong thời gian sớm nhất.');
    }

    private function cleanTextInput($value): string
    {
        return preg_replace('/\s+/u', ' ', trim((string) $value));
    }

    private function cleanMessage($value): string
    {
        $message = trim((string) $value);
        $message = preg_replace("/[ \t]+/u", ' ', $message);
        $message = preg_replace("/(\r\n|\r|\n){3,}/u", "\n\n", $message);

        return $message;
    }

    private function normalizeVietnamPhone($value): ?string
    {
        $phone = preg_replace('/[\s.\-()]/', '', (string) $value);

        if ($phone === '') {
            return null;
        }

        if (str_starts_with($phone, '+84')) {
            return '+84' . ltrim(substr($phone, 3), '0');
        }

        if (str_starts_with($phone, '84')) {
            return '+84' . ltrim(substr($phone, 2), '0');
        }

        if (str_starts_with($phone, '0')) {
            return '+84' . substr($phone, 1);
        }

        return $phone;
    }
}
