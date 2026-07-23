<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Xác minh quản trị | FruitShop</title>
    <style>
        *{box-sizing:border-box}body{margin:0;min-height:100vh;display:grid;place-items:center;padding:20px;background:#f1f5ed;color:#173425;font-family:Arial,sans-serif}.card{width:min(440px,100%);padding:28px;border:1px solid #d6e1d5;border-radius:8px;background:#fff;box-shadow:0 18px 48px rgba(20,60,35,.1)}h1{margin:0 0 8px;font-size:25px}p{color:#66766d;line-height:1.6}.code{width:100%;height:50px;border:1px solid #a8bcae;border-radius:7px;padding:0 14px;font-size:18px;letter-spacing:.08em}.button{width:100%;height:48px;margin-top:12px;border:0;border-radius:7px;background:#1f7a4a;color:#fff;font-weight:700;cursor:pointer}.error{display:block;margin-top:7px;color:#b53f2d}.links{display:flex;justify-content:space-between;align-items:center;margin-top:18px;font-size:13px}.links button{border:0;background:none;color:#42614e;cursor:pointer}
    </style>
</head>
<body>
<main class="card">
    <h1>Xác minh quản trị</h1>
    <p>Nhập mã 6 số từ ứng dụng Authenticator hoặc một mã khôi phục chưa sử dụng.</p>
    <form method="post" action="{{ route('admin.2fa.challenge.verify') }}">
        @csrf
        <input class="code" name="code" maxlength="30" required autofocus autocomplete="one-time-code" placeholder="Mã xác thực">
        @error('code')<span class="error">{{ $message }}</span>@enderror
        <button class="button" type="submit">Xác minh và tiếp tục</button>
    </form>
    <div class="links">
        <span>{{ auth()->user()->email }}</span>
        <form method="post" action="{{ route('logout') }}">@csrf<button type="submit">Đăng xuất</button></form>
    </div>
</main>
</body>
</html>
