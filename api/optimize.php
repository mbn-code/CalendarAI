<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../backend/db.php';
require_once __DIR__ . '/../config.php';
header('Content-Type: application/json');
session_start();

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'User not authenticated']);
    exit;
}

try {
    $rawInput = file_get_contents('php://input');
    error_log("Raw input: " . $rawInput); // Log raw input for debugging

    $input = json_decode($rawInput, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        error_log("JSON decode error: " . json_last_error_msg()); // Log JSON decoding errors
        throw new Exception("Invalid input data");
    }

    if (!$input || !isset($input['preferences'])) {
        throw new Exception("Missing preferences in input data");
    }

    $preferences = $input['preferences'];
    $userId = $_SESSION['user_id'];

    $query = "SELECT * FROM calendar_events WHERE user_id = ? AND start_date >= CURDATE() ORDER BY start_date ASC";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $events = $result->fetch_all(MYSQLI_ASSOC);

    if (count($events) === 0) {
        echo json_encode([
            'success' => true,
            'message' => 'No events to optimize',
            'changes' => []
        ]);
        exit;
    }

    $changes = [];
    $currentTime = strtotime('now');

    foreach ($events as $event) {
        $eventStart = strtotime($event['start_date']);
        $eventEnd = isset($event['end_date']) ? strtotime($event['end_date']) : $eventStart + 3600;

        // Skip breaks to preserve their original timing
        if (isset($event['category_id']) && $event['category_id'] === 'break') {
            $currentTime = max($currentTime, $eventEnd);
            continue;
        }

        if ($eventStart > $currentTime) {
            $gap = $eventStart - $currentTime;

            if ($gap > 3600) {
                $newTime = date('Y-m-d H:i:s', $currentTime + 1800);
                $reason = 'Optimized to utilize free time effectively';
                $changes[] = [
                    'event_id' => $event['id'],
                    'new_time' => $newTime,
                    'reason' => $reason
                ];

                $updateQuery = "UPDATE calendar_events SET start_date = ?, is_ai_optimized = 1, ai_description = ? WHERE id = ?";
                $updateStmt = $conn->prepare($updateQuery);
                $updateStmt->bind_param("ssi", $newTime, $reason, $event['id']);
                $updateStmt->execute();

                $currentTime += 1800;
            }
        }

        $currentTime = max($currentTime, $eventEnd);
    }

    echo json_encode([
        'success' => true,
        'message' => 'Optimization complete',
        'changes' => $changes
    ]);
} catch (Exception $e) {
    error_log("Exception: " . $e->getMessage()); // Log exception messages
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
