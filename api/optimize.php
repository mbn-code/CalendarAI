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
require_once '../backend/ScheduleOptimizer.php';
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

    // validere preset parametre
    $preset = isset($data['preset']) ? $data['preset'] : 'default';
    if (!in_array($preset, ['default', 'busy_week', 'conflicts', 'optimized'])) {
        $preset = 'default';
    }
    
    // tjekker auto apply med isset
    $autoApply = isset($data['auto_apply']) ? (bool)$data['auto_apply'] : false;
    
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

    // Get events for the selected days
    $events = [];
    $userId = $_SESSION['user_id'] ?? 1; // Default to user 1 if not set
    
    // Fetch user-specific optimization preferences
    $userPrefStmt = $pdo->prepare("SELECT * FROM user_preferences WHERE user_id = ?");
    $userPrefStmt->execute([$userId]);
    $userPrefs = $userPrefStmt->fetch(PDO::FETCH_ASSOC);

    // Step 2: Use a more optimized query to get all events for all days at once
    try {
        // Using an IN clause for better performance with multiple days
        $placeholders = implode(',', array_fill(0, count($days), '?'));
        $params = array_merge($days, [$userId]); // All days + userId
        
        $stmt = $pdo->prepare("
            SELECT * FROM calendar_events 
            WHERE DATE(start_date) IN ($placeholders)
            AND user_id = ? 
            ORDER BY start_date ASC
        ");
        $stmt->execute($params);
        $events = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        error_log("[DEBUG] Retrieved events count: " . count($events));
        if (count($events) > 0) {
            error_log("[DEBUG] First event sample: " . json_encode($events[0]));
        }
    } catch (PDOException $e) {
        error_log("[ERROR] Database error in optimize.php: " . $e->getMessage());
        throw new Exception("Error retrieving events: " . $e->getMessage());
    }

    // Step 3: Create and configure the optimizer
    // Initialize our new optimizer with user preferences
    $optimizer = new ScheduleOptimizerV2($userPrefs ?: [], $preset);
    
    // Add all events to the optimizer
    $optimizer->addEvents($events);
    
    // Run the optimization process
    $optimizer->optimize($preset);
    
    // Get the resulting changes
    $changes = $optimizer->getChanges();
    
    // Calculate schedule health metrics using the optimizer
    $schedule_health = $optimizer->calculateScheduleHealth();
    
    // Generate insights based on changes and health metrics
    $insights = $optimizer->generateInsights($preset);
    
    // Log the suggested changes
    error_log("[DEBUG] Suggested changes: " . json_encode($changes));    // Apply changes directly to the database if auto_apply is enabled
    $appliedChanges = 0;
    if ($autoApply && !empty($changes)) {
        $appliedChanges = applyChangesToDatabase($changes, $preset);
        error_log("[DEBUG] Auto-applied $appliedChanges changes to the database");
    }

    // Get metrics about the optimization process
    $metrics = $optimizer->getMetrics();

    // Create response data structure with enhanced metrics
    $responseData = [
        'success' => true,
        'changes' => $changes,
        'analysis' => [
            'schedule_health' => $schedule_health,
            'insights' => $insights,
            'metrics' => [
                'conflicts_resolved' => $metrics['conflicts_resolved'] ?? 0,
                'breaks_added' => $metrics['breaks_added'] ?? 0,
                'events_moved' => $metrics['events_moved'] ?? 0,
                'events_split' => $metrics['events_split'] ?? 0
            ]
        ],
        'preset_used' => $preset,
        'changes_applied' => $autoApply ? $appliedChanges : 0
    ];

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
 * Apply optimization changes directly to the database
 * 
 * @param array $changes List of changes to apply
 * @param string $preset The optimization preset used
 * @return int Number of changes successfully applied
 */
function applyChangesToDatabase($changes, $preset) {
    global $pdo;
    $appliedCount = 0;
    $changeLog = [];
    
    try {
        // Start a transaction for database consistency
        $pdo->beginTransaction();
        
        // Batch similar changes for more efficient processing
        $updates = [];
        $creations = [];
        $deletions = [];
        
        foreach ($changes as $change) {
            // Skip changes without necessary data
            if (!isset($change['new_time']) && !isset($change['action'])) {
                error_log("[ERROR] Missing required data in change: " . json_encode($change));
                continue;
            }
            
            // Group by operation type
            if (isset($change['action']) && $change['action'] === 'create') {
                $creations[] = $change;
            } elseif (isset($change['action']) && $change['action'] === 'delete') {
                $deletions[] = $change;
            } elseif (isset($change['event_id']) && $change['event_id'] !== 'new_break' && $change['event_id'] !== 'new_split_event') {
                $updates[] = $change;
            }
        }
        
        // Process updates - prepare batch update if multiple similar changes
        if (!empty($updates)) {
            $updateStmt = $pdo->prepare("
                UPDATE calendar_events 
                SET start_date = :start_date,
                    end_date = :end_date,
                    is_ai_optimized = TRUE,
                    is_human_ai_altered = FALSE,
                    preset_source = :preset,
                    ai_description = :reason
                WHERE id = :id
            ");
            
            foreach ($updates as $change) {
                $newStartTime = $change['new_time'];
                $duration = isset($change['duration']) ? (int)$change['duration'] * 60 : 3600; // Default 1 hour in seconds
                $newEndTime = date('Y-m-d H:i:s', strtotime($newStartTime) + $duration);
                $reason = $change['reason'] ?? 'Schedule optimized';
                
                $updateStmt->bindParam(':start_date', $newStartTime);
                $updateStmt->bindParam(':end_date', $newEndTime);
                $updateStmt->bindParam(':preset', $preset);
                $updateStmt->bindParam(':reason', $reason);
                $updateStmt->bindParam(':id', $change['event_id']);
                
                try {
                    if ($updateStmt->execute()) {
                        $appliedCount++;
                        $changeLog[] = [
                            'type' => 'update',
                            'event_id' => $change['event_id'],
                            'new_time' => $newStartTime,
                            'status' => 'success'
                        ];
                        error_log("[DEBUG] Successfully updated event ID {$change['event_id']} to $newStartTime");
                    } else {
                        $changeLog[] = [
                            'type' => 'update',
                            'event_id' => $change['event_id'],
                            'status' => 'failed',
                            'error' => json_encode($updateStmt->errorInfo())
                        ];
                        error_log("[ERROR] Failed to update event ID {$change['event_id']}: " . json_encode($updateStmt->errorInfo()));
                    }
                } catch (PDOException $e) {
                    // Log individual errors but continue with other changes
                    $changeLog[] = [
                        'type' => 'update',
                        'event_id' => $change['event_id'],
                        'status' => 'failed',
                        'error' => $e->getMessage()
                    ];
                    error_log("[ERROR] Exception updating event ID {$change['event_id']}: " . $e->getMessage());
                }
            }
        }
        
        // Process creations (breaks and split events)
        if (!empty($creations)) {
            $createStmt = $pdo->prepare("
                INSERT INTO calendar_events 
                (title, description, start_date, end_date, user_id, is_ai_optimized, is_human_ai_altered, preset_source, is_break, category)
                VALUES 
                (:title, :description, :start_date, :end_date, :user_id, TRUE, FALSE, :preset, :is_break, :category)
            ");
            
            $userId = $_SESSION['user_id'] ?? 1; // Default to user 1 if not set
            
            foreach ($creations as $change) {
                $newStartTime = $change['new_time'];
                $duration = isset($change['duration']) ? (int)$change['duration'] * 60 : 900; // Default 15 min for breaks
                $newEndTime = date('Y-m-d H:i:s', strtotime($newStartTime) + $duration);
                $title = $change['title'] ?? 'Untitled Event';
                
                // Determine if this is a break or a regular event
                $isBreak = ($change['event_id'] === 'new_break');
                $category = $isBreak ? 'Break' : 'Study';
                $description = $change['reason'] ?? ($isBreak ? 'Auto-generated break' : 'Auto-generated split event');
                
                try {
                    $createStmt->bindParam(':title', $title);
                    $createStmt->bindParam(':description', $description);
                    $createStmt->bindParam(':start_date', $newStartTime);
                    $createStmt->bindParam(':end_date', $newEndTime);
                    $createStmt->bindParam(':user_id', $userId);
                    $createStmt->bindParam(':preset', $preset);
                    $createStmt->bindParam(':is_break', $isBreak, PDO::PARAM_BOOL);
                    $createStmt->bindParam(':category', $category);
                    
                    if ($createStmt->execute()) {
                        $appliedCount++;
                        $changeLog[] = [
                            'type' => 'create',
                            'title' => $title,
                            'new_time' => $newStartTime,
                            'status' => 'success'
                        ];
                        error_log("[DEBUG] Successfully created new event '$title' at $newStartTime");
                    } else {
                        $changeLog[] = [
                            'type' => 'create',
                            'title' => $title,
                            'status' => 'failed',
                            'error' => json_encode($createStmt->errorInfo())
                        ];
                        error_log("[ERROR] Failed to create new event: " . json_encode($createStmt->errorInfo()));
                    }
                } catch (PDOException $e) {
                    $changeLog[] = [
                        'type' => 'create',
                        'title' => $title,
                        'status' => 'failed',
                        'error' => $e->getMessage()
                    ];
                    error_log("[ERROR] Exception creating event '$title': " . $e->getMessage());
                }
            }
        }
        
        // Process deletions - if applicable
        if (!empty($deletions)) {
            $deleteIDs = [];
            foreach ($deletions as $change) {
                if (!isset($change['event_id']) || $change['event_id'] === 'new_break' || $change['event_id'] === 'new_split_event') {
                    continue; // Skip if not a valid event ID
                }
                $deleteIDs[] = $change['event_id'];
            }
            
            if (!empty($deleteIDs)) {
                // More efficient to delete multiple events in one query if possible
                $placeholders = implode(',', array_fill(0, count($deleteIDs), '?'));
                $deleteStmt = $pdo->prepare("DELETE FROM calendar_events WHERE id IN ($placeholders)");
                
                try {
                    if ($deleteStmt->execute($deleteIDs)) {
                        $deleteCount = $deleteStmt->rowCount();
                        $appliedCount += $deleteCount;
                        $changeLog[] = [
                            'type' => 'delete',
                            'event_ids' => $deleteIDs,
                            'count' => $deleteCount,
                            'status' => 'success'
                        ];
                        error_log("[DEBUG] Successfully deleted $deleteCount events");
                    } else {
                        $changeLog[] = [
                            'type' => 'delete',
                            'event_ids' => $deleteIDs,
                            'status' => 'failed',
                            'error' => json_encode($deleteStmt->errorInfo())
                        ];
                        error_log("[ERROR] Failed to delete events: " . json_encode($deleteStmt->errorInfo()));
                    }
                } catch (PDOException $e) {
                    $changeLog[] = [
                        'type' => 'delete',
                        'event_ids' => $deleteIDs,
                        'status' => 'failed',
                        'error' => $e->getMessage()
                    ];
                    error_log("[ERROR] Exception deleting events: " . $e->getMessage());
                }
            }
        }
        
        // Generate summary statistics for chat log
        $breakCount = count(array_filter($changes, function($change) {
            return isset($change['event_id']) && $change['event_id'] === 'new_break';
        }));
        
        $splitCount = count(array_filter($changes, function($change) {
            return isset($change['event_id']) && $change['event_id'] === 'new_split_event';
        }));
        
        $moveCount = count(array_filter($changes, function($change) {
            return isset($change['event_id']) && 
                   $change['event_id'] !== 'new_break' && 
                   $change['event_id'] !== 'new_split_event' &&
                   (!isset($change['action']) || $change['action'] !== 'delete');
        }));
        
        $deleteCount = count(array_filter($changes, function($change) {
            return isset($change['action']) && $change['action'] === 'delete';
        }));
        
        // Log to chat history
        $userId = $_SESSION['user_id'] ?? 1;
        $message = "AI optimization applied: $moveCount events moved";
        if ($breakCount > 0) $message .= ", $breakCount breaks added";
        if ($splitCount > 0) $message .= ", $splitCount events split across days";
        if ($deleteCount > 0) $message .= ", $deleteCount events removed";
        
        $chatStmt = $pdo->prepare("
            INSERT INTO assistant_chat (user_id, message, is_user, created_at)
            VALUES (:user_id, :message, 0, NOW())
        ");
        
        $chatStmt->bindParam(':user_id', $userId);
        $chatStmt->bindParam(':message', $message);
        $chatStmt->execute();
        
        // Write detailed change log to database for auditing/history
        $logJson = json_encode($changeLog);
        $logStmt = $pdo->prepare("
            INSERT INTO system_prompts (user_id, prompt_text, is_active)
            VALUES (:user_id, :log_data, 0)
        ");
        $logStmt->bindParam(':user_id', $userId);
        $logStmt->bindParam(':log_data', $logJson);
        $logStmt->execute();
        
        // Commit all changes at once
        $pdo->commit();
        
        return $appliedCount;
        
    } catch (PDOException $e) {
        // Roll back the transaction on error
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log("[ERROR] Database error while applying changes: " . $e->getMessage());
        return 0;
    }
}

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
    $breaks_to_add = [];
    $events_to_move_days = [];
    $workday_start = strtotime($params['optimal_day_start'] ?? '09:00');
    $workday_end = strtotime($params['optimal_day_end'] ?? '17:00');
    
    // Extract user preferences
    $focus_block_duration = ($params['focus_block_duration'] ?? 60) * 60; // In seconds
    $min_break = ($params['min_break'] ?? 15) * 60; // Convert to seconds
    $break_frequency = $params['break_frequency'] ?? 3; // Events before a break
    
    // Step 1: Validate all events and collect valid ones
    $validEvents = [];
    foreach ($events as $event) {
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
        
        // Extract the original date (YYYY-MM-DD) to preserve it
        $originalDate = date('Y-m-d', $start);
        
        // Determine event type (study session vs other)
        $isStudy = (stripos($event['title'] ?? '', 'study') !== false) || 
                   (stripos($event['category'] ?? '', 'study') !== false) ||
                   (stripos($event['description'] ?? '', 'study') !== false);
            
        // Add to valid events with parsed timestamps
        $validEvents[] = [
            'event' => $event,
            'start' => $start,
            'end' => $end,
            'duration' => $end - $start,
            'originalDate' => $originalDate,
            'isStudy' => $isStudy
        ];
    }
    
    // Step 2: Sort events by start time to ensure proper sequence
    usort($validEvents, function($a, $b) {
        return $a['start'] - $b['start'];
    });
    
    // Step 3: Process events in sequence with proper spacing
    $last_end_time = $workday_start;
    $currentDate = null;
    $consecutive_events = 0; // Track consecutive events for break insertion
    $daily_load = []; // Track total hours by day for potential day shifting
    
    foreach ($validEvents as $index => $eventData) {
        $event = $eventData['event'];
        $start = $eventData['start'];
        $end = $eventData['end'];
        $duration = $eventData['duration'];
        $originalDate = $eventData['originalDate'];
        $isStudy = $eventData['isStudy'];
        
        // Initialize daily load tracker if needed
        if (!isset($daily_load[$originalDate])) {
            $daily_load[$originalDate] = 0;
        }
        
        // Add this event's duration to the daily load
        $daily_load[$originalDate] += $duration / 3600; // Convert to hours
        
        // Reset last_end_time if we're on a new date
        if ($currentDate !== $originalDate) {
            $currentDate = $originalDate;
            $last_end_time = strtotime($originalDate . ' ' . date('H:i:s', $workday_start));
            $consecutive_events = 0;
        }
        
        $needs_rescheduling = false;
        $new_start = null;
        $reason = '';
        $adjust_duration = false;
        $split_event = false;
        $move_to_another_day = false;
        $new_duration = $duration;
        
        // Check if this is a study session that needs to be adjusted based on preference
        if ($isStudy && $duration > $focus_block_duration + 10*60) { // Allow 10 min buffer
            // Study session is longer than preferred focus block duration
            $adjust_duration = true;
            $new_duration = $focus_block_duration;
            $reason = 'Split long study session into shorter focused blocks';
            
            // If study session is very long, consider moving part to another day
            if ($duration > 2 * $focus_block_duration) {
                $split_event = true;
            }
        }
        
        // Count consecutive events for break insertion
        $consecutive_events++;
        
        // Apply preset-specific optimizations
        switch ($preset) {
            case 'busy_week':
                // Optimize for high efficiency
                if ($start - $last_end_time > 30 * 60) { // 30-minute gap
                    $needs_rescheduling = true;
                    $new_start = $last_end_time + 15 * 60;
                    $reason = 'Compacted schedule for higher efficiency';
                }
                
                // If we've scheduled too many events in one day, try to move some
                if ($daily_load[$originalDate] > 9 && $isStudy) { // More than 9 hours in a day
                    $move_to_another_day = true;
                    $reason = 'Moved to balance daily workload';
                }
                break;

            case 'conflicts':
                // Focus on resolving overlaps
                if ($start < $last_end_time) { // Overlap detected
                    $needs_rescheduling = true;
                    $new_start = $last_end_time + 15 * 60;
                    $reason = 'Resolved scheduling conflict';
                }
                break;

            case 'optimized':
                // Apply ideal spacing and timing
                $optimal_start = $last_end_time + 15 * 60; // 15-minute spacing
                if (abs($start - $optimal_start) > 5 * 60) { // If more than 5 minutes off from optimal
                    $needs_rescheduling = true;
                    $new_start = $optimal_start;
                    $reason = 'Optimized for ideal spacing and energy levels';
                }
                
                // Insert breaks after multiple consecutive events
                if ($consecutive_events >= $break_frequency) {
                    // Add a break after this event
                    $break_start = $end + 5*60; // 5 min after this event
                    $break_end = $break_start + $min_break;
                    
                    // Check if break fits within workday
                    $dayEndTime = strtotime($originalDate . ' ' . date('H:i:s', $workday_end));
                    if ($break_end <= $dayEndTime) {
                        $breaks_to_add[] = [
                            'title' => 'Break',
                            'start_date' => date('Y-m-d H:i:s', $break_start),
                            'end_date' => date('Y-m-d H:i:s', $break_end),
                            'originalDate' => $originalDate,
                            'reason' => 'Added scheduled break to improve productivity'
                        ];
                        
                        // Reset consecutive events counter
                        $consecutive_events = 0;
                    }
                }
                break;

            default:
                // Basic optimization
                if ($start < $last_end_time) { // Overlap detected
                    $needs_rescheduling = true;
                    $new_start = $last_end_time + 15 * 60;
                    $reason = 'Basic schedule optimization';
                }
        }
        
        // Handle event that needs to move to another day
        if ($move_to_another_day && $isStudy) {
            // Find a day with less load
            $tomorrow = date('Y-m-d', strtotime($originalDate . ' +1 day'));
            $dayAfter = date('Y-m-d', strtotime($originalDate . ' +2 days'));
            
            // Initialize load counters if needed
            if (!isset($daily_load[$tomorrow])) $daily_load[$tomorrow] = 0;
            if (!isset($daily_load[$dayAfter])) $daily_load[$dayAfter] = 0;
            
            // Choose the day with less load
            $target_date = ($daily_load[$tomorrow] <= $daily_load[$dayAfter]) ? $tomorrow : $dayAfter;
            
            // Create a change to move this event to another day
            $new_start_time = date('Y-m-d', strtotime($target_date)) . ' ' . date('H:i:s', $workday_start);
            $changes[] = [
                'event_id' => $event['id'],
                'new_time' => $new_start_time,
                'duration' => round($duration / 60),
                'reason' => "Moved to $target_date to better balance workload"
            ];
            
            // Update the daily load
            $daily_load[$target_date] += $duration / 3600;
            $daily_load[$originalDate] -= $duration / 3600;
            
            // Skip further processing of this event
            continue;
        }
        
        // Apply duration adjustment if needed
        if ($adjust_duration) {
            $needs_rescheduling = true;
            if (!$new_start) {
                $new_start = $start; // Keep original start time if not already changed
            }
            
            // If we need to split this into multiple events
            if ($split_event) {
                // First part stays here
                $changes[] = [
                    'event_id' => $event['id'],
                    'new_time' => date('Y-m-d H:i:s', $new_start),
                    'duration' => round($new_duration / 60),
                    'reason' => $reason
                ];
                
                // Second part moves to tomorrow
                $tomorrow = date('Y-m-d', strtotime($originalDate . ' +1 day'));
                $tomorrow_start = $tomorrow . ' ' . date('H:i:s', $workday_start);
                
                // Create a new event for the second part
                $events_to_move_days[] = [
                    'title' => $event['title'] . ' (continued)',
                    'start_date' => $tomorrow_start,
                    'end_date' => date('Y-m-d H:i:s', strtotime($tomorrow_start) + ($duration - $new_duration)),
                    'parent_event_id' => $event['id'],
                    'reason' => 'Split long study session across days'
                ];
                
                // Update tracking variables
                $last_end_time = $new_start + $new_duration;
                
                // Skip to next event
                continue;
            }
        }
        
        // Apply standard rescheduling if needed
        if ($needs_rescheduling) {
            // Calculate end of workday for the current date
            $dayEndTime = strtotime($originalDate . ' ' . date('H:i:s', $workday_end));
            
            // Ensure the new end time doesn't exceed workday_end
            if ($new_start + $new_duration > $dayEndTime) {
                // If it would exceed, try to fit it by reducing duration if over 30 minutes
                if ($new_duration > 30 * 60) {
                    $adjusted_duration = min($new_duration, ($dayEndTime - $new_start));
                    $changes[] = [
                        'event_id' => $event['id'],
                        'new_time' => date('Y-m-d H:i:s', $new_start),
                        'duration' => round($adjusted_duration / 60),
                        'reason' => $reason . ' (with adjusted duration)'
                    ];
                    $last_end_time = $new_start + $adjusted_duration;
                } else {
                    // Move to next day if it's too small to reduce
                    $tomorrow = date('Y-m-d', strtotime($originalDate . ' +1 day'));
                    $tomorrow_start = $tomorrow . ' ' . date('H:i:s', $workday_start);
                    
                    $changes[] = [
                        'event_id' => $event['id'],
                        'new_time' => $tomorrow_start,
                        'duration' => round($new_duration / 60),
                        'reason' => 'Moved to next day to fit workday boundaries'
                    ];
                    
                    // Initialize next day's load if needed
                    if (!isset($daily_load[$tomorrow])) $daily_load[$tomorrow] = 0;
                    $daily_load[$tomorrow] += $new_duration / 3600;
                }
            } else {
                $changes[] = [
                    'event_id' => $event['id'],
                    'new_time' => date('Y-m-d H:i:s', $new_start),
                    'duration' => round($new_duration / 60),
                    'reason' => $reason
                ];
                $last_end_time = $new_start + $new_duration;
            }
        } else {
            // Event doesn't need rescheduling, update last_end_time
            $last_end_time = $end;
        }
        
        // Ensure minimum break between events
        $last_end_time += $min_break;
    }
    
    // Process breaks to add
    foreach ($breaks_to_add as $break) {
        // Use a special key to indicate this is a new event to be created
        $changes[] = [
            'event_id' => 'new_break',
            'title' => $break['title'],
            'new_time' => $break['start_date'],
            'duration' => round((strtotime($break['end_date']) - strtotime($break['start_date'])) / 60),
            'reason' => $break['reason'],
            'action' => 'create'
        ];
    }
    
    // Process events that need to be moved to different days
    foreach ($events_to_move_days as $new_event) {
        $changes[] = [
            'event_id' => 'new_split_event',
            'title' => $new_event['title'],
            'new_time' => $new_event['start_date'],
            'duration' => round((strtotime($new_event['end_date']) - strtotime($new_event['start_date'])) / 60),
            'reason' => $new_event['reason'],
            'parent_id' => $new_event['parent_event_id'],
            'action' => 'create'
        ];
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
