<?php

namespace Tests\Feature;

use App\Models\Plan;
use App\Models\Subscriber;
use App\Models\WhatsappChatSession;
use App\Services\WhatsAppConversationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class WhatsAppConversationExtendTest extends TestCase
{
    use RefreshDatabase;

    private function makeSuspendedSubscriber(): Subscriber
    {
        $plan = Plan::query()->create([
            'name' => 'P50',
            'speed_mbps' => 50,
            'price' => 70,
            'duration_days' => 30,
            'is_active' => true,
        ]);

        $router = \App\Models\Router::query()->create([
            'name' => 'R1',
            'host' => '10.0.0.1',
            'api_port' => 8728,
            'username' => 'admin',
            'password' => 'secret',
            'is_active' => true,
        ]);

        return Subscriber::query()->create([
            'full_name' => 'عميل تجريبي',
            'phone' => '0599000111',
            'pppoe_username' => 'u'.uniqid(),
            'pppoe_password' => 'secret',
            'plan_id' => $plan->id,
            'router_id' => $router->id,
            'monthly_price' => 70,
            'start_date' => now()->subMonth()->toDateString(),
            'end_date' => now()->subDay()->toDateString(),
            'status' => 'suspended',
            'self_extend_count' => 0,
        ]);
    }

    public function test_subscription_number_assigned_on_create(): void
    {
        $subscriber = $this->makeSuspendedSubscriber();
        $this->assertSame(
            Subscriber::formatSubscriptionNumber((int) $subscriber->id),
            $subscriber->fresh()->subscription_number
        );
        $this->assertSame('MADD-000007', Subscriber::normalizeSubscriptionNumber('7'));
        $this->assertSame('MADD-000007', Subscriber::normalizeSubscriptionNumber('madd-7'));
    }

    public function test_webhook_verify_returns_challenge(): void
    {
        config(['services.whatsapp.webhook_verify_token' => 'secret-verify']);

        $this->get('/api/whatsapp/webhook?'.http_build_query([
            'hub.mode' => 'subscribe',
            'hub.verify_token' => 'secret-verify',
            'hub.challenge' => '12345',
        ]))->assertOk()->assertSee('12345');
    }

    public function test_button_then_subscription_number_extends(): void
    {
        config([
            'services.whatsapp.enabled' => true,
            'services.whatsapp.access_token' => 'token',
            'services.whatsapp.phone_number_id' => '1',
            'services.whatsapp.app_secret' => '',
        ]);

        Http::fake([
            'graph.facebook.com/*' => Http::response(['messages' => [['id' => 'wamid.x']]], 200),
        ]);

        $this->mock(\App\Services\MikrotikService::class, function ($mock) {
            $mock->shouldReceive('enablePppoeUser')->andReturnNull();
        });

        $subscriber = $this->makeSuspendedSubscriber();
        $svc = app(WhatsAppConversationService::class);

        $svc->handleWebhookPayload([
            'entry' => [[
                'changes' => [[
                    'value' => [
                        'messages' => [[
                            'from' => '970599000111',
                            'type' => 'button',
                            'button' => ['text' => 'مدد خطك 3 أيام', 'payload' => 'extend'],
                        ]],
                    ],
                ]],
            ]],
        ]);

        $this->assertDatabaseHas('whatsapp_chat_sessions', [
            'phone' => '970599000111',
            'state' => WhatsappChatSession::STATE_AWAIT_SUBSCRIPTION_NUMBER,
        ]);

        $svc->handleWebhookPayload([
            'entry' => [[
                'changes' => [[
                    'value' => [
                        'messages' => [[
                            'from' => '970599000111',
                            'type' => 'text',
                            'text' => ['body' => $subscriber->subscription_number],
                        ]],
                    ],
                ]],
            ]],
        ]);

        $subscriber->refresh();
        $this->assertSame('active', $subscriber->status);
        $this->assertSame(1, (int) $subscriber->self_extend_count);
        $this->assertDatabaseHas('whatsapp_chat_sessions', [
            'phone' => '970599000111',
            'state' => WhatsappChatSession::STATE_MENU,
        ]);
    }

    public function test_first_message_sends_start_menu_template(): void
    {
        config([
            'services.whatsapp.enabled' => true,
            'services.whatsapp.access_token' => 'token',
            'services.whatsapp.phone_number_id' => '1',
            'services.whatsapp.app_secret' => '',
            'services.whatsapp.template_start' => 'button_start_message',
            'services.whatsapp.template_language' => 'ar',
        ]);

        Http::fake([
            'graph.facebook.com/*' => Http::response(['messages' => [['id' => 'wamid.start']]], 200),
        ]);

        app(WhatsAppConversationService::class)->handleWebhookPayload([
            'entry' => [[
                'changes' => [[
                    'value' => [
                        'messages' => [[
                            'from' => '970599000222',
                            'type' => 'text',
                            'text' => ['body' => 'مرحبا'],
                        ]],
                    ],
                ]],
            ]],
        ]);

        $this->assertDatabaseHas('whatsapp_chat_sessions', [
            'phone' => '970599000222',
            'state' => WhatsappChatSession::STATE_MENU,
        ]);

        Http::assertSent(function ($request) {
            $data = $request->data();

            return ($data['type'] ?? '') === 'template'
                && ($data['template']['name'] ?? '') === 'button_start_message';
        });
    }

    public function test_support_button_then_subscription_number_reports_line(): void
    {
        config([
            'services.whatsapp.enabled' => true,
            'services.whatsapp.access_token' => 'token',
            'services.whatsapp.phone_number_id' => '1',
            'services.whatsapp.app_secret' => '',
            'services.whatsapp.template_check_line' => 'afhas_khatak',
            'services.whatsapp.template_language' => 'ar',
        ]);

        Http::fake([
            'graph.facebook.com/*' => Http::response(['messages' => [['id' => 'wamid.x']]], 200),
        ]);

        $subscriber = $this->makeSuspendedSubscriber();
        $svc = app(WhatsAppConversationService::class);

        $svc->handleWebhookPayload([
            'entry' => [[
                'changes' => [[
                    'value' => [
                        'messages' => [[
                            'from' => '970599000333',
                            'type' => 'button',
                            'button' => ['text' => 'الدعم الفني', 'payload' => 'support'],
                        ]],
                    ],
                ]],
            ]],
        ]);

        $this->assertDatabaseHas('whatsapp_chat_sessions', [
            'phone' => '970599000333',
            'state' => WhatsappChatSession::STATE_AWAIT_CHECK_LINE,
        ]);

        Http::assertSent(function ($request) {
            $data = $request->data();

            return ($data['type'] ?? '') === 'template'
                && ($data['template']['name'] ?? '') === 'afhas_khatak';
        });

        $svc->handleWebhookPayload([
            'entry' => [[
                'changes' => [[
                    'value' => [
                        'messages' => [[
                            'from' => '970599000333',
                            'type' => 'text',
                            'text' => ['body' => $subscriber->subscription_number],
                        ]],
                    ],
                ]],
            ]],
        ]);

        $this->assertDatabaseHas('whatsapp_chat_sessions', [
            'phone' => '970599000333',
            'state' => WhatsappChatSession::STATE_MENU,
        ]);

        Http::assertSent(function ($request) {
            $data = $request->data();
            $body = (string) data_get($data, 'text.body', '');

            return ($data['type'] ?? '') === 'text'
                && str_contains($body, 'نتيجة فحص الخط')
                && str_contains($body, (string) $subscriber->subscription_number);
        });
    }

    public function test_unknown_subscription_number_keeps_waiting(): void
    {
        config([
            'services.whatsapp.enabled' => true,
            'services.whatsapp.access_token' => 'token',
            'services.whatsapp.phone_number_id' => '1',
            'services.whatsapp.app_secret' => '',
        ]);

        Http::fake([
            'graph.facebook.com/*' => Http::response(['messages' => [['id' => 'wamid.x']]], 200),
        ]);

        WhatsappChatSession::query()->create([
            'phone' => '970599000111',
            'state' => WhatsappChatSession::STATE_AWAIT_SUBSCRIPTION_NUMBER,
            'expires_at' => now()->addHour(),
        ]);

        app(WhatsAppConversationService::class)->handleWebhookPayload([
            'entry' => [[
                'changes' => [[
                    'value' => [
                        'messages' => [[
                            'from' => '970599000111',
                            'type' => 'text',
                            'text' => ['body' => 'MADD-999999'],
                        ]],
                    ],
                ]],
            ]],
        ]);

        $this->assertDatabaseHas('whatsapp_chat_sessions', [
            'phone' => '970599000111',
            'state' => WhatsappChatSession::STATE_AWAIT_SUBSCRIPTION_NUMBER,
        ]);
    }
}
