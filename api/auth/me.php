<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';
madd_cors();

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
  madd_json(['ok' => false, 'error' => 'Method not allowed'], 405);
}

$token = madd_bearer_token();
if (!$token) {
  madd_json(['ok' => false, 'error' => 'غير مسجّل الدخول'], 401);
}

try {
  $pdo = madd_db();
  $stmt = $pdo->prepare(
    'SELECT u.* FROM auth_tokens t
     INNER JOIN users u ON u.id = t.user_id
     WHERE t.token = ? AND t.expires_at > NOW() AND u.status = \'active\'
     LIMIT 1'
  );
  $stmt->execute([$token]);
  $user = $stmt->fetch();

  if (!$user) {
    madd_json(['ok' => false, 'error' => 'الجلسة منتهية'], 401);
  }

  madd_json(['ok' => true, 'user' => madd_public_user($user)]);
} catch (Throwable $e) {
  madd_json([
    'ok' => false,
    'error' => 'تعذّر التحقق من الجلسة',
    'detail' => $e->getMessage(),
  ], 500);
}
