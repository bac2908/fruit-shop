<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class ResolveOrderReturnRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) optional($this->user())->isAdmin();
    }

    protected function prepareForValidation(): void
    {
        $note = preg_replace('/\s+/u', ' ', trim((string) $this->input('admin_note')));
        $this->merge([
            'admin_note' => $note === '' ? null : $note,
            'refund_reference' => trim((string) $this->input('refund_reference')),
        ]);
    }

    public function rules(): array
    {
        $requiresNote = $this->routeIs('admin.orders.returns.reject', 'admin.orders.returns.complete');
        $requiresRefundReference = $this->routeIs('admin.orders.returns.refund');

        return [
            'admin_note' => [$requiresNote ? 'required' : 'nullable', 'string', 'min:5', 'max:500', 'not_regex:/[<>]/'],
            'refund_amount' => ['nullable', 'integer', 'min:1000', 'max:1000000000'],
            'refund_reference' => [
                $requiresRefundReference ? 'required' : 'nullable',
                'string',
                'min:4',
                'max:120',
                'regex:/^[A-Za-z0-9._\/-]+$/',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'admin_note.required' => 'Vui lòng nhập lý do từ chối để khách hàng hiểu.',
            'admin_note.min' => 'Phản hồi cần có ít nhất 5 ký tự.',
            'admin_note.max' => 'Phản hồi không được vượt quá 500 ký tự.',
            'refund_amount.min' => 'Số tiền hoàn phải từ 1.000đ.',
            'refund_reference.required' => 'Vui lòng nhập mã tham chiếu giao dịch hoàn tiền.',
            'refund_reference.regex' => 'Mã tham chiếu hoàn tiền không hợp lệ.',
        ];
    }
}
