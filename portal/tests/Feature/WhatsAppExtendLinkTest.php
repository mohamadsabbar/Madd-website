<?php

namespace Tests\Feature;

use App\Models\Plan;
use App\Models\Subscriber;
use App\Models\WhatsappExtendToken;
use App\Services\CustomerPortalService;
use App\Services\WhatsAppExtendLinkService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WhatsAppExtendLinkTest extends TestCase
{
    use RefreshDatabase;

    private function makeSuspendedSubscriber(): Subscriber
    {
        $plan = Plan::query()->create([
            'name' => 'Test 50',
            'speed_mbps' => 50,
            'price' => 70,
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

        return Subscriber::query()->create([
            'full_name' => 'مشترك تجريبي',
            'phone' => '0599111222',
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

    public function test_issue_token_and_reject_expired(): void
    {
        config(['services.whatsapp.cta_extend_stages' => 'before_1,suspend']);

        $subscriber = $this->makeSuspendedSubscriber();
        $links = app(WhatsAppExtendLinkService::class);

        $this->assertTrue($links->stageSupportsCta('before_1'));
        $this->assertFalse($links->stageSupportsCta('before_3'));

        $token = $links->issueToken($subscriber, 'suspend');
        $this->assertNotNull($links->findValid($token->token));

        $token->update(['expires_at' => now()->subMinute()]);
        $this->assertNull($links->findValid($token->token));
    }

    public function test_self_extend_state_allows_suspended(): void
    {
        $subscriber = $this->makeSuspendedSubscriber();
        $state = app(CustomerPortalService::class)->selfExtendState($subscriber);

        $this->assertTrue($state['can_self_extend']);
        $this->assertSame(3, $state['next_days']);
    }

    public function test_wa_extend_route_extends_suspended_subscriber(): void
    {
        $subscriber = $this->makeSuspendedSubscriber();
        $links = app(WhatsAppExtendLinkService::class);
        $token = $links->issueToken($subscriber, 'suspend');

        $this->mock(\App\Services\MikrotikService::class, function ($mock) {
            $mock->shouldReceive('enablePppoeUser')->andReturnNull();
        });

        $response = $this->get('/wa/extend/'.$token->token);

        $response->assertOk();
        $response->assertSee('تم تمديد الخط', false);

        $subscriber->refresh();
        $this->assertSame('active', $subscriber->status);
        $this->assertSame(1, (int) $subscriber->self_extend_count);
        $this->assertNotNull(WhatsappExtendToken::query()->find($token->id)?->used_at);
    }

    public function test_wa_extend_rejects_invalid_token(): void
    {
        $this->get('/wa/extend/notarealtoken123')->assertStatus(410);
    }
}
