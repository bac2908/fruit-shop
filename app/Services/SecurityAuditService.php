<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class SecurityAuditService
{
    public function record(string $action, array $context = [], array $metadata = []): void
    {
        if (! Schema::hasTable('security_audit_log')) {
            return;
        }

        DB::table('security_audit_log')->insert([
            'user_id' => $context['user_id'] ?? null,
            'action' => $action,
            'ip_address' => $context['ip_address'] ?? null,
            'user_agent' => $context['user_agent'] ?? null,
            'metadata' => $metadata === []
                ? null
                : json_encode($metadata, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'created_at' => now(),
        ]);
    }
}
