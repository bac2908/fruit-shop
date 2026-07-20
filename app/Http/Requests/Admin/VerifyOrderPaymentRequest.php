<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class VerifyOrderPaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) optional($this->user())->isAdmin();
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'payment_reference' => trim((string) $this->input('payment_reference')),
            'admin_note' => $this->cleanText($this->input('admin_note')),
        ]);
    }

    public function rules(): array
    {
        return [
            'payment_reference' => ['required', 'string', 'min:4', 'max:120', 'regex:/^[A-Za-z0-9._\/-]+$/'],
            'admin_note' => ['nullable', 'string', 'max:500', 'not_regex:/[<>]/'],
        ];
    }

    public function messages(): array
    {
        return [
            'payment_reference.required' => 'Vui lòng nhập mã tham chiếu giao dịch ngân hàng.',
            'payment_reference.min' => 'Mã tham chiếu cần có ít nhất 4 ký tự.',
            'payment_reference.regex' => 'Mã tham chiếu chỉ được chứa chữ, số, dấu chấm, gạch ngang hoặc gạch chéo.',
        ];
    }

    private function cleanText($value): ?string
    {
        $value = preg_replace('/\s+/u', ' ', trim((string) $value));

        return $value === '' ? null : $value;
    }
}
