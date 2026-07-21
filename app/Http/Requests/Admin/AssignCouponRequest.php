<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class AssignCouponRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) optional($this->user())->isAdmin();
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'email' => Str::lower(trim((string) $this->input('email'))),
        ]);
    }

    public function rules(): array
    {
        return [
            'coupon_id' => ['required', 'integer', Rule::exists('coupons', 'id')->whereNull('deleted_at')],
            'target' => ['required', Rule::in(['single', 'all_customers'])],
            'email' => [
                Rule::requiredIf($this->input('target') === 'single'),
                'nullable',
                'email',
                'max:120',
                Rule::exists('users', 'email')->where('role', 'customer'),
            ],
            'expires_at' => ['nullable', 'date', 'after:now'],
        ];
    }

    public function messages(): array
    {
        return [
            'coupon_id.required' => 'Vui lòng chọn voucher.',
            'target.required' => 'Vui lòng chọn nhóm khách hàng nhận voucher.',
            'email.required' => 'Vui lòng nhập email khách hàng.',
            'email.exists' => 'Không tìm thấy tài khoản khách hàng với email này.',
            'expires_at.after' => 'Hạn sử dụng riêng phải ở tương lai.',
        ];
    }
}
