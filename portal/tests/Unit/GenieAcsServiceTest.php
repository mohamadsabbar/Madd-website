<?php

namespace Tests\Unit;

use App\Services\GenieAcsService;
use PHPUnit\Framework\TestCase;

class GenieAcsServiceTest extends TestCase
{
    public function test_parses_ssid_password_and_hosts(): void
    {
        $device = [
            '_id' => '00E0-HG-ABC123',
            '_lastInform' => date('c'),
            '_deviceId' => ['_SerialNumber' => 'ABC123'],
            'InternetGatewayDevice' => [
                'LANDevice' => [
                    '1' => [
                        'WLANConfiguration' => [
                            '1' => [
                                'SSID' => ['_value' => 'MADD_Home'],
                                'BSSID' => ['_value' => '11:22:33:44:55:66'],
                                'KeyPassphrase' => ['_value' => 'secret12'],
                            ],
                        ],
                        'Hosts' => [
                            'Host' => [
                                '1' => [
                                    'HostName' => ['_value' => 'iPhone 15 Pro'],
                                    'MACAddress' => ['_value' => 'aa:bb:cc:dd:ee:ff'],
                                    'IPAddress' => ['_value' => '192.168.1.10'],
                                    'Active' => ['_value' => true],
                                ],
                                '2' => [
                                    'HostName' => ['_value' => 'PlayStation 5'],
                                    'MACAddress' => ['_value' => 'AA:BB:CC:00:00:02'],
                                    'IPAddress' => ['_value' => '192.168.1.20'],
                                    'Active' => ['_value' => false],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $acs = new GenieAcsService;
        $wifi = $acs->wifiFromDevice($device);
        $hosts = $acs->hostsFromDevice($device);

        $this->assertSame('MADD_Home', $wifi['ssid']);
        $this->assertSame('secret12', $wifi['password']);
        $this->assertTrue($wifi['online']);
        $this->assertCount(2, $hosts);
        $this->assertSame('iPhone 15 Pro', $hosts[0]['name']);
        $this->assertSame('phone', $hosts[0]['kind']);
        $this->assertSame('AA:BB:CC:DD:EE:FF', $hosts[0]['mac']);
        $this->assertSame('console', $hosts[1]['kind']);
    }

    public function test_parses_hikvision_ds3wr12c_dual_band_and_associated_devices(): void
    {
        $device = [
            '_id' => 'A00001-DS%2D3WR12C%2DH-GM3239569',
            '_lastInform' => date('c'),
            '_deviceId' => [
                '_OUI' => 'A00001',
                '_Manufacturer' => 'Hikvision',
                '_ProductClass' => 'DS-3WR12C-H',
                '_SerialNumber' => 'GM3239569',
            ],
            'InternetGatewayDevice' => [
                'LANDevice' => [
                    '1' => [
                        'WLANConfiguration' => [
                            '1' => [
                                'SSID' => ['_value' => 'MADD_Home'],
                                'Channel' => ['_value' => 6],
                                'KeyPassphrase' => ['_value' => 'home-pass-24'],
                                'AssociatedDevice' => [
                                    '1' => [
                                        'AssociatedDeviceMACAddress' => ['_value' => '11:22:33:44:55:66'],
                                        'AssociatedDeviceIPAddress' => ['_value' => '192.168.1.15'],
                                        'AssociatedDeviceHostName' => ['_value' => 'iPhone 15'],
                                    ],
                                ],
                            ],
                            '2' => [
                                'SSID' => ['_value' => 'MADD_Home'],
                                'Channel' => ['_value' => 44],
                                'KeyPassphrase' => ['_value' => 'home-pass-24'],
                                'AssociatedDevice' => [
                                    '1' => [
                                        'AssociatedDeviceMACAddress' => ['_value' => '11:22:33:44:55:66'],
                                        'AssociatedDeviceIPAddress' => ['_value' => '192.168.1.15'],
                                        'AssociatedDeviceHostName' => ['_value' => 'iPhone 15'],
                                    ],
                                ],
                            ],
                            '3' => [
                                'SSID' => ['_value' => 'MADD_Guest'],
                                'KeyPassphrase' => ['_value' => 'guest-pass'],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $acs = new GenieAcsService;
        $wifi = $acs->wifiFromDevice($device);
        $hosts = $acs->hostsFromDevice($device);

        $this->assertSame('MADD_Home', $wifi['ssid']);
        $this->assertSame('home-pass-24', $wifi['password']);
        $this->assertSame('hikvision', $wifi['vendor']);
        $this->assertSame('DS-3WR12C-H', $wifi['model']);
        $this->assertTrue($wifi['dual_band']);
        $this->assertTrue($wifi['wifi_available']);
        $this->assertCount(3, $wifi['bands']);
        $this->assertSame('2.4GHz', $wifi['bands'][0]['band']);
        $this->assertSame('5GHz', $wifi['bands'][1]['band']);
        $this->assertTrue($wifi['bands'][2]['guest']);
        $this->assertCount(1, $hosts);
        $this->assertSame('iPhone 15', $hosts[0]['name']);
        $this->assertSame('11:22:33:44:55:66', $hosts[0]['mac']);
        $this->assertTrue($hosts[0]['active']);
    }

    public function test_reads_mercusys_vendor_password_key(): void
    {
        $device = [
            '_deviceId' => [
                '_Manufacturer' => 'MERCUSYS',
                '_ProductClass' => 'MR30G',
                '_SerialNumber' => '088AF12007CD',
            ],
            'InternetGatewayDevice' => [
                'LANDevice' => [
                    '1' => [
                        'WLANConfiguration' => [
                            '1' => [
                                'SSID' => ['_value' => 'Ameed'],
                                'X_TP_PreSharedKey' => ['_value' => 'tp-secret-99'],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $acs = new GenieAcsService;
        $wifi = $acs->wifiFromDevice($device);

        $this->assertSame('Ameed', $wifi['ssid']);
        $this->assertSame('tp-secret-99', $wifi['password']);
        $this->assertSame('mercusys', $wifi['vendor']);
    }

    public function test_normalizes_serial_and_mac(): void
    {
        $acs = new GenieAcsService;
        $this->assertSame('ABC123', $acs->normalizeSerial(' abc 123 '));
        $this->assertSame('88A0F12007CD', $acs->normalizeSerial('88:A0:F1:20:07:CD'));
        $this->assertSame('88A0F12007CD', $acs->normalizeMac('88-a0-f1-20-07-cd'));
    }

    public function test_encodes_hikvision_device_id_for_genieacs_nbi(): void
    {
        $acs = new GenieAcsService;
        $id = 'A00001-DS%2D3WR12C%2DH-GM3239569';

        $this->assertSame(
            'A00001-DS%25252D3WR12C%25252DH-GM3239569',
            $acs->encodeDeviceId($id),
        );
        $this->assertSame('00E0-HG-ABC123', $acs->encodeDeviceId('00E0-HG-ABC123'));
    }
}
