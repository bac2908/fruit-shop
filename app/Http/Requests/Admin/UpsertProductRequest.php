<?php

namespace App\Http\Requests\Admin;

use App\Models\Product;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class UpsertProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) optional($this->user())->isAdmin();
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'name' => preg_replace('/\s+/u', ' ', trim((string) $this->input('name'))),
            'slug' => Str::slug((string) $this->input('slug')),
            'sku' => Str::upper(trim((string) $this->input('sku'))),
            'unit' => preg_replace('/\s+/u', ' ', trim((string) $this->input('unit'))),
            'is_active' => $this->boolean('is_active'),
            'has_gear_detail' => $this->boolean('has_gear_detail'),
        ]);
    }

    public function rules(): array
    {
        $product = $this->route('product');
        $productId = $product instanceof Product ? $product->getKey() : null;

        return [
            'category_id' => [
                'nullable',
                'integer',
                Rule::exists('categories', 'id')->whereNull('deleted_at'),
            ],
            'name' => ['required', 'string', 'min:2', 'max:180'],
            'slug' => [
                'nullable',
                'string',
                'max:200',
                'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                Rule::unique('products', 'slug')->ignore($productId),
            ],
            'sku' => [
                'required',
                'string',
                'max:80',
                'regex:/^[A-Z0-9._-]+$/',
                Rule::unique('products', 'sku')->ignore($productId),
            ],
            'unit' => ['required', 'string', 'max:30'],
            'stock' => ['required', 'integer', 'min:0', 'max:1000000'],
            'low_stock_threshold' => ['required', 'integer', 'min:0', 'max:1000000'],
            'price' => ['required', 'integer', 'min:1000', 'max:1000000000'],
            'sale_price' => ['nullable', 'integer', 'min:1000', 'max:1000000000', 'lt:price'],
            'cost_price' => ['nullable', 'integer', 'min:0', 'max:1000000000', 'lte:price'],
            'short_desc' => ['nullable', 'string', 'max:500'],
            'description' => ['nullable', 'string', 'max:30000'],
            'sort_order' => ['required', 'integer', 'min:0', 'max:1000000'],
            'meta_title' => ['nullable', 'string', 'max:70'],
            'meta_description' => ['nullable', 'string', 'max:160'],
            'is_active' => ['required', 'boolean'],
            'has_gear_detail' => ['required', 'boolean'],
            'images' => ['nullable', 'array', 'max:8'],
            'images.*' => [
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:4096',
                'dimensions:min_width=300,min_height=300,max_width=6000,max_height=6000',
            ],
            'existing_image_order' => ['nullable', 'array'],
            'existing_image_order.*' => ['integer', 'min:0', 'max:1000'],
            'remove_images' => ['nullable', 'array'],
            'remove_images.*' => ['integer', 'distinct', 'exists:product_images,id'],
            'stock_note' => ['nullable', 'string', 'max:500', 'not_regex:/[<>]/'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Vui lòng nhập tên sản phẩm.',
            'slug.regex' => 'Slug chỉ gồm chữ thường, số và dấu gạch ngang.',
            'slug.unique' => 'Slug này đã được sử dụng.',
            'sku.required' => 'Vui lòng nhập SKU.',
            'sku.regex' => 'SKU chỉ gồm chữ in hoa, số, dấu chấm, gạch dưới hoặc gạch ngang.',
            'sku.unique' => 'SKU này đã được sử dụng.',
            'unit.required' => 'Vui lòng nhập đơn vị bán.',
            'price.min' => 'Giá bán phải từ 1.000đ.',
            'sale_price.lt' => 'Giá khuyến mãi phải nhỏ hơn giá gốc.',
            'cost_price.lte' => 'Giá vốn không được lớn hơn giá gốc.',
            'images.max' => 'Mỗi lần chỉ được tải tối đa 8 ảnh.',
            'images.*.image' => 'Tệp tải lên phải là hình ảnh hợp lệ.',
            'images.*.mimes' => 'Ảnh chỉ hỗ trợ JPG, PNG hoặc WebP.',
            'images.*.max' => 'Mỗi ảnh không được vượt quá 4MB.',
            'images.*.dimensions' => 'Ảnh phải từ 300x300 đến 6000x6000 pixel.',
        ];
    }
}
