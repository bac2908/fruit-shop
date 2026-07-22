<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AdminCustomerService
{
    public function __construct(private SecurityAuditService $audit) {}

    public function updateProfile(User $customer, array $data, array $auditContext): User
    {
        return DB::transaction(function () use ($customer, $data, $auditContext) {
            $customer = $this->lockedCustomer($customer->id);
            $fields = ['name', 'phone', 'birthday', 'gender', 'admin_note'];
            $before = Arr::only($customer->getAttributes(), $fields);
            $customer->forceFill(Arr::only($data, $fields))->save();

            $this->audit->record('admin_customer_profile_updated', $auditContext, [
                'customer_id' => $customer->id,
                'changes' => $this->changes($before, $customer, $fields),
            ]);

            return $customer->fresh();
        });
    }

    public function suspend(User $customer, string $reason, array $auditContext): User
    {
        return DB::transaction(function () use ($customer, $reason, $auditContext) {
            $customer = $this->lockedCustomer($customer->id);

            if ($customer->isSuspended()) {
                throw ValidationException::withMessages([
                    'account' => 'Tài khoản này đã được tạm ngưng.',
                ]);
            }

            $customer->forceFill([
                'account_status' => User::ACCOUNT_STATUS_SUSPENDED,
                'suspended_at' => now(),
                'suspended_by' => $auditContext['user_id'] ?? null,
                'suspension_reason' => $reason,
            ]);
            $this->invalidateSessions($customer);
            $customer->save();

            $this->audit->record('admin_customer_suspended', $auditContext, [
                'customer_id' => $customer->id,
                'reason' => $reason,
            ]);

            return $customer->fresh('suspendedBy');
        });
    }

    public function activate(User $customer, array $auditContext): User
    {
        return DB::transaction(function () use ($customer, $auditContext) {
            $customer = $this->lockedCustomer($customer->id);

            if (! $customer->isSuspended()) {
                throw ValidationException::withMessages([
                    'account' => 'Tài khoản này đang hoạt động.',
                ]);
            }

            $customer->forceFill([
                'account_status' => User::ACCOUNT_STATUS_ACTIVE,
                'suspended_at' => null,
                'suspended_by' => null,
                'suspension_reason' => null,
                'failed_login_attempts' => 0,
                'locked_until' => null,
            ])->save();

            $this->audit->record('admin_customer_activated', $auditContext, [
                'customer_id' => $customer->id,
            ]);

            return $customer->fresh();
        });
    }

    public function unlock(User $customer, array $auditContext): User
    {
        return DB::transaction(function () use ($customer, $auditContext) {
            $customer = $this->lockedCustomer($customer->id);
            $customer->forceFill([
                'failed_login_attempts' => 0,
                'locked_until' => null,
            ])->save();

            $this->audit->record('admin_customer_login_unlocked', $auditContext, [
                'customer_id' => $customer->id,
            ]);

            return $customer->fresh();
        });
    }

    public function revokeSessions(User $customer, array $auditContext): User
    {
        return DB::transaction(function () use ($customer, $auditContext) {
            $customer = $this->lockedCustomer($customer->id);
            $this->invalidateSessions($customer);
            $customer->save();

            $this->audit->record('admin_customer_sessions_revoked', $auditContext, [
                'customer_id' => $customer->id,
                'session_version' => (int) $customer->session_version,
            ]);

            return $customer->fresh();
        });
    }

    public function membership(int $totalSpent): array
    {
        if ($totalSpent >= 10000000) {
            return ['tier' => 'Kim cương', 'points' => intdiv($totalSpent, 10000)];
        }

        if ($totalSpent >= 5000000) {
            return ['tier' => 'Vàng', 'points' => intdiv($totalSpent, 10000)];
        }

        if ($totalSpent >= 2000000) {
            return ['tier' => 'Bạc', 'points' => intdiv($totalSpent, 10000)];
        }

        return ['tier' => 'Đồng', 'points' => intdiv($totalSpent, 10000)];
    }

    private function lockedCustomer(int $customerId): User
    {
        return User::query()
            ->whereKey($customerId)
            ->where('role', 'customer')
            ->lockForUpdate()
            ->firstOrFail();
    }

    private function invalidateSessions(User $customer): void
    {
        $customer->session_version = max(1, (int) $customer->session_version) + 1;
        $customer->setRememberToken(Str::random(60));

        if (Schema::hasTable('personal_access_tokens')) {
            $customer->tokens()->delete();
        }

        if (Schema::hasTable('secure_sessions')) {
            DB::table('secure_sessions')->where('user_id', $customer->id)->delete();
        }

        $sessionTable = (string) config('session.table', 'sessions');
        if (Schema::hasTable($sessionTable) && Schema::hasColumn($sessionTable, 'user_id')) {
            DB::table($sessionTable)->where('user_id', $customer->id)->delete();
        }
    }

    private function changes(array $before, User $customer, array $fields): array
    {
        return collect($fields)
            ->filter(fn (string $field) => ($before[$field] ?? null) != $customer->getAttribute($field))
            ->mapWithKeys(fn (string $field) => [$field => [
                'from' => $before[$field] ?? null,
                'to' => $customer->getAttribute($field),
            ]])
            ->all();
    }
}
