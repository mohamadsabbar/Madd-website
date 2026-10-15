<?php

namespace Tests\Unit;

use App\Services\WhatsAppCloudService;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class WhatsAppCloudServiceTest extends TestCase
{
    public function test_normalize_phone_palestine(): void
    {
        $svc = new WhatsAppCloudService;

        $this->assertSame('970599123456', $svc->normalizePhone('0599123456'));
        $this->assertSame('970599123456', $svc->normalizePhone('+970599123456'));
        $this->assertSame('970599123456', $svc->normalizePhone('599123456'));
    }

    public function test_send_template_uses_http_fake_and_does_not_leak_token_in_exception(): void
    {
        config([
            'services.whatsapp.enabled' => true,
            'services.whatsapp.access_token' => 'secret-token-do-not-log',
            'services.whatsapp.phone_number_id' => '123456',
            'services.whatsapp.api_version' => 'v21.0',
            'services.whatsapp.template_language' => 'ar',
        ]);

        Http::fake([
            'graph.facebook.com/*' => Http::response([
                'messages' => [['id' => 'wamid.test123']],
            ], 200),
        ]);

        $svc = new WhatsAppCloudService;
        $result = $svc->sendTemplate('0599123456', 'madd_reminder_before', [
            'أحمد', 'INV-1', '90.00', '2026-10-15',
        ]);

        $this->assertTrue($result['ok']);
        $this->assertSame('wamid.test123', $result['message_id']);

        Http::assertSent(function ($request) {
            return str_contains($request->url(), '/123456/messages')
                && $request['to'] === '970599123456'
                && $request['template']['name'] === 'madd_reminder_before'
                && ! str_contains(json_encode($request->data()), 'secret-token-do-not-log');
        });
    }

    public function test_disabled_whatsapp_does_not_call_api(): void
    {
        config([
            'services.whatsapp.enabled' => false,
            'services.whatsapp.access_token' => 'token',
            'services.whatsapp.phone_number_id' => '123',
        ]);

        Http::fake();

        $svc = new WhatsAppCloudService;
        $result = $svc->sendTemplate('0599123456', 'tpl', ['a', 'b', 'c', 'd']);

        $this->assertFalse($result['ok']);
        Http::assertNothingSent();
    }
}
