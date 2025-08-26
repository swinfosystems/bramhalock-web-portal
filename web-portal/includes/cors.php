<?php
// Centralized CORS helper to avoid duplicate headers
function apply_cors() {
  // Remove any previously set headers in this PHP response
  @header_remove('Access-Control-Allow-Origin');
  @header_remove('Access-Control-Allow-Methods');
  @header_remove('Access-Control-Allow-Headers');
  @header_remove('Access-Control-Max-Age');

  $origin = isset($_SERVER['HTTP_ORIGIN']) ? $_SERVER['HTTP_ORIGIN'] : '*';
  // If no Origin header, fallback to *; otherwise reflect the origin to avoid multiple * values
  header('Access-Control-Allow-Origin: ' . $origin);
  header('Vary: Origin');
  header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
  header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
  header('Access-Control-Max-Age: 86400');
}
