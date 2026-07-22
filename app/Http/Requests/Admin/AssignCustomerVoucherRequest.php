<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AssignCustomerVoucherRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) optional($this->user())->isAdmin();
    }

    public function rules(): array
    {
        return [
            'coupon_id' => ['required', 'integer', Rule::exists('coupons', 'id')->whereNull('deleted_at')],
            'expires_at' => ['nullable', 'date', 'after:now'],
        ];
    }

    public function messages(): array
    {
        return [
            'coupon_id.required' => 'Vui lòng chọn voucher cần phát.',
            'coupon_id.exists' => 'Voucher đã chọn không còn tồn tại.',
            'expires_at.after' => 'Hạn sử dụng riêng phải ở tương lai.',
        ];
    }
}
