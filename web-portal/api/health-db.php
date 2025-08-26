<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

$result = [ 'status' => 'unknown' ];

try {
  require_once '../config/database-render.php';
  $result['db'] = 'connected';

  // Check tables exist
  $tables = ['devices','commands','events'];
  $missing = [];
  foreach ($tables as $t) {
    try {
      $stmt = $pdo->query("SELECT 1 FROM {$t} LIMIT 1");
      $stmt->fetch();
    } catch (Throwable $te) {
      $missing[] = $t;
    }
  }
  $result['missing_tables'] = $missing;
  $result['status'] = empty($missing) ? 'ok' : 'needs_migration';
} catch (Throwable $e) {
  http_response_code(500);
  $result['status'] = 'db_error';
  $result['error'] = $e->getMessage();
}

echo json_encode($result);
