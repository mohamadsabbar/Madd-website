<?php

namespace Tests\Feature;

use App\Models\Plan;
use App\Models\Subscriber;
use App\Models\WhatsappChatSession;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ChatwootAgentBotExtendTest extends TestCase
{
    use RefreshDatabase;

    public function test_chatwoot_bot_extend_flow(): void
    {
        config([
            'services.chatwoot.base_url' => 'https://chat.madd.ps',
            'services.chatwoot.account_id' => 1,
            'services.chatwoot.bot_access_token' => 'bot-token',
            'services.chatwoot.bot_webhook_token' => 'secret-hook',
            'services.whatsapp.enabled' => true,
            'services.whatsapp.access_token' => 'wa-token',
            'services.whatsapp.phone_number_id' => '1',
        ]);

        Http::fake([
            'chat.madd.ps/*' => Http::response(['id' => 1], 200),
            'graph.facebook.com/*' => Http::response(['messages' => [['id' => 'wamid.x']]], 200),
        ]);

        $this->mock(\App\Services\MikrotikService::class, function ($mock) {
            $mock->shouldReceive('enablePppoeUser')->andReturnNull();
        });

        $plan = Plan::query()->create([
            'name' => 'P',
            'speed_mbps' => 50,
            'price' => 70,
            'duration_days' => 30,
            'is_active' => true,
        ]);
        $router = \App\Models\Router::query()->create([
            'name' => 'R',
            'host' => '1.1.1.1',
            'api_port' => 8728,
            'username' => 'a',
            'password' => 'b',
            'is_active' => true,
        ]);
        $subscriber = Subscriber::query()->create([
            'full_name' => 'عميل',
            'phone' => '0599000111',
            'pppoe_username' => 'u'.uniqid(),
            'pppoe_password' => 'x',
            'plan_id' => $plan->id,
            'router_id' => $router->id,
            'monthly_price' => 70,
            'start_date' => now()->subMonth()->toDateString(),
            'end_date' => now()->subDay()->toDateString(),
            'status' => 'suspended',
            'self_extend_count' => 0,
        ]);

        $this->postJson('/api/whatsapp/chatwoot-bot?token=secret-hook', [
            'event' => 'message_created',
            'message_type' => 'incoming',
            'content' => 'مدد خطك 3 أيام',
            'sender' => ['phone_number' => '+970599000111', 'type' => 'contact'],
            'conversation' => ['id' => 10, 'display_id' => 10],
            'account' => ['id' => 1],
        ])->assertOk();

        $this->assertDatabaseHas('whatsapp_chat_sessions', [
            'phone' => '970599000111',
            'state' => WhatsappChatSession::STATE_AWAIT_SUBSCRIPTION_NUMBER,
        ]);

        $this->postJson('/api/whatsapp/chatwoot-bot?token=secret-hook', [
            'event' => 'message_created',
            'message_type' => 'incoming',
            'content' => $subscriber->subscription_number,
            'sender' => ['phone_number' => '+970599000111', 'type' => 'contact'],
            'conversation' => ['id' => 10, 'display_id' => 10],
            'account' => ['id' => 1],
        ])->assertOk();

        $this->assertSame('active', $subscriber->fresh()->status);

        Http::assertSent(function ($request) {
            return str_contains($request->url(), '/conversations/10/messages')
                && ($request['content'] ?? '') !== '';
        });
    }

    public function test_rejects_bad_token(): void
    {
        config(['services.chatwoot.bot_webhook_token' => 'secret-hook']);

        $this->postJson('/api/whatsapp/chatwoot-bot?token=wrong', [
            'event' => 'message_created',
            'message_type' => 'incoming',
            'content' => 'hi',
        ])->assertForbidden();
    }
}
