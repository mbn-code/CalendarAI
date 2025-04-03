<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../backend/db.php';
header('Content-Type: application/json');

try {
    debug_log('Starting to apply changes');
    $data = json_decode(file_get_contents('php://input'), true);
    if (!isset($data['changes']) || !is_array($data['changes'])) {
        debug_log('Invalid changes data received', $data);
        throw new Exception('Invalid changes data');
    }

    $changes = $data['changes'];
    $appliedCount = 0;
    $errors = [];

    debug_log('Initiating changes application', ['changes_count' => count($changes)]);

    // Start transaction
    $conn->begin_transaction();
    debug_log('Started database transaction');

    try {
        // Prepare statement for updating events
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
                debug_log('Missing required fields in change', $change);
                throw new Exception('Missing required fields in change data');
            }

            debug_log('Processing change', [
                'event_id' => $change['event_id'],
                'new_time' => $change['new_time']
            ]);

            $newStartTime = $change['new_time'];
            $duration = isset($change['duration']) ? (int)$change['duration'] * 60 : 3600; // Convert minutes to seconds, default 1 hour
            $newEndTime = date('Y-m-d H:i:s', strtotime($newStartTime) + $duration);

            $updateStmt->bind_param('ssi', 
                $newStartTime,
                $newEndTime,
                $change['event_id']
            );

            if (!$updateStmt->execute()) {
                debug_log('Failed to update event', [
                    'event_id' => $change['event_id'],
                    'error' => $updateStmt->error
                ]);
                throw new Exception("Failed to update event {$change['event_id']}: " . $updateStmt->error);
            }

            debug_log('Successfully applied change', [
                'event_id' => $change['event_id'],
                'new_start' => $newStartTime,
                'new_end' => $newEndTime
            ]);

            $appliedCount++;
        }

        // Log optimization action in assistant_chat
        $userId = $_SESSION['user_id'] ?? 1;
        $message = "Applied AI schedule optimization - {$appliedCount} changes";
        
        debug_log('Logging optimization action', [
            'user_id' => $userId,
            'changes_applied' => $appliedCount
        ]);
        
        $logStmt = $conn->prepare("
            INSERT INTO assistant_chat (user_id, message, is_user)
            VALUES (?, ?, FALSE)
        ");
        $logStmt->bind_param('is', $userId, $message);
        $logStmt->execute();

        // Commit transaction if all changes were successful
        $conn->commit();
        debug_log('Committed database transaction');

        debug_log('Successfully completed all changes', [
            'total_applied' => $appliedCount
        ]);

        echo json_encode([
            'success' => true,
            'message' => "Successfully applied {$appliedCount} changes",
            'changes_applied' => $appliedCount
        ]);

    } catch (Exception $e) {
        // Rollback transaction on error
        debug_log('Rolling back transaction due to error', [
            'error' => $e->getMessage()
        ]);
        $conn->rollback();
        throw $e;
    }

} catch (Exception $e) {
    debug_log('Error applying changes', [
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ]);
    error_log('Apply Changes Error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
