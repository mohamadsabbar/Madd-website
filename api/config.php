<?php
/**
 * إعدادات محلية — لا ترفع للإنتاج بكلمات مرور حقيقية إن أمكن.
 * انسخ من config.example.php إذا لم يكن الملف موجوداً.
 */
$example = __DIR__ . '/config.example.php';
if (!is_file(__DIR__ . '/config.php') && is_file($example)) {
  // يسمح بالتشغيل مباشرة من المثال في بيئة التطوير
  return require $example;
}

return [
  'db' => [
    'host' => '127.0.0.1',
    'port' => 3306,
    'name' => 'madd_wifi',
    'user' => 'root',
    'pass' => '',
    'charset' => 'utf8mb4',
  ],
  'app' => [
    'cors_origin' => 'http://127.0.0.1:5173',
    'token_ttl_hours' => 168,
  ],
];
