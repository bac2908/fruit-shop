<?php

namespace App\Http\Requests\Admin;

use App\Models\Coupon;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpsertCouponRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) optional($this->user())->isAdmin();
    }

    protected function prepareForValidation(): void
    {
        foreach (['title', 'description'] as $field) {
            $value = preg_replace('/\s+/u', ' ', trim((string) $this->input($field)));
            $this->merge([$field => $value === '' ? null : $value]);
        }

        $this->merge([
            'code' => Str::upper(trim((string) $this->input('code'))),
            'is_active' => $this->boolean('is_active'),
            'is_public' => $this->boolean('is_public'),
            'gift_product_id' => $this->input('type') === Coupon::TYPE_GIFT
                ? $this->input('gift_product_id')
                : null,
            'gift_quantity' => $this->input('type') === Coupon::TYPE_GIFT
                ? $this->input('gift_quantity', 1)
                : null,
        ]);
    }

    public function rules(): array
    {
        $coupon = $this->route('coupon');
        $couponId = $coupon instanceof Coupon ? $coupon->getKey() : null;

        return [
            'title' => ['required', 'string', 'min:2', 'max:160', 'not_regex:/[<>]/'],
            'code' => [
                'required',
                'string',
                'max:80',
                'regex:/^[A-Z0-9_-]+$/',
                Rule::unique('coupons', 'code')->ignore($couponId),
            ],
            'type' => ['required', Rule::in([Coupon::TYPE_PERCENT, Coupon::TYPE_FIXED, Coupon::TYPE_GIFT])],
            'value' => ['nullable', 'integer', 'min:0', 'max:1000000000'],
            'gift_product_id' => [
                Rule::requiredIf($this->input('type') === Coupon::TYPE_GIFT),
                'nullable',
                'integer',
                Rule::exists('products', 'id')->whereNull('deleted_at'),
            ],
            'gift_quantity' => [
                Rule::requiredIf($this->input('type') === Coupon::TYPE_GIFT),
                'nullable',
                'integer',
                'min:1',
                'max:100',
            ],
            'min_order_total' => ['nullable', 'integer', 'min:0', 'max:1000000000'],
            'max_discount' => ['nullable', 'integer', 'min:0', 'max:1000000000'],
            'usage_limit' => ['nullable', 'integer', 'min:1', 'max:1000000'],
            'per_customer_limit' => ['nullable', 'integer', 'min:1', 'max:1000'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'is_active' => ['required', 'boolean'],
            'is_public' => ['required', 'boolean'],
            'description' => ['nullable', 'string', 'max:1000', 'not_regex:/[<>]/'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator) {
                $type = $this->input('type');
                $value = (int) $this->input('value', 0);

                if ($type !== Coupon::TYPE_GIFT && $value <= 0) {
                    $validator->errors()->add('value', 'Voucher giảm giá cần có giá trị lớn hơn 0.');
                }

                if ($type === Coupon::TYPE_PERCENT && $value > 100) {
                    $validator->errors()->add('value', 'Voucher phần trăm không được vượt quá 100%.');
                }

                if ($type === Coupon::TYPE_FIXED && (int) $this->input('max_discount', 0) > 0) {
                    $validator->errors()->add('max_discount', 'Voucher giảm tiền cố định không cần nhập mức giảm tối đa.');
                }
            },
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => 'Vui lòng nhập tiêu đề voucher.',
            'code.required' => 'Vui lòng nhập mã voucher.',
            'code.regex' => 'Mã chỉ gồm chữ in hoa, số, dấu gạch dưới hoặc gạch ngang.',
            'code.unique' => 'Mã voucher này đã tồn tại.',
            'gift_product_id.required' => 'Vui lòng chọn sản phẩm được tặng.',
            'gift_product_id.exists' => 'Sản phẩm quà tặng không tồn tại hoặc đã bị xóa.',
            'gift_quantity.required' => 'Vui lòng nhập số lượng quà tặng.',
            'ends_at.after_or_equal' => 'Thời gian kết thúc phải sau thời gian bắt đầu.',
        ];
    }
}
