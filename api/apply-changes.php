<?php
require_once __DIR__ . '/../backend/db.php';
header('Content-Type: application/json');

try {
    $data = json_decode(file_get_contents('php://input'), true);
    if (!isset($data['changes']) || !is_array($data['changes'])) {
        throw new Exception('Invalid changes data');
    }

    $changes = $data['changes'];
    $appliedCount = 0;
    $errors = [];

    // Start transaction
    $conn->begin_transaction();

    try {
        // Prepare statements for updating events
        $updateStmt = $conn->prepare("
            UPDATE calendar_events 
            SET start_date = ?,
                end_date = ?,
                is_ai_optimized = TRUE,
                is_human_ai_altered = TRUE
            WHERE id = ?
        ");

        foreach ($changes as $change) {
            if (!isset($change['event_id'], $change['new_time'])) {
                throw new Exception('Missing required fields in change data');
            }

            $newStartTime = $change['new_time'];
            $duration = isset($change['duration']) ? (int)$change['duration'] : 60; // Default 1 hour if not specified
            $newEndTime = date('Y-m-d H:i:s', strtotime($newStartTime) + ($duration * 60));

            $updateStmt->bind_param('ssi', 
                $newStartTime,
                $newEndTime,
                $change['event_id']
            );

            if (!$updateStmt->execute()) {
                throw new Exception("Failed to update event {$change['event_id']}: " . $updateStmt->error);
            }

            $appliedCount++;
        }

        // Commit transaction if all changes were successful
        $conn->commit();

        // Log optimization action
        $logStmt = $conn->prepare("
            INSERT INTO assistant_chat (user_id, message, is_user, action_type, status)
            VALUES (?, 'Applied AI schedule optimization', 0, 'optimize', 'completed')
        ");
        
        $userId = $_SESSION['user_id'] ?? 1;
        $logStmt->bind_param('i', $userId);
        $logStmt->execute();

        echo json_encode([
            'success' => true,
            'message' => "Successfully applied $appliedCount changes",
            'changes_count' => $appliedCount
        ]);

    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollback();
        throw $e;
    }

} catch (Exception $e) {
    error_log('Apply Changes Error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
