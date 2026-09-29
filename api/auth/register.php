<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';
madd_cors();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  madd_json(['ok' => false, 'error' => 'Method not allowed'], 405);
}

$body = madd_body();
$name = trim((string) ($body['name'] ?? ''));
$email = trim((string) ($body['email'] ?? ''));
$phone = trim((string) ($body['phone'] ?? ''));
$password = (string) ($body['password'] ?? '');

if ($name === '' || $email === '' || $password === '') {
  madd_json(['ok' => false, 'error' => 'الاسم والبريد وكلمة المرور مطلوبة'], 422);
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
  madd_json(['ok' => false, 'error' => 'البريد غير صالح'], 422);
}

if (strlen($password) < 6) {
  madd_json(['ok' => false, 'error' => 'كلمة المرور يجب أن تكون 6 أحرف على الأقل'], 422);
}

try {
  $pdo = madd_db();
  $hash = password_hash($password, PASSWORD_DEFAULT);
  $stmt = $pdo->prepare(
    'INSERT INTO users (name, phone, email, password_hash, role, status)
     VALUES (?, ?, ?, ?, \'customer\', \'active\')'
  );
  $stmt->execute([$name, $phone !== '' ? $phone : null, $email, $hash]);

  $userId = (int) $pdo->lastInsertId();
  $token = bin2hex(random_bytes(32));
  $hours = (int) (madd_config()['app']['token_ttl_hours'] ?? 168);
  $expires = (new DateTimeImmutable("+{$hours} hours"))->format('Y-m-d H:i:s');

  $ins = $pdo->prepare('INSERT INTO auth_tokens (user_id, token, expires_at) VALUES (?, ?, ?)');
  $ins->execute([$userId, $token, $expires]);

  madd_json([
    'ok' => true,
    'token' => $token,
    'expires_at' => $expires,
    'user' => [
      'id' => $userId,
      'name' => $name,
      'email' => $email,
      'phone' => $phone !== '' ? $phone : null,
      'role' => 'customer',
    ],
  ], 201);
} catch (PDOException $e) {
  if ((int) $e->getCode() === 23000) {
    madd_json(['ok' => false, 'error' => 'البريد مستخدم مسبقاً'], 409);
  }
  madd_json(['ok' => false, 'error' => 'تعذّر إنشاء الحساب', 'detail' => $e->getMessage()], 500);
}
