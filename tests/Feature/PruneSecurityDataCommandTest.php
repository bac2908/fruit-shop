<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PruneSecurityDataCommandTest extends TestCase
{
    use DatabaseTransactions;

    public function test_it_prunes_only_expired_security_data(): void
    {
        DB::table('secure_sessions')->insert([
            [
                'session_id' => 'expired-session',
                'expires_at' => now()->subMinute(),
                'created_at' => now()->subDay(),
                'updated_at' => now()->subDay(),
            ],
            [
                'session_id' => 'active-session',
                'expires_at' => now()->addHour(),
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
        DB::table('security_audit_log')->insert([
            ['action' => 'old-event', 'created_at' => now()->subDays(91)],
            ['action' => 'recent-event', 'created_at' => now()->subDays(89)],
        ]);
        DB::table('failed_login_attempts')->insert([
            ['email' => 'old@example.test', 'attempt_time' => now()->subDays(31)],
            ['email' => 'recent@example.test', 'attempt_time' => now()->subDays(29)],
        ]);

        $this->artisan('shop:prune-security-data')->assertSuccessful();

        $this->assertDatabaseMissing('secure_sessions', ['session_id' => 'expired-session']);
        $this->assertDatabaseHas('secure_sessions', ['session_id' => 'active-session']);
        $this->assertDatabaseMissing('security_audit_log', ['action' => 'old-event']);
        $this->assertDatabaseHas('security_audit_log', ['action' => 'recent-event']);
        $this->assertDatabaseMissing('failed_login_attempts', ['email' => 'old@example.test']);
        $this->assertDatabaseHas('failed_login_attempts', ['email' => 'recent@example.test']);
    }
}
