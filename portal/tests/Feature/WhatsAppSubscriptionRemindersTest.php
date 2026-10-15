<?php

namespace Tests\Feature;

use App\Models\Invoice;
use App\Models\Plan;
use App\Models\Subscriber;
use App\Models\User;
use App\Models\WhatsappReminderLog;
use App\Services\WhatsAppSubscriptionReminderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class WhatsAppSubscriptionRemindersTest extends TestCase
{
    use RefreshDatabase;

    private function seedOpenInvoice(array $subscriberAttrs = [], array $invoiceAttrs = []): array
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'is_active' => true,
        ]);
        $plan = Plan::query()->create([
            'name' => 'Test 100',
            'speed_mbps' => 100,
            'price' => 90,
            'duration_days' => 30,
            'is_active' => true,
        ]);
        $router = \App\Models\Router::query()->create([
            'name' => 'R1',
            'host' => '192.168.88.1',
            'api_port' => 8728,
            'username' => 'admin',
            'password' => 'secret',
            'is_active' => true,
        ]);

        $subscriber = Subscriber::query()->create(array_merge([
            'full_name' => 'أحمد تجريبي',
            'phone' => '0599123456',
            'pppoe_username' => 'testuser'.uniqid(),
            'pppoe_password' => 'secret',
            'plan_id' => $plan->id,
            'router_id' => $router->id,
            'monthly_price' => 90,
            'start_date' => now()->subDays(27)->toDateString(),
            'end_date' => now()->addDays(3)->toDateString(),
            'status' => 'active',
        ], $subscriberAttrs));

        $invoice = Invoice::query()->create(array_merge([
            'invoice_number' => 'INV-'.uniqid(),
            'subscriber_id' => $subscriber->id,
            'plan_id' => $plan->id,
            'created_by' => $admin->id,
            'amount' => 90,
            'due_date' => now()->addDays(3)->toDateString(),
            'status' => 'unpaid',
        ], $invoiceAttrs));

        return [$subscriber, $invoice];
    }

    public function test_dry_run_before_3_days_does_not_send_or_log_sent(): void
    {
        config([
            'services.whatsapp.enabled' => true,
            'services.whatsapp.access_token' => 'token',
            'services.whatsapp.phone_number_id' => '1',
            'services.whatsapp.template_before' => 'madd_before',
            'services.whatsapp.template_after' => 'madd_after',
            'services.whatsapp.before_days' => [3, 1],
        ]);

        Http::fake();
        $this->seedOpenInvoice(['end_date' => now()->addDays(3)->toDateString()]);

        $result = app(WhatsAppSubscriptionReminderService::class)->process(true);

        $this->assertSame(1, $result['candidates']);
        $this->assertSame(0, $result['sent']);
        $this->assertSame('dry-run', $result['rows'][0]['status']);
        Http::assertNothingSent();
        $this->assertDatabaseCount('whatsapp_reminder_logs', 0);
    }

    public function test_sends_before_3_once_and_idempotent_on_second_run(): void
    {
        config([
            'services.whatsapp.enabled' => true,
            'services.whatsapp.access_token' => 'token',
            'services.whatsapp.phone_number_id' => '1',
            'services.whatsapp.template_before' => 'madd_before',
            'services.whatsapp.template_after' => 'madd_after',
            'services.whatsapp.before_days' => [3, 1],
        ]);

        Http::fake([
            'graph.facebook.com/*' => Http::response(['messages' => [['id' => 'wamid.1']]], 200),
        ]);

        [$subscriber, $invoice] = $this->seedOpenInvoice([
            'end_date' => now()->addDays(3)->toDateString(),
        ]);

        $svc = app(WhatsAppSubscriptionReminderService::class);
        $first = $svc->process(false);
        $second = $svc->process(false);

        $this->assertSame(1, $first['sent']);
        $this->assertSame(0, $second['sent']);
        $this->assertDatabaseHas('whatsapp_reminder_logs', [
            'subscriber_id' => $subscriber->id,
            'invoice_id' => $invoice->id,
            'stage' => WhatsappReminderLog::STAGE_BEFORE_3,
            'status' => 'sent',
        ]);
    }

    public function test_paid_invoice_is_not_reminded(): void
    {
        config([
            'services.whatsapp.enabled' => true,
            'services.whatsapp.access_token' => 'token',
            'services.whatsapp.phone_number_id' => '1',
            'services.whatsapp.template_before' => 'madd_before',
            'services.whatsapp.before_days' => [3, 1],
        ]);

        Http::fake();
        $this->seedOpenInvoice(
            ['end_date' => now()->addDays(3)->toDateString()],
            ['status' => 'paid', 'paid_at' => now()->toDateString()]
        );

        $result = app(WhatsAppSubscriptionReminderService::class)->process(false);

        $this->assertSame(0, $result['candidates']);
        Http::assertNothingSent();
    }
}
