<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';
madd_cors();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  madd_json(['ok' => false, 'error' => 'Method not allowed'], 405);
}

$token = madd_bearer_token();
if ($token) {
  try {
    $pdo = madd_db();
    $stmt = $pdo->prepare('DELETE FROM auth_tokens WHERE token = ?');
    $stmt->execute([$token]);
  } catch (Throwable $e) {
    // تجاهل أخطاء قطع الاتصال عند الخروج
  }
}

madd_json(['ok' => true]);
