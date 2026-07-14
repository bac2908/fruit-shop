<?php

namespace App\Models;

use App\Notifications\CustomerResetPasswordNotification;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'email_verified_at',
        'google_id',
        'auth_provider',
        'password',
        'phone',
        'address',
        'birthday',
        'gender',
        'avatar_url',
        'notify_order_status',
        'notify_promotions',
        'role',
        'password_changed_at',
        'force_password_change',
        'failed_login_attempts',
        'locked_until',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'birthday' => 'date',
        'notify_order_status' => 'boolean',
        'notify_promotions' => 'boolean',
        'role' => 'string',
        'password_changed_at' => 'datetime',
        'force_password_change' => 'boolean',
        'failed_login_attempts' => 'integer',
        'locked_until' => 'datetime',
    ];

    /**
     * Relationships
     */
    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public function cart()
    {
        return $this->hasOne(Cart::class);
    }

    public function addresses()
    {
        return $this->hasMany(UserAddress::class);
    }

    public function defaultAddress()
    {
        return $this->hasOne(UserAddress::class)->where('is_default', true);
    }

    public function wishlistItems()
    {
        return $this->hasMany(WishlistItem::class);
    }

    public function productViews()
    {
        return $this->hasMany(ProductView::class);
    }

    public function vouchers()
    {
        return $this->hasMany(UserVoucher::class);
    }

    /**
     * Check if user is admin
     */
    public function isAdmin()
    {
        return $this->role === 'admin';
    }

    public function sendPasswordResetNotification($token)
    {
        $this->notify(new CustomerResetPasswordNotification($token));
    }

    public function sendEmailVerificationNotification(): void
    {
        $this->notify(new \App\Notifications\CustomerVerifyEmailNotification());
    }
}
