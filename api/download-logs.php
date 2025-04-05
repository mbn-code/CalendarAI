<?php
/**
 * API endpoint to download debug logs
 */
session_start();
require_once '../config.php';

// Only allow this in debug mode and for admins
if (!defined('DEBUG') || DEBUG !== true || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    http_response_code(403);
    echo 'Unauthorized';
    exit;
}

$log_file = __DIR__ . '/../logs/debug.log';

if (file_exists($log_file)) {
    // Set headers for download
    header('Content-Description: File Transfer');
    header('Content-Type: text/plain');
    header('Content-Disposition: attachment; filename="calendar_ai_debug_' . date('Y-m-d_H-i-s') . '.log"');
    header('Expires: 0');
    header('Cache-Control: must-revalidate');
    header('Pragma: public');
    header('Content-Length: ' . filesize($log_file));
    
    // Clear output buffer
    ob_clean();
    flush();
    
    // Read and output file
    readfile($log_file);
    exit;
} else {
    // Return empty file if log doesn't exist
    header('Content-Description: File Transfer');
    header('Content-Type: text/plain');
    header('Content-Disposition: attachment; filename="calendar_ai_debug_' . date('Y-m-d_H-i-s') . '.log"');
    header('Expires: 0');
    header('Cache-Control: must-revalidate');
    header('Pragma: public');
    header('Content-Length: 0');
    
    exit;
}
?>