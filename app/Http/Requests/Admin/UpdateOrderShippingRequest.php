<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateOrderShippingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) optional($this->user())->isAdmin();
    }

    protected function prepareForValidation(): void
    {
        foreach (['shipping_provider', 'tracking_code', 'shipping_delivery_eta', 'shipping_delivery_note'] as $field) {
            $value = preg_replace('/\s+/u', ' ', trim((string) $this->input($field)));
            $this->merge([$field => $value === '' ? null : $value]);
        }
    }

    public function rules(): array
    {
        return [
            'shipping_fee' => ['required', 'integer', 'min:0', 'max:2000000'],
            'shipping_provider' => ['nullable', 'string', 'max:100', 'not_regex:/[<>]/'],
            'tracking_code' => ['nullable', 'string', 'max:120', 'regex:/^[A-Za-z0-9._\/-]+$/'],
            'shipping_delivery_eta' => ['nullable', 'string', 'max:120', 'not_regex:/[<>]/'],
            'shipping_delivery_note' => ['nullable', 'string', 'max:500', 'not_regex:/[<>]/'],
        ];
    }

    public function messages(): array
    {
        return [
            'shipping_fee.required' => 'Vui lòng nhập phí giao hàng.',
            'shipping_fee.integer' => 'Phí giao hàng phải là số nguyên VND.',
            'shipping_fee.min' => 'Phí giao hàng không được âm.',
            'shipping_fee.max' => 'Phí giao hàng quá lớn, vui lòng kiểm tra lại.',
            'tracking_code.regex' => 'Mã vận đơn chỉ được chứa chữ, số, dấu chấm, gạch ngang hoặc gạch chéo.',
            '*.not_regex' => 'Thông tin vận chuyển không được chứa ký tự HTML.',
        ];
    }
}
