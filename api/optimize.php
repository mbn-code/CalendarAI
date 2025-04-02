<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../backend/db.php';
header('Content-Type: application/json');
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'User not authenticated']);
    exit;
}

try {
    // Read and decode the JSON input from JavaScript
    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input || !isset($input['preferences'])) {
        throw new Exception("Invalid input data");
    }
    
    $preferences = $input['preferences'];
    
    // Get the user ID from session
    $userId = $_SESSION['user_id'];
    
    // Get events for optimization
    $query = "SELECT ce.*, ec.name as category_name, ec.color as category_color 
             FROM calendar_events ce 
             LEFT JOIN event_categories ec ON ce.category_id = ec.id 
             WHERE ce.user_id = ? AND ce.start_date >= CURDATE() 
             ORDER BY ce.start_date ASC";
    $stmt = $conn->prepare($query);
    
    if (!$stmt) {
        throw new Exception("Database error: " . $conn->error);
    }
    
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $events = $result->fetch_all(MYSQLI_ASSOC);
    
    if (count($events) === 0) {
        echo json_encode([
            'success' => true, 
            'message' => 'No events to optimize', 
            'events' => [],
            'schedule_health' => [
                'focus_time_utilization' => 0,
                'break_compliance' => 0,
                'conflict_score' => 0,
                'balance_score' => 0
            ],
            'suggestions' => ["No events found to optimize."],
            'changes' => []
        ]);
        exit;
    }
    
    // Enhanced optimization algorithm
    $optimizedEvents = [];
    $changes = [];
    $categoryDistribution = [];
    $timeDistribution = [
        'morning' => 0,
        'afternoon' => 0,
        'evening' => 0
    ];
    
    // Analyze current schedule
    foreach ($events as $event) {
        $hour = (int)date('H', strtotime($event['start_date']));
        if ($hour >= 6 && $hour < 12) $timeDistribution['morning']++;
        else if ($hour >= 12 && $hour < 17) $timeDistribution['afternoon']++;
        else if ($hour >= 17 && $hour < 22) $timeDistribution['evening']++;
        
        if (isset($event['category_name'])) {
            $categoryDistribution[$event['category_name']] = ($categoryDistribution[$event['category_name']] ?? 0) + 1;
        }
    }
    
    // Calculate schedule health metrics
    $schedule_health = [
        'focus_time_utilization' => calculateFocusTimeUtilization($events, $preferences),
        'break_compliance' => calculateBreakCompliance($events),
        'conflict_score' => calculateConflictScore($events),
        'balance_score' => calculateBalanceScore($timeDistribution, $preferences['studyTime']),
        'category_distribution' => $categoryDistribution,
        'time_distribution' => $timeDistribution
    ];
    
    // Generate detailed suggestions
    $suggestions = generateDetailedSuggestions($schedule_health, $preferences);
    
    // Create optimized changes
    foreach ($events as $index => $event) {
        $optimizedTime = optimizeEventTime($event, $preferences, $schedule_health);
        if ($optimizedTime !== $event['start_date']) {
            $changes[] = [
                'event_id' => $event['id'],
                'new_time' => $optimizedTime,
                'original_time' => $event['start_date'],
                'duration' => calculateEventDuration($event),
                'reason' => generateChangeReason($event, $optimizedTime, $preferences),
                'category' => $event['category_name'] ?? 'Uncategorized',
                'category_color' => $event['category_color'] ?? '#808080',
                'impact_score' => calculateImpactScore($event, $optimizedTime, $schedule_health)
            ];
        }
        
        // Mark event as AI optimized
        $updateQuery = "UPDATE calendar_events SET is_ai_optimized = 1 WHERE id = ?";
        $updateStmt = $conn->prepare($updateQuery);
        $updateStmt->bind_param("i", $event['id']);
        $updateStmt->execute();
    }
    
    // Sort changes by impact score
    usort($changes, function($a, $b) {
        return $b['impact_score'] <=> $a['impact_score'];
    });
    
    // Return enhanced response
    echo json_encode([
        'success' => true, 
        'message' => 'Schedule optimized successfully', 
        'schedule_health' => $schedule_health,
        'suggestions' => $suggestions,
        'changes' => $changes,
        'statistics' => [
            'total_events' => count($events),
            'optimized_events' => count($changes),
            'improvement_score' => calculateImprovementScore($schedule_health)
        ]
    ]);
    
} catch (Exception $e) {
    error_log("Optimization error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Optimization failed: ' . $e->getMessage()]);
}

// Helper functions
function calculateFocusTimeUtilization($events, $preferences) {
    // Calculate how well events align with preferred focus times
    $totalEvents = count($events);
    $alignedEvents = 0;
    
    foreach ($events as $event) {
        $hour = (int)date('H', strtotime($event['start_date']));
        $isAligned = false;
        
        switch($preferences['studyTime']) {
            case 'morning':
                $isAligned = ($hour >= 6 && $hour < 12);
                break;
            case 'afternoon':
                $isAligned = ($hour >= 12 && $hour < 17);
                break;
            case 'evening':
                $isAligned = ($hour >= 17 && $hour < 22);
                break;
        }
        
        if ($isAligned) $alignedEvents++;
    }
    
    return $totalEvents > 0 ? round(($alignedEvents / $totalEvents) * 100) : 100;
}

function calculateBreakCompliance($events) {
    // Check if events have adequate breaks between them
    $breakViolations = 0;
    $sortedEvents = $events;
    usort($sortedEvents, function($a, $b) {
        return strtotime($a['start_date']) - strtotime($b['start_date']);
    });
    
    for ($i = 0; $i < count($sortedEvents) - 1; $i++) {
        $gap = strtotime($sortedEvents[$i + 1]['start_date']) - strtotime($sortedEvents[$i]['start_date']);
        if ($gap < 900) { // Less than 15 minutes
            $breakViolations++;
        }
    }
    
    return $breakViolations > 0 ? round((1 - ($breakViolations / count($events))) * 100) : 100;
}

function calculateConflictScore($events) {
    // Count overlapping events
    $conflicts = 0;
    for ($i = 0; $i < count($events); $i++) {
        for ($j = $i + 1; $j < count($events); $j++) {
            if (eventsOverlap($events[$i], $events[$j])) {
                $conflicts++;
            }
        }
    }
    return $conflicts;
}

function calculateBalanceScore($distribution, $preferredTime) {
    // Calculate how well the schedule matches preferred distribution
    $total = array_sum($distribution);
    if ($total === 0) return 100;
    
    $idealDistribution = [
        'morning' => $preferredTime === 'morning' ? 0.5 : 0.25,
        'afternoon' => $preferredTime === 'afternoon' ? 0.5 : 0.25,
        'evening' => $preferredTime === 'evening' ? 0.5 : 0.25
    ];
    
    $score = 100;
    foreach ($distribution as $time => $count) {
        $actualPercentage = $count / $total;
        $difference = abs($actualPercentage - $idealDistribution[$time]);
        $score -= ($difference * 100);
    }
    
    return max(0, round($score));
}

function generateDetailedSuggestions($health, $preferences) {
    $suggestions = [];
    
    if ($health['focus_time_utilization'] < 70) {
        $suggestions[] = "Consider moving more events to your preferred {$preferences['studyTime']} time slot to improve focus";
    }
    
    if ($health['break_compliance'] < 80) {
        $suggestions[] = "Add more breaks between events to maintain productivity";
    }
    
    if ($health['conflict_score'] > 0) {
        $suggestions[] = "Resolve scheduling conflicts to reduce overlapping events";
    }
    
    if ($health['balance_score'] < 60) {
        $suggestions[] = "Rebalance your schedule to better match your preferred working hours";
    }
    
    return $suggestions;
}

function calculateEventDuration($event) {
    if (!isset($event['end_date']) || $event['end_date'] === null) {
        return 60; // Default duration
    }
    return round((strtotime($event['end_date']) - strtotime($event['start_date'])) / 60);
}

function generateChangeReason($event, $newTime, $preferences) {
    $oldHour = (int)date('H', strtotime($event['start_date']));
    $newHour = (int)date('H', strtotime($newTime));
    
    if ($preferences['studyTime'] === 'morning' && $newHour >= 6 && $newHour < 12) {
        return "Moved to morning hours for better focus";
    } else if ($preferences['studyTime'] === 'afternoon' && $newHour >= 12 && $newHour < 17) {
        return "Aligned with preferred afternoon schedule";
    } else if ($preferences['studyTime'] === 'evening' && $newHour >= 17 && $newHour < 22) {
        return "Scheduled during optimal evening hours";
    }
    
    return "Optimized timing based on your preferences";
}

function calculateImpactScore($event, $newTime, $health) {
    // Calculate how much this change improves the schedule
    $score = 0;
    
    // More points for resolving conflicts
    if ($health['conflict_score'] > 0) $score += 30;
    
    // Points for improving time distribution
    if ($health['balance_score'] < 70) $score += 20;
    
    // Points for maintaining breaks
    if ($health['break_compliance'] < 80) $score += 25;
    
    return min(100, $score);
}

function calculateImprovementScore($health) {
    return round(
        ($health['focus_time_utilization'] + 
         $health['break_compliance'] + 
         (100 - ($health['conflict_score'] * 10)) + 
         $health['balance_score']) / 4
    );
}

function eventsOverlap($event1, $event2) {
    $start1 = strtotime($event1['start_date']);
    $start2 = strtotime($event2['start_date']);
    $end1 = isset($event1['end_date']) ? strtotime($event1['end_date']) : $start1 + 3600;
    $end2 = isset($event2['end_date']) ? strtotime($event2['end_date']) : $start2 + 3600;
    
    return ($start1 < $end2) && ($start2 < $end1);
}

function optimizeEventTime($event, $preferences, $health) {
    // Implement smart time optimization logic
    $currentHour = (int)date('H', strtotime($event['start_date']));
    $preferredStart = 0;
    
    switch($preferences['studyTime']) {
        case 'morning':
            $preferredStart = 8; // 8 AM
            break;
        case 'afternoon':
            $preferredStart = 13; // 1 PM
            break;
        case 'evening':
            $preferredStart = 18; // 6 PM
            break;
    }
    
    if (abs($currentHour - $preferredStart) <= 2) {
        return $event['start_date']; // Already in a good time slot
    }
    
    // Move to preferred time while maintaining same minutes
    $newTime = date('Y-m-d ', strtotime($event['start_date'])) . 
               sprintf('%02d:%s:00', 
                      $preferredStart + rand(0, 3), 
                      date('i', strtotime($event['start_date'])));
    
    return $newTime;
}
?>
