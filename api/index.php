<?php
header('Content-Type: application/json; charset=utf-8');
echo json_encode([
  'ok' => true,
  'service' => 'MADD API',
  'endpoints' => [
    'POST /auth/login.php',
    'POST /auth/logout.php',
    'GET /auth/me.php',
    'POST /auth/register.php',
  ],
], JSON_UNESCAPED_UNICODE);
