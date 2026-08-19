<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class PruneSecurityData extends Command
{
    protected $signature = 'shop:prune-security-data
        {--audit-days=90 : Keep security audit entries for this many days}
        {--failed-login-days=30 : Keep failed login attempts for this many days}';

    protected $description = 'Delete expired sessions and security records past their retention period.';

    public function handle(): int
    {
        $auditDays = max(1, (int) $this->option('audit-days'));
        $failedLoginDays = max(1, (int) $this->option('failed-login-days'));

        $deleted = [
            'Expired sessions' => $this->deleteExpiredSessions(),
            'Old audit entries' => $this->deleteOlderThan('security_audit_log', 'created_at', $auditDays),
            'Old failed logins' => $this->deleteOlderThan('failed_login_attempts', 'attempt_time', $failedLoginDays),
        ];

        $this->table(
            ['Data', 'Deleted'],
            collect($deleted)->map(fn (int $count, string $label) => [$label, $count])->values()->all()
        );

        return self::SUCCESS;
    }

    private function deleteExpiredSessions(): int
    {
        if (! Schema::hasTable('secure_sessions')) {
            return 0;
        }

        return DB::table('secure_sessions')
            ->whereNotNull('expires_at')
            ->where('expires_at', '<', now())
            ->delete();
    }

    private function deleteOlderThan(string $table, string $column, int $days): int
    {
        if (! Schema::hasTable($table)) {
            return 0;
        }

        return DB::table($table)
            ->where($column, '<', now()->subDays($days))
            ->delete();
    }
}
