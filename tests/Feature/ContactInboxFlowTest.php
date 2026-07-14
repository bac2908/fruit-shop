<?php

namespace Tests\Feature;

use App\Models\ContactMessage;
use App\Models\User;
use App\Notifications\ContactMessageReceivedNotification;
use App\Notifications\ContactReplyNotification;
use App\Services\ContactSpamGuard;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class ContactInboxFlowTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        config([
            'shop.contact.inbox_email' => 'admin@example.test',
            'shop.contact.min_fill_seconds' => 0,
        ]);
        Notification::fake();
    }

    public function test_valid_contact_is_stored_and_admin_is_notified(): void
    {
        $response = $this->post(route('contact.submit'), $this->payload());

        $response->assertRedirect(route('contact.page'));
        $this->assertDatabaseHas('contact_messages', [
            'email' => 'customer@example.test',
            'status' => ContactMessage::STATUS_NEW,
        ]);
        Notification::assertSentOnDemand(ContactMessageReceivedNotification::class);
    }

    public function test_honeypot_and_duplicate_submissions_do_not_pollute_inbox(): void
    {
        $honeypot = $this->payload(['website' => 'https://spam.example']);
        $this->post(route('contact.submit'), $honeypot)->assertRedirect(route('contact.page'));
        $this->assertDatabaseMissing('contact_messages', ['email' => 'customer@example.test']);

        $this->post(route('contact.submit'), $this->payload());
        $this->post(route('contact.submit'), $this->payload());

        $this->assertSame(1, ContactMessage::query()->where('email', 'customer@example.test')->count());
    }

    public function test_admin_can_open_and_reply_to_contact(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $contact = ContactMessage::query()->create([
            'name' => 'Khách hàng',
            'email' => 'customer@example.test',
            'message' => 'Tôi cần tư vấn một giỏ quà trái cây.',
            'status' => ContactMessage::STATUS_NEW,
            'spam_score' => 0,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.contacts.show', $contact))
            ->assertOk();

        $this->assertSame(ContactMessage::STATUS_READ, $contact->fresh()->status);

        $this->actingAs($admin)
            ->post(route('admin.contacts.reply', $contact), [
                'reply_message' => 'Shop đã nhận yêu cầu và sẽ gọi lại để tư vấn trong hôm nay.',
            ])
            ->assertRedirect();

        $this->assertSame(ContactMessage::STATUS_REPLIED, $contact->fresh()->status);
        Notification::assertSentOnDemand(ContactReplyNotification::class);
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'contact_token' => app(ContactSpamGuard::class)->issueToken(),
            'website' => '',
            'name' => 'Khách hàng',
            'email' => 'customer@example.test',
            'phone' => '0912345678',
            'message' => 'Tôi cần tư vấn sản phẩm giỏ quà trái cây cho gia đình.',
        ], $overrides);
    }
}
