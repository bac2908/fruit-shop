<?php

namespace App\Models;

use App\Notifications\CustomerResetPasswordNotification;
use App\Notifications\CustomerVerifyEmailNotification;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasApiTokens, HasFactory, Notifiable;

    public const ACCOUNT_STATUS_ACTIVE = 'active';

    public const ACCOUNT_STATUS_SUSPENDED = 'suspended';

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
        'admin_role',
        'account_status',
        'suspended_at',
        'suspended_by',
        'suspension_reason',
        'admin_note',
        'session_version',
        'last_login_at',
        'last_login_ip',
        'password_changed_at',
        'force_password_change',
        'two_factor_secret',
        'two_factor_recovery_codes',
        'two_factor_confirmed_at',
        'two_factor_last_used_step',
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
        'two_factor_secret',
        'two_factor_recovery_codes',
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
        'admin_role' => 'string',
        'suspended_at' => 'datetime',
        'session_version' => 'integer',
        'last_login_at' => 'datetime',
        'password_changed_at' => 'datetime',
        'force_password_change' => 'boolean',
        'two_factor_secret' => 'encrypted',
        'two_factor_recovery_codes' => 'encrypted:array',
        'two_factor_confirmed_at' => 'datetime',
        'two_factor_last_used_step' => 'integer',
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

    public function suspendedBy()
    {
        return $this->belongsTo(self::class, 'suspended_by');
    }

    /**
     * Check if user is admin
     */
    public function isAdmin()
    {
        return $this->role === 'admin';
    }

    public function hasAdminPermission(string $permission): bool
    {
        return app(\App\Services\AdminPermissionService::class)->allows($this, $permission);
    }

    public function hasTwoFactorAuthentication(): bool
    {
        return $this->isAdmin()
            && $this->two_factor_confirmed_at !== null
            && trim((string) $this->two_factor_secret) !== '';
    }

    public function isSuspended(): bool
    {
        return $this->account_status === self::ACCOUNT_STATUS_SUSPENDED;
    }

    public static function accountStatusLabels(): array
    {
        return [
            self::ACCOUNT_STATUS_ACTIVE => 'Đang hoạt động',
            self::ACCOUNT_STATUS_SUSPENDED => 'Tạm ngưng',
        ];
    }

    public function sendPasswordResetNotification($token)
    {
        $this->notify(new CustomerResetPasswordNotification($token));
    }

    public function sendEmailVerificationNotification(): void
    {
        $this->notify(new CustomerVerifyEmailNotification);
    }
}
