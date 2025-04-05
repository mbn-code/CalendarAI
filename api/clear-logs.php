<?php
/**
 * API endpoint to clear debug logs
 */
session_start();
require_once '../config.php';

header('Content-Type: application/json');

// Only allow this in debug mode and for admins
if (!defined('DEBUG') || DEBUG !== true || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$log_file = __DIR__ . '/../logs/debug.log';

try {
    file_put_contents($log_file, '');
    echo json_encode(['success' => true, 'message' => 'Logs cleared successfully']);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>