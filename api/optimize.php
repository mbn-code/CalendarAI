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

    if (!$input || !isset($input['days']) || !is_array($input['days']) || !isset($input['preferences'])) {
        throw new Exception("Missing or invalid input data");
    }

    $selectedDays = $input['days'];
    $preferences = $input['preferences'];
    $userId = $_SESSION['user_id'];

    // Ensure selected days are unique and sorted
    $selectedDays = array_unique($selectedDays);
    sort($selectedDays);

    $placeholders = implode(',', array_fill(0, count($selectedDays), '?'));
    $query = "SELECT * FROM calendar_events WHERE user_id = ? AND DATE(start_date) IN ($placeholders) ORDER BY start_date ASC";
    $stmt = $conn->prepare($query);

    $params = array_merge([$userId], $selectedDays);
    $stmt->bind_param(str_repeat('s', count($params)), ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
    $events = $result->fetch_all(MYSQLI_ASSOC);

    if (count($events) === 0) {
        echo json_encode([
            'success' => true,
            'message' => 'No events to optimize for the selected days',
            'changes' => [],
            'analysis' => [
                'insights' => ['No events found for the selected days.'],
                'schedule_health' => [
                    'focus_time_utilization' => 100,
                    'break_compliance' => 100,
                    'conflict_score' => 0,
                    'balance_score' => 10
                ]
            ]
        ]);
        exit;
    }

    $changes = [];
    $insights = [];
    $totalEvents = count($events);
    $conflictCount = 0;
    $breakCompliance = 0;
    $focusTimeEvents = 0;

    foreach ($selectedDays as $day) {
        $currentTime = strtotime($day . ' 00:00:00');
        $endOfDay = strtotime($day . ' 23:59:59');
        $dayEvents = 0;
        $lastEventEnd = null;

        foreach ($events as $event) {
            $eventStart = strtotime($event['start_date']);
            $eventEnd = isset($event['end_date']) ? strtotime($event['end_date']) : $eventStart + 3600;

            // Skip events outside the current day
            if ($eventStart < $currentTime || $eventStart > $endOfDay) {
                continue;
            }
            
            $dayEvents++;

            // Check for conflicts
            if ($lastEventEnd !== null && $eventStart < $lastEventEnd) {
                $conflictCount++;
            }

            // Check break compliance
            if ($lastEventEnd !== null) {
                $breakDuration = ($eventStart - $lastEventEnd) / 60; // in minutes
                if ($breakDuration >= $preferences['breakDuration']) {
                    $breakCompliance++;
                }
            }

            if ($preferences['priority'] === 'deadlines') {
                // Prioritize events with deadlines
                if (isset($event['deadline']) && $event['deadline']) {
                    $currentTime = max($currentTime, $eventEnd);
                } else {
                    $newTime = date('Y-m-d H:i:s', $currentTime);
                    $reason = 'Rescheduled to prioritize deadlines';
                    $changes[] = [
                        'event_id' => $event['id'],
                        'new_time' => $newTime,
                        'reason' => $reason
                    ];

                    $updateQuery = "UPDATE calendar_events SET start_date = ?, is_ai_optimized = 1, ai_description = ? WHERE id = ?";
                    $updateStmt = $conn->prepare($updateQuery);
                    $updateStmt->bind_param("ssi", $newTime, $reason, $event['id']);
                    $updateStmt->execute();

                    $currentTime += 3600; // Assume 1-hour duration for rescheduled events
                }
            } elseif ($preferences['priority'] === 'balanced') {
                // Balanced mode: Distribute events evenly throughout the day
                $newTime = date('Y-m-d H:i:s', $currentTime);
                $reason = 'Rescheduled for balanced optimization';
                $changes[] = [
                    'event_id' => $event['id'],
                    'new_time' => $newTime,
                    'reason' => $reason
                ];

                $updateQuery = "UPDATE calendar_events SET start_date = ?, is_ai_optimized = 1, ai_description = ? WHERE id = ?";
                $updateStmt = $conn->prepare($updateQuery);
                $updateStmt->bind_param("ssi", $newTime, $reason, $event['id']);
                $updateStmt->execute();

                $currentTime += 3600; // Assume 1-hour duration for rescheduled events
            } elseif ($preferences['priority'] === 'flexible') {
                // Flexible mode: Reschedule events to maximize free time
                if ($eventStart > $currentTime) {
                    $newTime = date('Y-m-d H:i:s', $currentTime);
                    $reason = 'Rescheduled for flexible optimization';
                    $changes[] = [
                        'event_id' => $event['id'],
                        'new_time' => $newTime,
                        'reason' => $reason
                    ];

                    $updateQuery = "UPDATE calendar_events SET start_date = ?, is_ai_optimized = 1, ai_description = ? WHERE id = ?";
                    $updateStmt = $conn->prepare($updateQuery);
                    $updateStmt->bind_param("ssi", $newTime, $reason, $event['id']);
                    $updateStmt->execute();

                    $currentTime += 3600; // Assume 1-hour duration for rescheduled events
                }
            }

            $lastEventEnd = $eventEnd;
        }

        // Generate insights based on the day's analysis
        if ($dayEvents > 0) {
            $insights[] = "Found {$dayEvents} events on " . date('F j, Y', strtotime($day));
        }

        // Add new study sessions or breaks based on preferences
        while ($currentTime + $preferences['sessionLength'] * 60 <= $endOfDay) {
            $sessionStart = date('Y-m-d H:i:s', $currentTime);
            $sessionEnd = date('Y-m-d H:i:s', $currentTime + $preferences['sessionLength'] * 60);

            $insertQuery = "INSERT INTO calendar_events (user_id, title, start_date, end_date, category_id, is_ai_optimized, ai_description) VALUES (?, 'Study Session', ?, ?, 'study', 1, 'Added by AI optimization')";
            $insertStmt = $conn->prepare($insertQuery);
            $insertStmt->bind_param("iss", $userId, $sessionStart, $sessionEnd);
            $insertStmt->execute();

            $changes[] = [
                'event_id' => $conn->insert_id,
                'new_time' => $sessionStart,
                'reason' => 'Added a study session based on preferences'
            ];
            $focusTimeEvents++;

            $currentTime += $preferences['sessionLength'] * 60;

            // Add a break after each session
            if ($currentTime + $preferences['breakDuration'] * 60 <= $endOfDay) {
                $breakStart = date('Y-m-d H:i:s', $currentTime);
                $breakEnd = date('Y-m-d H:i:s', $currentTime + $preferences['breakDuration'] * 60);

                $insertQuery = "INSERT INTO calendar_events (user_id, title, start_date, end_date, category_id, is_ai_optimized, ai_description) VALUES (?, 'Break', ?, ?, 'break', 1, 'Added by AI optimization')";
                $insertStmt = $conn->prepare($insertQuery);
                $insertStmt->bind_param("iss", $userId, $breakStart, $breakEnd);
                $insertStmt->execute();

                $changes[] = [
                    'event_id' => $conn->insert_id,
                    'new_time' => $breakStart,
                    'reason' => 'Added a break after a study session'
                ];

                $currentTime += $preferences['breakDuration'] * 60;
            }
        }
    }

    // Calculate schedule health metrics
    $totalBreaks = max(1, $totalEvents - 1); // Maximum possible breaks
    $breakCompliancePercent = ($breakCompliance / $totalBreaks) * 100;
    $conflictScore = min(10, $conflictCount); // Scale from 0-10, lower is better
    $focusTimeUtilization = min(100, ($focusTimeEvents / max(1, $totalEvents)) * 100);
    $balanceScore = min(10, 10 - ($conflictScore / 2) + ($breakCompliancePercent / 20));

    if (count($changes) > 0) {
        $insights[] = "Optimized " . count($changes) . " events across selected days";
    }
    if ($conflictCount > 0) {
        $insights[] = "Resolved {$conflictCount} schedule conflicts";
    }
    if ($focusTimeEvents > 0) {
        $insights[] = "Added {$focusTimeEvents} focused study sessions";
    }

    echo json_encode([
        'success' => true,
        'message' => 'Optimization complete for selected days',
        'changes' => $changes,
        'analysis' => [
            'insights' => $insights,
            'schedule_health' => [
                'focus_time_utilization' => round($focusTimeUtilization),
                'break_compliance' => round($breakCompliancePercent),
                'conflict_score' => $conflictScore,
                'balance_score' => round($balanceScore, 1)
            ]
        ]
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
