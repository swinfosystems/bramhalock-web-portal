<?php
// Database configuration for Render PostgreSQL
$database_url = getenv('DATABASE_URL');

if ($database_url) {
    // Parse DATABASE_URL for Render/PostgreSQL providers
    $db = parse_url($database_url);
    $host = $db['host'];
    $port = $db['port'] ?? 5432;
    $dbname = ltrim($db['path'], '/');
    $username = $db['user'];
    $password = $db['pass'];
    // Extract sslmode from query if present (e.g., sslmode=require)
    $query = $db['query'] ?? '';
    parse_str($query, $qparams);
    $sslmode = $qparams['sslmode'] ?? null;
    if (!$sslmode && !in_array($host, ['localhost', '127.0.0.1'])) {
        // Default to require for managed providers like Neon
        $sslmode = 'require';
    }
    $dsn = "pgsql:host=$host;port=$port;dbname=$dbname" . ($sslmode ? ";sslmode=$sslmode" : "");
    
    try {
        $pdo = new PDO($dsn, $username, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Database connection failed: ' . $e->getMessage()]);
        exit;
    }
} else {
    // Fallback to environment variables
    $host = getenv('DB_HOST') ?: 'localhost';
    $port = getenv('DB_PORT') ?: '5432';
    $dbname = getenv('DB_NAME') ?: 'bramhalock';
    $username = getenv('DB_USER') ?: 'postgres';
    $password = getenv('DB_PASS') ?: '';
    $sslmode = getenv('DB_SSLMODE') ?: (in_array($host, ['localhost', '127.0.0.1']) ? null : 'require');
    $dsn = "pgsql:host=$host;port=$port;dbname=$dbname" . ($sslmode ? ";sslmode=$sslmode" : "");
    
    try {
        $pdo = new PDO($dsn, $username, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Database connection failed: ' . $e->getMessage()]);
        exit;
    }
}
?>
