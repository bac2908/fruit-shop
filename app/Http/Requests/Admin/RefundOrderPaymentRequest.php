<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class RefundOrderPaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) optional($this->user())->isAdmin();
    }

    protected function prepareForValidation(): void
    {
        $note = preg_replace('/\s+/u', ' ', trim((string) $this->input('admin_note')));
        $this->merge([
            'refund_reference' => trim((string) $this->input('refund_reference')),
            'admin_note' => $note === '' ? null : $note,
        ]);
    }

    public function rules(): array
    {
        return [
            'refund_reference' => ['required', 'string', 'min:4', 'max:120', 'regex:/^[A-Za-z0-9._\/-]+$/'],
            'admin_note' => ['nullable', 'string', 'max:500', 'not_regex:/[<>]/'],
        ];
    }

    public function messages(): array
    {
        return [
            'refund_reference.required' => 'Vui lòng nhập mã tham chiếu hoàn tiền.',
            'refund_reference.regex' => 'Mã tham chiếu hoàn tiền không hợp lệ.',
        ];
    }
}
