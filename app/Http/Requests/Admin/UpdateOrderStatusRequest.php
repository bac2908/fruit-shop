<?php

namespace App\Http\Requests\Admin;

use App\Models\Order;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateOrderStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) optional($this->user())->isAdmin();
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'admin_note' => $this->cleanText($this->input('admin_note')),
        ]);
    }

    public function rules(): array
    {
        return [
            'status' => ['required', Rule::in(array_keys(Order::statusLabels()))],
            'admin_note' => ['nullable', 'string', 'max:500', 'not_regex:/[<>]/'],
        ];
    }

    public function messages(): array
    {
        return [
            'status.required' => 'Vui lòng chọn trạng thái đơn hàng.',
            'status.in' => 'Trạng thái đơn hàng không hợp lệ.',
            'admin_note.max' => 'Ghi chú không được vượt quá 500 ký tự.',
            'admin_note.not_regex' => 'Ghi chú không được chứa ký tự HTML.',
        ];
    }

    private function cleanText($value): ?string
    {
        $value = preg_replace('/\s+/u', ' ', trim((string) $value));

        return $value === '' ? null : $value;
    }
}
