@php
    $isEditing = $product->exists;
    $formAction = $isEditing
        ? route('admin.products.update', $product)
        : route('admin.products.store');
@endphp

<form method="post" action="{{ $formAction }}" enctype="multipart/form-data" class="product-form">
    @csrf
    @if($isEditing)
        @method('PUT')
    @endif

    <section class="editor-layout">
        <div class="editor-main">
            <article class="panel form-panel">
                <div class="panel-head">
                    <div>
                        <h2 class="panel-title">Thông tin bán hàng</h2>
                        <p class="panel-sub">Tên, mã quản lý, danh mục và nội dung hiển thị trên storefront.</p>
                    </div>
                </div>

                <div class="form-grid two-columns">
                    <div class="field span-2">
                        <label for="name">Tên sản phẩm <span>*</span></label>
                        <input id="name" class="input @error('name') invalid @enderror" name="name" type="text" maxlength="180" value="{{ old('name', $product->name) }}" required>
                        @error('name')<small class="field-error">{{ $message }}</small>@enderror
                    </div>

                    <div class="field">
                        <label for="sku">SKU <span>*</span></label>
                        <input id="sku" class="input @error('sku') invalid @enderror" name="sku" type="text" maxlength="80" value="{{ old('sku', $product->sku) }}" placeholder="TGC-000001" required>
                        @error('sku')<small class="field-error">{{ $message }}</small>@enderror
                    </div>

                    <div class="field">
                        <label for="slug">Slug</label>
                        <input id="slug" class="input @error('slug') invalid @enderror" name="slug" type="text" maxlength="200" value="{{ old('slug', $product->slug) }}" placeholder="Tự tạo từ tên nếu để trống">
                        @error('slug')<small class="field-error">{{ $message }}</small>@enderror
                    </div>

                    <div class="field">
                        <label for="category_id">Danh mục</label>
                        <select id="category_id" class="select @error('category_id') invalid @enderror" name="category_id">
                            <option value="">Chưa phân loại</option>
                            @foreach($categories as $category)
                                <option value="{{ $category->id }}" @selected((string) old('category_id', $product->category_id) === (string) $category->id)>
                                    {{ $category->parent_id ? '— ' : '' }}{{ $category->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('category_id')<small class="field-error">{{ $message }}</small>@enderror
                    </div>

                    <div class="field">
                        <label for="unit">Đơn vị bán <span>*</span></label>
                        <input id="unit" class="input @error('unit') invalid @enderror" name="unit" type="text" maxlength="30" value="{{ old('unit', $product->unit) }}" placeholder="kg, hộp, giỏ..." required>
                        @error('unit')<small class="field-error">{{ $message }}</small>@enderror
                    </div>

                    <div class="field span-2">
                        <label for="short_desc">Mô tả ngắn</label>
                        <textarea id="short_desc" class="textarea compact @error('short_desc') invalid @enderror" name="short_desc" maxlength="500">{{ old('short_desc', $product->short_desc) }}</textarea>
                        <small class="field-help">Hiển thị ở phần tóm tắt sản phẩm, tối đa 500 ký tự.</small>
                        @error('short_desc')<small class="field-error">{{ $message }}</small>@enderror
                    </div>

                    <div class="field span-2">
                        <label for="description">Mô tả chi tiết</label>
                        <textarea id="description" class="textarea @error('description') invalid @enderror" name="description" maxlength="30000">{{ old('description', $product->description) }}</textarea>
                        <small class="field-help">Cho phép HTML cơ bản; mã script và thuộc tính nguy hiểm sẽ tự động bị loại bỏ.</small>
                        @error('description')<small class="field-error">{{ $message }}</small>@enderror
                    </div>
                </div>
            </article>

            <article class="panel form-panel">
                <div class="panel-head">
                    <div>
                        <h2 class="panel-title">Giá và tồn kho</h2>
                        <p class="panel-sub">Giá tính bằng VND. Thay đổi tồn kho sẽ được ghi vào sổ kho.</p>
                    </div>
                </div>

                <div class="form-grid three-columns">
                    <div class="field">
                        <label for="price">Giá gốc <span>*</span></label>
                        <input id="price" class="input @error('price') invalid @enderror" name="price" type="number" min="1000" max="1000000000" step="1000" value="{{ old('price', $product->price) }}" required>
                        @error('price')<small class="field-error">{{ $message }}</small>@enderror
                    </div>
                    <div class="field">
                        <label for="sale_price">Giá khuyến mãi</label>
                        <input id="sale_price" class="input @error('sale_price') invalid @enderror" name="sale_price" type="number" min="1000" max="1000000000" step="1000" value="{{ old('sale_price', $product->sale_price) }}">
                        @error('sale_price')<small class="field-error">{{ $message }}</small>@enderror
                    </div>
                    <div class="field">
                        <label for="cost_price">Giá vốn</label>
                        <input id="cost_price" class="input @error('cost_price') invalid @enderror" name="cost_price" type="number" min="0" max="1000000000" step="1000" value="{{ old('cost_price', $product->cost_price) }}">
                        @error('cost_price')<small class="field-error">{{ $message }}</small>@enderror
                    </div>
                    <div class="field">
                        <label for="stock">Tồn kho <span>*</span></label>
                        <input id="stock" class="input @error('stock') invalid @enderror" name="stock" type="number" min="0" max="1000000" value="{{ old('stock', $product->stock ?? 0) }}" required>
                        @error('stock')<small class="field-error">{{ $message }}</small>@enderror
                    </div>
                    <div class="field">
                        <label for="low_stock_threshold">Ngưỡng cảnh báo <span>*</span></label>
                        <input id="low_stock_threshold" class="input @error('low_stock_threshold') invalid @enderror" name="low_stock_threshold" type="number" min="0" max="1000000" value="{{ old('low_stock_threshold', $product->low_stock_threshold ?? 5) }}" required>
                        @error('low_stock_threshold')<small class="field-error">{{ $message }}</small>@enderror
                    </div>
                    <div class="field">
                        <label for="sort_order">Thứ tự hiển thị <span>*</span></label>
                        <input id="sort_order" class="input @error('sort_order') invalid @enderror" name="sort_order" type="number" min="0" max="1000000" value="{{ old('sort_order', $product->sort_order ?? 0) }}" required>
                        @error('sort_order')<small class="field-error">{{ $message }}</small>@enderror
                    </div>
                    <div class="field span-3">
                        <label for="stock_note">Lý do điều chỉnh kho</label>
                        <input id="stock_note" class="input @error('stock_note') invalid @enderror" name="stock_note" type="text" maxlength="500" value="{{ old('stock_note') }}" placeholder="Ví dụ: nhập thêm từ nhà cung cấp, kiểm kê thực tế...">
                        @error('stock_note')<small class="field-error">{{ $message }}</small>@enderror
                    </div>
                </div>
            </article>

            <article class="panel form-panel">
                <div class="panel-head">
                    <div>
                        <h2 class="panel-title">Hình ảnh sản phẩm</h2>
                        <p class="panel-sub">Tối đa 8 ảnh. Ảnh có thứ tự nhỏ nhất sẽ là ảnh đại diện.</p>
                    </div>
                </div>

                @if($isEditing && $product->images->isNotEmpty())
                    <div class="image-grid">
                        @foreach($product->images as $image)
                            <div class="image-item">
                                <img src="{{ $image->url ? \App\Support\MediaUrl::resolve($image->url) : '' }}" alt="Ảnh {{ $product->name }}">
                                <div class="image-controls">
                                    <label for="order_{{ $image->id }}">Thứ tự</label>
                                    <input id="order_{{ $image->id }}" class="input" type="number" min="0" max="1000" name="existing_image_order[{{ $image->id }}]" value="{{ old('existing_image_order.' . $image->id, $image->sort_order) }}">
                                    <label class="check-line danger-check">
                                        <input type="checkbox" name="remove_images[]" value="{{ $image->id }}" @checked(in_array($image->id, old('remove_images', [])))>
                                        Xóa khi lưu
                                    </label>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif

                <div class="upload-box">
                    <label for="images"><i class="ri-image-add-line"></i> Chọn ảnh mới</label>
                    <input id="images" name="images[]" type="file" accept="image/jpeg,image/png,image/webp" multiple>
                    <small>JPG, PNG hoặc WebP; 300x300 đến 6000x6000; tối đa 4MB mỗi ảnh.</small>
                </div>
                @error('images')<small class="field-error">{{ $message }}</small>@enderror
                @error('images.*')<small class="field-error">{{ $message }}</small>@enderror
            </article>

            <article class="panel form-panel">
                <div class="panel-head">
                    <div>
                        <h2 class="panel-title">SEO</h2>
                        <p class="panel-sub">Tiêu đề và mô tả dùng cho Google và chia sẻ mạng xã hội.</p>
                    </div>
                </div>
                <div class="form-grid two-columns">
                    <div class="field">
                        <label for="meta_title">Meta title</label>
                        <input id="meta_title" class="input @error('meta_title') invalid @enderror" name="meta_title" type="text" maxlength="70" value="{{ old('meta_title', $product->meta_title) }}">
                        @error('meta_title')<small class="field-error">{{ $message }}</small>@enderror
                    </div>
                    <div class="field">
                        <label for="meta_description">Meta description</label>
                        <textarea id="meta_description" class="textarea compact @error('meta_description') invalid @enderror" name="meta_description" maxlength="160">{{ old('meta_description', $product->meta_description) }}</textarea>
                        @error('meta_description')<small class="field-error">{{ $message }}</small>@enderror
                    </div>
                </div>
            </article>
        </div>

        <aside class="editor-side">
            <article class="panel form-panel sticky-panel">
                <h2 class="panel-title">Xuất bản</h2>

                <input type="hidden" name="is_active" value="0">
                <label class="check-card">
                    <input type="checkbox" name="is_active" value="1" @checked((bool) old('is_active', $product->is_active))>
                    <span>
                        <strong>Hiển thị sản phẩm</strong>
                        <small>Chỉ bật khi giá và hình ảnh đã hoàn chỉnh.</small>
                    </span>
                </label>

                <input type="hidden" name="has_gear_detail" value="0">
                <label class="check-card">
                    <input type="checkbox" name="has_gear_detail" value="1" @checked((bool) old('has_gear_detail', $product->has_gear_detail))>
                    <span>
                        <strong>Sản phẩm đặt theo yêu cầu</strong>
                        <small>Dùng cho giỏ quà, mâm cúng hoặc sản phẩm cần tư vấn.</small>
                    </span>
                </label>

                <div class="save-actions">
                    <button class="btn btn-primary" type="submit"><i class="ri-save-3-line"></i>{{ $isEditing ? 'Lưu thay đổi' : 'Tạo sản phẩm' }}</button>
                    <a class="btn btn-ghost" href="{{ route('admin.products') }}">Hủy</a>
                </div>

                @if($isEditing)
                    <div class="record-meta">
                        <span>ID: {{ $product->id }}</span>
                        <span>Cập nhật: {{ \App\Support\LocalDateTime::format($product->updated_at) }}</span>
                    </div>
                @endif
            </article>
        </aside>
    </section>
</form>
