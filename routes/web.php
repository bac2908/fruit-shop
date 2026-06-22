<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\AuthController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// Trang chủ
Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/collections/all', [ProductController::class, 'index'])->name('products.index');
Route::view('/pages/about-us', 'pages.about')->name('about');
Route::view('/pages/lien-he', 'pages.contact')->name('contact.page');
Route::get('/pages/frontpage', [PageController::class, 'frontpage'])->name('page.frontpage');
Route::get('/pages/cau-hoi-thuong-gap', [PageController::class, 'faq'])->name('page.faq');
Route::get('/pages/chinh-sach-bao-mat', [PageController::class, 'privacyPolicy'])->name('page.privacy');
Route::get('/pages/chinh-sach-bao-mat-thong-tin', [PageController::class, 'privacyInfo'])->name('page.privacy.info');
Route::get('/pages/chinh-sach-doi-tra', [PageController::class, 'returnPolicy'])->name('page.return');
Route::get('/pages/chinh-sach-giao-hang-va-thanh-toan', [PageController::class, 'shippingPaymentPolicy'])->name('page.shipping.payment');
Route::get('/pages/dieu-khoan-dich-vu', [PageController::class, 'termsOfService'])->name('page.terms');
Route::get('/pages/khach-hang-doanh-nghiep', [PageController::class, 'corporateCustomers'])->name('page.corporate');

Route::middleware('guest')->group(function () {
	Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
	Route::post('/login', [AuthController::class, 'login'])->name('login.post');
	Route::get('/register', [AuthController::class, 'showRegisterForm'])->name('register');
});

Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth')->name('logout');

// Admin FE-first routes (UI only, BE data wiring in the next phase)
Route::prefix('admin')->name('admin.')->middleware(['auth', 'admin'])->group(function () {
	Route::get('/', function () {
		return view('admin.dashboard');
	})->name('dashboard');

	Route::get('/products', function () {
		return view('admin.products');
	})->name('products');

	Route::get('/orders', function () {
		return view('admin.orders');
	})->name('orders');

	Route::get('/customers', function () {
		return view('admin.customers');
	})->name('customers');

	Route::get('/coupons', function () {
		return view('admin.coupons');
	})->name('coupons');

	Route::get('/reports', function () {
		return view('admin.reports');
	})->name('reports');

	Route::get('/settings', function () {
		return view('admin.settings');
	})->name('settings');
});

// Trang danh mục sản phẩm
Route::get('/collections/{slug}/{tag}', [CategoryController::class, 'show'])->name('categories.show.tag');
Route::get('/collections/{slug}', [CategoryController::class, 'show'])->name('categories.show');

// Trang chi tiết sản phẩm
Route::get('/products/{slug}', [ProductController::class, 'show'])->name('products.show');

// Các trang khác (Giỏ hàng, Tìm kiếm...)
Route::get('/cart', [CartController::class, 'index'])->name('cart');
Route::post('/cart/add', [CartController::class, 'add'])->name('cart.add');
Route::post('/cart/update', [CartController::class, 'update'])->name('cart.update');
Route::post('/cart/remove', [CartController::class, 'remove'])->name('cart.remove');
Route::post('/cart/coupon', [CartController::class, 'applyCoupon'])->name('cart.coupon.apply');
Route::post('/cart/coupon/remove', [CartController::class, 'removeCoupon'])->name('cart.coupon.remove');
Route::get('/checkout', [CartController::class, 'checkout'])->name('checkout');
Route::post('/checkout/place-order', [CartController::class, 'placeOrder'])->name('checkout.place');
Route::get('/checkout/thank-you/{code}/{token?}', [CartController::class, 'thankYou'])->name('checkout.thankyou');
Route::get('/search', [ProductController::class, 'search'])->name('search');
