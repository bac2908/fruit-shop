@if(session('success'))
    <div class="admin-alert success">{{ session('success') }}</div>
@endif

@if($errors->any())
    <div class="admin-alert error">
        <strong>Chưa thể lưu sản phẩm.</strong>
        <span>{{ $errors->first() }}</span>
    </div>
@endif
