<?php
// Start the session to ensure session data is available
session_start();

// Simplify output buffering logic
ob_start();

// Turn off display errors so they don't corrupt JSON output
ini_set('display_errors', 0);
error_reporting(E_ERROR);

// Ensure no whitespace or BOM characters at the beginning of the file
ob_clean();

require_once '../backend/db.php';
require_once '../config.php'; // Include config for debug functions
header('Content-Type: application/json');

try {
    // Log the start of the optimization process
    error_log("[DEBUG] Starting optimization process");

    // Step 1: Validate Incoming Request Data
    $rawInput = file_get_contents('php://input');
    error_log("[DEBUG] Raw input: " . $rawInput);
    
    $data = json_decode($rawInput, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        error_log("[ERROR] JSON parsing error: " . json_last_error_msg());
        throw new Exception('Invalid JSON data: ' . json_last_error_msg());
    }
    
    if (!$data) {
        throw new Exception('Empty or invalid request data');
    }

    // Get preset parameters with validation
    $preset = isset($data['preset']) ? $data['preset'] : 'default';
    if (!in_array($preset, ['default', 'busy_week', 'conflicts', 'optimized'])) {
        $preset = 'default';
    }
    
    // Validate days array
    $days = $data['days'] ?? [];
    if (!is_array($days)) {
        error_log("[ERROR] Days parameter is not an array");
        throw new Exception('Days parameter must be an array');
    }

    // Make sure we have days to process
    if (empty($days)) {
        throw new Exception('No days selected for optimization');
    }

    // Log the received data
    error_log("[DEBUG] Received data: " . json_encode($data));

    // Load preset-specific optimization parameters
    $optimizationParams = [];
    switch ($preset) {
        case 'busy_week':
            $optimizationParams = [
                'min_break' => 15,
                'max_consecutive_meetings' => 3,
                'preferred_meeting_duration' => 45,
                'focus_block_duration' => 90,
                'optimal_day_start' => '09:00',
                'optimal_day_end' => '17:00'
            ];
            break;
        case 'conflicts':
            $optimizationParams = [
                'min_break' => 30,
                'max_consecutive_meetings' => 2,
                'conflict_resolution_priority' => 'high',
                'spacing_buffer' => 15,
                'optimal_day_start' => '09:00',
                'optimal_day_end' => '17:00'
            ];
            break;
        case 'optimized':
            $optimizationParams = [
                'min_break' => 30,
                'max_consecutive_meetings' => 2,
                'focus_block_duration' => 120,
                'break_frequency' => 3,
                'optimal_day_start' => '09:00',
                'optimal_day_end' => '17:00'
            ];
            break;
        default:
            $optimizationParams = [
                'min_break' => 15,
                'max_consecutive_meetings' => 3,
                'focus_block_duration' => 60,
                'break_frequency' => 4,
                'optimal_day_start' => '09:00',
                'optimal_day_end' => '17:00'
            ];
    }

    // Log the selected preset and parameters
    error_log("[DEBUG] Selected preset: $preset");
    error_log("[DEBUG] Optimization parameters: " . json_encode($optimizationParams));

    // Get events for the selected days
    $events = [];
    $userId = $_SESSION['user_id'] ?? 1; // Default to user 1 if not set
    
    // Step 3: Verify Database Connection and Queries
    foreach ($days as $day) {
        try {
            $stmt = $pdo->prepare("
                SELECT * FROM calendar_events 
                WHERE DATE(start_date) = ? 
                AND user_id = ? 
                ORDER BY start_date ASC
            ");
            $stmt->execute([$day, $userId]);
            $dayEvents = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if ($dayEvents) {
                error_log("[DEBUG] Found " . count($dayEvents) . " events for day: $day");
            } else {
                error_log("[DEBUG] No events found for day: $day");
            }
            
            $events = array_merge($events, $dayEvents);
        } catch (PDOException $e) {
            // Log the error but continue
            error_log("[ERROR] Database error in optimize.php: " . $e->getMessage());
        }
    }

    // Log the retrieved events
    error_log("[DEBUG] Retrieved events count: " . count($events));
    if (count($events) > 0) {
        error_log("[DEBUG] First event sample: " . json_encode($events[0]));
    }

    // Apply optimization based on preset
    $changes = [];
    $insights = [];
    $schedule_health = [
        'focus_time_utilization' => 70,
        'break_compliance' => 80,
        'conflict_score' => 2,
        'balance_score' => 75
    ];

    // Group events by day for processing
    $eventsByDay = [];
    foreach ($events as $event) {
        // Step 4: Validate Date Fields
        if (empty($event['start_date'])) {
            error_log("[ERROR] Event is missing start_date field: " . json_encode($event));
            continue;
        }
        
        $date = date('Y-m-d', strtotime($event['start_date']));
        if ($date === false) {
            error_log("[ERROR] Invalid date format for event ID " . $event['id']);
            continue;
        }
        
        $eventsByDay[$date][] = $event;
    }

    // Process each day
    foreach ($eventsByDay as $date => $dayEvents) {
        $dayChanges = optimizeDaySchedule($dayEvents, $optimizationParams, $preset);
        $changes = array_merge($changes, $dayChanges);
    }

    // Log the suggested changes
    error_log("[DEBUG] Suggested changes: " . json_encode($changes));

    // Calculate schedule health metrics
    $schedule_health = calculateScheduleHealth($events, $changes, $optimizationParams);

    // Generate insights based on changes and health metrics
    $insights = generateInsights($changes, $schedule_health, $preset);

    // Create response data structure
    $responseData = [
        'success' => true,
        'changes' => $changes,
        'analysis' => [
            'schedule_health' => $schedule_health,
            'insights' => $insights
        ],
        'preset_used' => $preset
    ];

    // Step 5: Test with dummy data if needed
    // For debugging: $responseData = ['success' => true, 'message' => 'Test output'];

    // Use JSON_THROW_ON_ERROR flag to catch JSON encoding errors
    $jsonResponse = json_encode($responseData, JSON_THROW_ON_ERROR);
    
    // Clear any previous output
    ob_clean();
    
    // Output the JSON
    echo $jsonResponse;

} catch (Exception $e) {
    // Log the error for debugging
    error_log("[ERROR] Optimization error: " . $e->getMessage() . " - " . $e->getTraceAsString());
    
    http_response_code(500);
    
    // Create a simplified error response
    $errorResponse = [
        'success' => false,
        'error' => "Error during optimization: " . $e->getMessage()
    ];
    
    // Clear any previous output
    ob_clean();
    
    try {
        // Use JSON_THROW_ON_ERROR flag to catch JSON encoding errors
        echo json_encode($errorResponse, JSON_THROW_ON_ERROR);
    } catch (Exception $jsonEx) {
        // If JSON encoding fails, return a simple string response
        error_log("[ERROR] JSON encoding error: " . $jsonEx->getMessage());
        echo '{"success":false,"error":"Internal server error - Failed to generate response"}';
    }
}

// Ensure all output is sent and buffer is flushed
ob_end_flush();

/**
 * Optimizes a day's schedule based on given parameters and preset
 *
 * @param array $events List of events for the day
 * @param array $params Optimization parameters
 * @param string $preset Selected optimization preset
 * @return array List of suggested changes
 */
function optimizeDaySchedule($events, $params, $preset) {
    $changes = [];
    $workday_start = strtotime($params['optimal_day_start'] ?? '09:00');
    $workday_end = strtotime($params['optimal_day_end'] ?? '17:00');
    $last_end_time = $workday_start;
    
    foreach ($events as $event) {
        // Step 4: Validate date fields
        if (empty($event['start_date'])) {
            error_log("[ERROR] Invalid start_date for event ID " . ($event['id'] ?? 'unknown'));
            continue;
        }
        
        $start = strtotime($event['start_date']);
        if ($start === false) {
            error_log("[ERROR] Invalid start_date format for event ID " . ($event['id'] ?? 'unknown'));
            continue;
        }
        
        $end = !empty($event['end_date']) && strtotime($event['end_date']) !== false 
            ? strtotime($event['end_date']) 
            : $start + 3600; // Default 1 hour
        
        // Apply preset-specific optimizations
        switch ($preset) {
            case 'busy_week':
                // Optimize for high efficiency
                if ($start - $last_end_time > 30 * 60) { // 30-minute gap
                    $new_start = date('Y-m-d H:i:s', $last_end_time + 15 * 60);
                    $duration = $end - $start;
                    $changes[] = [
                        'event_id' => $event['id'],
                        'new_time' => $new_start,
                        'duration' => round($duration / 60),
                        'reason' => 'Compacted schedule for higher efficiency'
                    ];
                }
                break;

            case 'conflicts':
                // Focus on resolving overlaps
                if ($start < $last_end_time) { // Overlap detected
                    $new_start = date('Y-m-d H:i:s', $last_end_time + 15 * 60);
                    $duration = $end - $start;
                    $changes[] = [
                        'event_id' => $event['id'],
                        'new_time' => $new_start,
                        'duration' => round($duration / 60),
                        'reason' => 'Resolved scheduling conflict'
                    ];
                }
                break;

            case 'optimized':
                // Apply ideal spacing and timing
                $optimal_start = $last_end_time + 30 * 60; // 30-minute spacing
                if ($start != $optimal_start) {
                    $new_start = date('Y-m-d H:i:s', $optimal_start);
                    $duration = $end - $start;
                    $changes[] = [
                        'event_id' => $event['id'],
                        'new_time' => $new_start,
                        'duration' => round($duration / 60),
                        'reason' => 'Optimized for ideal spacing and energy levels'
                    ];
                }
                break;

            default:
                // Basic optimization
                if ($start < $last_end_time) {
                    $new_start = date('Y-m-d H:i:s', $last_end_time + 15 * 60);
                    $duration = $end - $start;
                    $changes[] = [
                        'event_id' => $event['id'],
                        'new_time' => $new_start,
                        'duration' => round($duration / 60),
                        'reason' => 'Basic schedule optimization'
                    ];
                }
        }
        
        $last_end_time = $end;
    }
    
    return $changes;
}

/**
 * Calculate schedule health metrics
 *
 * @param array $events List of all events
 * @param array $changes List of suggested changes
 * @param array $params Optimization parameters
 * @return array Health metrics
 */
function calculateScheduleHealth($events, $changes, $params) {
    // Calculate various health metrics
    $total_events = count($events);
    if ($total_events === 0) {
        return [
            'focus_time_utilization' => 100,
            'break_compliance' => 100,
            'conflict_score' => 0,
            'balance_score' => 100
        ];
    }
    
    $optimized_events = count($changes);
    $conflicts_resolved = 0;
    $proper_breaks = 0;
    
    foreach ($changes as $change) {
        if (strpos($change['reason'], 'conflict') !== false) {
            $conflicts_resolved++;
        }
        if (strpos($change['reason'], 'break') !== false) {
            $proper_breaks++;
        }
    }
    
    return [
        'focus_time_utilization' => min(100, round(($total_events - $conflicts_resolved) / $total_events * 100)),
        'break_compliance' => min(100, round($proper_breaks / max(1, $total_events) * 100)),
        'conflict_score' => max(0, $conflicts_resolved),
        'balance_score' => min(100, round($optimized_events / max(1, $total_events) * 100))
    ];
}

/**
 * Generate insights based on schedule changes
 *
 * @param array $changes List of suggested changes
 * @param array $health Health metrics
 * @param string $preset Selected optimization preset
 * @return array List of insights
 */
function generateInsights($changes, $health, $preset) {
    $insights = [];
    
    // Add preset-specific insights
    switch ($preset) {
        case 'busy_week':
            $insights[] = "Optimized schedule for maximum efficiency with minimal gaps";
            $insights[] = "Maintained essential breaks while maximizing productive time";
            break;
            
        case 'conflicts':
            if ($health['conflict_score'] > 0) {
                $insights[] = "Resolved {$health['conflict_score']} scheduling conflicts";
            }
            $insights[] = "Improved schedule flow by eliminating overlapping events";
            break;
            
        case 'optimized':
            $insights[] = "Applied best practices for optimal work-life balance";
            $insights[] = "Structured day with ideal break intervals and focus periods";
            break;
            
        default:
            $insights[] = "Basic schedule optimization complete";
    }
    
    // Add health-based insights
    if ($health['focus_time_utilization'] > 80) {
        $insights[] = "Excellent focus time utilization at {$health['focus_time_utilization']}%";
    }
    if ($health['break_compliance'] < 60) {
        $insights[] = "Consider adding more breaks to improve productivity";
    }
    
    return $insights;
}
?>
