<?php
require_once '../../backend/db.php';

header('Content-Type: application/json');

try {
    $preset = $_POST['preset'] ?? 'default';
    
    // Load preset SQL based on selection
    switch ($preset) {
        case 'busy_week':
            $sql = file_get_contents(__DIR__ . '/presets/busy_week.sql');
            break;
        case 'conflicts':
            $sql = file_get_contents(__DIR__ . '/presets/conflicts.sql');
            break;
        case 'optimized':
            $sql = file_get_contents(__DIR__ . '/presets/optimized.sql');
            break;
        default:
            $sql = file_get_contents(__DIR__ . '/presets/default.sql');
            $preset = 'default';
    }
    
    // Start transaction
    $pdo->beginTransaction();
    
    // Execute the preset SQL
    $pdo->exec($sql);
    
    // Update the preset type for all optimized events
    $updateSql = "UPDATE calendar_events SET preset_source = ? WHERE is_ai_optimized = 1";
    $stmt = $pdo->prepare($updateSql);
    $stmt->execute([$preset]);
    
    $pdo->commit();
    
    echo json_encode(['success' => true, 'message' => "Loaded $preset preset successfully"]);
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}