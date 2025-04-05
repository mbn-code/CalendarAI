<?php
require_once '../../backend/db.php';

header('Content-Type: application/json');

try {
    // Truncate relevant tables while preserving structure
    $tables = ['events', 'event_preferences', 'optimization_history'];
    
    foreach ($tables as $table) {
        $pdo->exec("TRUNCATE TABLE $table");
    }
    
    echo json_encode(['success' => true, 'message' => 'Calendar data reset successfully']);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}