<?php
/**
 * انسخ هذا الملف إلى config.php وعدّل القيم حسب بيئة XAMPP/MAMP لديك.
 */
return [
  'db' => [
    'host' => '127.0.0.1',
    'port' => 3306,
    'name' => 'madd_wifi',
    'user' => 'root',
    'pass' => '', // كلمة مرور MySQL (فارغة غالباً في XAMPP)
    'charset' => 'utf8mb4',
  ],
  'app' => [
    'cors_origin' => 'http://127.0.0.1:5173',
    'token_ttl_hours' => 168, // 7 أيام
  ],
];
