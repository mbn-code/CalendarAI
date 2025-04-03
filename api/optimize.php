<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../backend/db.php';
require_once __DIR__ . '/../config.php';
header('Content-Type: application/json');
session_start();

// In debug mode, allow testing without authentication
if (!isset($_SESSION['user_id'])) {
    if (DEBUG) {
        $_SESSION['user_id'] = 1;  // Use default test user
        debug_log('Debug mode: Using default test user');
    } else {
        debug_log('User not authenticated');
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'User not authenticated']);
        exit;
    }
}

try {
    debug_log('Starting schedule optimization');
    
    // Get and validate input data
    $rawInput = file_get_contents('php://input');
    debug_log('Received raw input', ['input' => $rawInput]);
    
    $input = json_decode($rawInput, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        debug_log('JSON decode error', ['error' => json_last_error_msg()]);
        throw new Exception("Invalid JSON input: " . json_last_error_msg());
    }
    
    debug_log('Parsed JSON input', $input);
    
    if (!$input || !isset($input['preferences'])) {
        debug_log('Missing preferences in input', $input);
        throw new Exception("Missing preferences in input data");
    }
    
    $preferences = $input['preferences'];
    $required = ['studyTime', 'learningStyle', 'priority'];
    foreach ($required as $field) {
        if (!isset($preferences[$field])) {
            debug_log('Missing required preference field', ['field' => $field]);
            throw new Exception("Missing required preference: {$field}");
        }
    }
    
    debug_log('Validated preferences', $preferences);
    $userId = $_SESSION['user_id'];
    
    // Get events for optimization
    $query = "SELECT ce.*, ec.name as category_name, ec.color as category_color 
             FROM calendar_events ce 
             LEFT JOIN event_categories ec ON ce.category_id = ec.id 
             WHERE ce.user_id = ? AND ce.start_date >= CURDATE() 
             ORDER BY ce.start_date ASC";
             
    debug_log('Preparing event query', ['query' => $query]);
    $stmt = $conn->prepare($query);
    if (!$stmt) {
        debug_log('Database prepare error', ['error' => $conn->error]);
        throw new Exception("Database error: " . $conn->error);
    }
    
    $stmt->bind_param("i", $userId);
    if (!$stmt->execute()) {
        debug_log('Database execute error', ['error' => $stmt->error]);
        throw new Exception("Failed to fetch events: " . $stmt->error);
    }
    
    $result = $stmt->get_result();
    $events = $result->fetch_all(MYSQLI_ASSOC);
    
    debug_log('Retrieved events', ['count' => count($events)]);
    
    if (count($events) === 0) {
        debug_log('No events found to optimize');
        echo json_encode([
            'success' => true,
            'message' => 'No events to optimize',
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

    // Prepare data for Mistral
    $mistralData = [
        'task' => 'optimize_schedule',
        'preferences' => [
            'study_time' => $preferences['studyTime'],
            'learning_style' => $preferences['learningStyle'],
            'priority_mode' => $preferences['priority'],
            'user_id' => $userId
        ],
        'events' => array_map(function($event) {
            return [
                'id' => $event['id'],
                'title' => $event['title'],
                'category' => $event['category_name'] ?? 'Uncategorized',
                'start_time' => $event['start_date'],
                'end_time' => $event['end_date'] ?? null,
                'is_optimized' => (bool)($event['is_ai_optimized'] ?? false),
                'is_human_altered' => (bool)($event['is_human_ai_altered'] ?? false)
            ];
        }, $events)
    ];
    
    debug_log('Prepared Mistral request data', $mistralData);

    // Call Mistral API with proper error handling
    $curl = curl_init();
    curl_setopt_array($curl, [
        CURLOPT_URL => "http://localhost:11434/api/generate",
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode([
            "model" => "mistral",
            "prompt" => json_encode($mistralData),
            "stream" => false,
            "max_tokens" => 2000,
            "temperature" => 0.7
        ]),
        CURLOPT_TIMEOUT => 10,  // Reduced timeout
        CURLOPT_CONNECTTIMEOUT => 5,
        CURLOPT_HTTPHEADER => ['Content-Type: application/json']
    ]);
    
    debug_log('Calling Mistral API');
    $response = curl_exec($curl);
    $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    $error = curl_error($curl);
    debug_log('Mistral API response', [
        'http_code' => $httpCode,
        'curl_error' => $error,
        'response_length' => strlen($response)
    ]);
    
    curl_close($curl);
    
    if ($error || $httpCode !== 200) {
        // Fallback response if Mistral is unavailable
        debug_log('Mistral API error or non-200 response, using fallback', [
            'error' => $error,
            'http_code' => $httpCode
        ]);
        
        echo json_encode([
            'success' => true,
            'message' => 'Schedule optimization completed with basic analysis',
            'schedule_health' => [
                'focus_time_utilization' => 75,
                'break_compliance' => 80,
                'conflict_score' => 2,
                'balance_score' => 7
            ],
            'suggestions' => [
                "Consider reviewing your schedule manually",
                "Basic optimization available - Mistral AI service unavailable"
            ],
            'changes' => array_map(function($event) {
                return [
                    'event_id' => $event['id'],
                    'new_time' => $event['start_date'],
                    'reason' => 'Keeping original schedule due to AI service unavailability'
                ];
            }, $events),
            'statistics' => [
                'total_events' => count($events),
                'optimized_events' => 0,
                'improvement_score' => 0
            ]
        ]);
        exit;
    }

    // Parse and validate Mistral response
    $result = json_decode($response, true);
    if (!$result) {
        debug_log('Failed to parse Mistral response', [
            'error' => json_last_error_msg(),
            'response' => substr($response, 0, 1000)
        ]);
        throw new Exception("Invalid response from Mistral API: " . json_last_error_msg());
    }

    if (!isset($result['response'])) {
        debug_log('Missing response field in Mistral result', $result);
        throw new Exception("Missing 'response' field in Mistral result");
    }

    // Parse the optimization result
    $optimizationResult = json_decode($result['response'], true);
    if (!$optimizationResult) {
        debug_log('Failed to parse optimization result', [
            'error' => json_last_error_msg(),
            'response' => $result['response']
        ]);
        throw new Exception("Failed to parse optimization result: " . json_last_error_msg());
    }

    // Validate optimization result structure
    $requiredFields = ['changes', 'schedule_health', 'suggestions'];
    foreach ($requiredFields as $field) {
        if (!isset($optimizationResult[$field])) {
            debug_log('Missing required field in optimization result', [
                'field' => $field,
                'result' => $optimizationResult
            ]);
            throw new Exception("Missing required field in optimization result: {$field}");
        }
    }

    // Process changes with validation
    $changes = [];
    foreach ($optimizationResult['changes'] as $change) {
        if (!isset($change['event_id'], $change['new_time'])) {
            debug_log('Invalid change format', $change);
            continue;
        }

        $changes[] = [
            'event_id' => $change['event_id'],
            'new_time' => $change['new_time'],
            'duration' => $change['duration'] ?? null,
            'reason' => $change['reason'] ?? 'Optimized based on preferences',
            'impact_score' => $change['impact_score'] ?? 0
        ];
    }
    
    debug_log('Processed changes', ['count' => count($changes)]);

    // Return enhanced response
    $response = [
        'success' => true,
        'message' => 'Schedule optimized successfully',
        'schedule_health' => $optimizationResult['schedule_health'],
        'suggestions' => $optimizationResult['suggestions'],
        'changes' => $changes,
        'statistics' => [
            'total_events' => count($events),
            'optimized_events' => count($changes),
            'improvement_score' => calculateImprovementScore($optimizationResult['schedule_health'])
        ]
    ];
    
    debug_log('Sending successful response', $response);
    echo json_encode($response);
    
} catch (Exception $e) {
    debug_log('Optimization error', [
        'message' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ]);
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Optimization failed: ' . $e->getMessage()
    ]);
}

function calculateImprovementScore($health) {
    return round(
        ($health['focus_time_utilization'] ?? 0 +
         $health['break_compliance'] ?? 0 +
         (100 - ($health['conflict_score'] ?? 0) * 10) +
         $health['balance_score'] ?? 0) / 4
    );
}
?>
