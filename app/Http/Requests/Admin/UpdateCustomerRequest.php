<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCustomerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) optional($this->user())->isAdmin();
    }

    protected function prepareForValidation(): void
    {
        $phone = preg_replace('/[\s().-]+/', '', trim((string) $this->input('phone')));
        if (str_starts_with($phone, '0') && strlen($phone) === 10) {
            $phone = '+84'.substr($phone, 1);
        }

        $this->merge([
            'name' => preg_replace('/\s+/u', ' ', trim((string) $this->input('name'))),
            'phone' => $phone === '' ? null : $phone,
            'admin_note' => trim((string) $this->input('admin_note')) ?: null,
        ]);
    }

    public function rules(): array
    {
        $customer = $this->route('customer');

        return [
            'name' => ['required', 'string', 'min:2', 'max:100', 'not_regex:/[<>]/'],
            'phone' => [
                'nullable',
                'string',
                'regex:/^\+84[0-9]{9}$/',
                Rule::unique('users', 'phone')->ignore(optional($customer)->id),
            ],
            'birthday' => ['nullable', 'date', 'before_or_equal:today'],
            'gender' => ['nullable', Rule::in(['male', 'female', 'other', 'unspecified'])],
            'admin_note' => ['nullable', 'string', 'max:2000', 'not_regex:/[<>]/'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Vui lòng nhập họ tên khách hàng.',
            'name.min' => 'Họ tên phải có ít nhất 2 ký tự.',
            'name.max' => 'Họ tên không được vượt quá 100 ký tự.',
            'name.not_regex' => 'Họ tên không được chứa mã HTML.',
            'phone.regex' => 'Số điện thoại phải theo định dạng Việt Nam 0xxxxxxxxx hoặc +84xxxxxxxxx.',
            'phone.unique' => 'Số điện thoại này đã thuộc về tài khoản khác.',
            'birthday.before_or_equal' => 'Ngày sinh không được ở tương lai.',
            'gender.in' => 'Giới tính không hợp lệ.',
            'admin_note.max' => 'Ghi chú nội bộ không được vượt quá 2.000 ký tự.',
            'admin_note.not_regex' => 'Ghi chú nội bộ không được chứa mã HTML.',
        ];
    }
}
