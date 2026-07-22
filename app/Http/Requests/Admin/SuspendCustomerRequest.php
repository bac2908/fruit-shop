<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class SuspendCustomerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) optional($this->user())->isAdmin();
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'reason' => preg_replace('/\s+/u', ' ', trim((string) $this->input('reason'))),
        ]);
    }

    public function rules(): array
    {
        return [
            'reason' => ['required', 'string', 'min:10', 'max:1000', 'not_regex:/[<>]/'],
        ];
    }

    public function messages(): array
    {
        return [
            'reason.required' => 'Vui lòng ghi rõ lý do tạm ngưng tài khoản.',
            'reason.min' => 'Lý do tạm ngưng cần có ít nhất 10 ký tự.',
            'reason.max' => 'Lý do tạm ngưng không được vượt quá 1.000 ký tự.',
            'reason.not_regex' => 'Lý do tạm ngưng không được chứa mã HTML.',
        ];
    }
}
