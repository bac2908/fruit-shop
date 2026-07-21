@if(session('success'))
    <div class="coupon-alert success">{{ session('success') }}</div>
@endif

@if($errors->any())
    <div class="coupon-alert danger" role="alert">
        @foreach($errors->all() as $error)<div>{{ $error }}</div>@endforeach
    </div>
@endif
