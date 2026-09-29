<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';
madd_cors();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  madd_json(['ok' => false, 'error' => 'Method not allowed'], 405);
}

$body = madd_body();
$email = trim((string) ($body['email'] ?? ''));
$password = (string) ($body['password'] ?? '');

if ($email === '' || $password === '') {
  madd_json(['ok' => false, 'error' => 'البريد وكلمة المرور مطلوبان'], 422);
}

try {
  $pdo = madd_db();
  $stmt = $pdo->prepare('SELECT * FROM users WHERE email = ? LIMIT 1');
  $stmt->execute([$email]);
  $user = $stmt->fetch();

  if (!$user || !password_verify($password, $user['password_hash'])) {
    madd_json(['ok' => false, 'error' => 'بيانات الدخول غير صحيحة'], 401);
  }

  if (($user['status'] ?? '') !== 'active') {
    madd_json(['ok' => false, 'error' => 'الحساب غير مفعّل'], 403);
  }

  $token = bin2hex(random_bytes(32));
  $hours = (int) (madd_config()['app']['token_ttl_hours'] ?? 168);
  $expires = (new DateTimeImmutable("+{$hours} hours"))->format('Y-m-d H:i:s');

  $ins = $pdo->prepare('INSERT INTO auth_tokens (user_id, token, expires_at) VALUES (?, ?, ?)');
  $ins->execute([(int) $user['id'], $token, $expires]);

  madd_json([
    'ok' => true,
    'token' => $token,
    'expires_at' => $expires,
    'user' => madd_public_user($user),
  ]);
} catch (Throwable $e) {
  madd_json([
    'ok' => false,
    'error' => 'تعذّر الاتصال بقاعدة البيانات. تأكد من تشغيل MySQL واستيراد schema.sql',
    'detail' => $e->getMessage(),
  ], 500);
}
