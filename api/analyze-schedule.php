<?php
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../backend/db.php';
header('Content-Type: application/json');

// Add CORS headers for API access
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

function debug_log($message, $context = []) {
    error_log($message . ' ' . json_encode($context));
}

function getScheduleAnalysis($userId) {
    debug_log('Starting schedule analysis', ['user_id' => $userId]);
    global $conn;
    
    // Get user preferences
    $prefQuery = "SELECT * FROM user_preferences WHERE user_id = ?";
    $stmt = $conn->prepare($prefQuery);
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $preferences = $stmt->get_result()->fetch_assoc();
    
    debug_log('Retrieved user preferences', $preferences);
    
    // Get current month's events
    $eventsQuery = "SELECT e.*, c.name as category_name 
                   FROM calendar_events e
                   LEFT JOIN event_categories c ON e.category_id = c.id
                   WHERE MONTH(e.start_date) = MONTH(CURRENT_DATE())
                   AND YEAR(e.start_date) = YEAR(CURRENT_DATE())
                   ORDER BY e.start_date";
    $events = $conn->query($eventsQuery)->fetch_all(MYSQLI_ASSOC);
    
    debug_log('Retrieved events for analysis', ['count' => count($events)]);
    
    // Format schedule for AI analysis
    $scheduleText = "Current Schedule:\n";
    foreach ($events as $event) {
        $scheduleText .= sprintf(
            "- %s (%s) at %s for %d minutes\n",
            $event['title'],
            $event['category_name'] ?? 'No Category',
            date('Y-m-d H:i', strtotime($event['start_date'])),
            (strtotime($event['end_date']) - strtotime($event['start_date'])) / 60
        );
    }
    
    // Format preferences for AI
    $preferencesText = "User Preferences:\n";
    if ($preferences) {
        $preferencesText .= "- Focus Time: {$preferences['focus_start_time']} to {$preferences['focus_end_time']}\n";
        $preferencesText .= "- Chill Time: {$preferences['chill_start_time']} to {$preferences['chill_end_time']}\n";
        $preferencesText .= "- Break Duration: {$preferences['break_duration']} minutes\n";
        $preferencesText .= "- Session Length: {$preferences['session_length']} minutes\n";
        $preferencesText .= "- Priority Mode: {$preferences['priority_mode']}\n";
    }
    
    debug_log('Prepared AI analysis input', [
        'schedule_length' => strlen($scheduleText),
        'preferences_length' => strlen($preferencesText)
    ]);
    
    // Get AI analysis
    $curl = curl_init();
    curl_setopt_array($curl, [
        CURLOPT_URL => "http://localhost:11434/api/generate",
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode([
            "model" => "mistral",
            "prompt" => "Analyze this schedule and preferences, then return a JSON object with the following structure:
            {
                \"insights\": [
                    \"specific observation about schedule patterns\",
                    \"potential improvements based on preferences\"
                ],
                \"optimization_suggestions\": [
                    {
                        \"event\": \"event title\",
                        \"suggestion\": \"specific suggestion\",
                        \"reason\": \"explanation why\"
                    }
                ],
                \"schedule_health\": {
                    \"focus_time_utilization\": percentage (0-100),
                    \"break_compliance\": percentage (0-100),
                    \"conflict_score\": number (0-10, lower is better),
                    \"balance_score\": number (0-10, higher is better)
                }
            }

            {$scheduleText}

            {$preferencesText}",
            "stream" => false
        ])
    ]);
    
    debug_log('Calling Mistral API');
    $response = curl_exec($curl);
    $error = curl_error($curl);
    $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    curl_close($curl);
    
    if ($error) {
        debug_log('Mistral API error', ['error' => $error, 'http_code' => $httpCode]);
        throw new Exception("Failed to analyze schedule: " . $error);
    }
    
    debug_log('Received Mistral API response', ['http_code' => $httpCode]);
    
    if ($httpCode !== 200) {
        throw new Exception("AI model returned non-200 status code: " . $httpCode);
    }
    
    $result = json_decode($response, true);
    if (!$result) {
        debug_log('Failed to parse Mistral response', [
            'error' => json_last_error_msg(),
            'response' => $response
        ]);
        throw new Exception("Failed to parse AI model response: " . json_last_error_msg());
    }
    
    if (!isset($result['response'])) {
        debug_log('Invalid response structure', $result);
        throw new Exception("Invalid response structure from AI model");
    }
    
    $analysisData = json_decode($result['response'], true);
    if (!$analysisData) {
        debug_log('Failed to parse analysis data', [
            'error' => json_last_error_msg(),
            'response' => $result['response']
        ]);
        throw new Exception("Failed to parse schedule analysis data");
    }
    
    // Validate the required structure
    $requiredKeys = ['insights', 'optimization_suggestions', 'schedule_health'];
    foreach ($requiredKeys as $key) {
        if (!isset($analysisData[$key])) {
            debug_log('Missing required key in analysis', [
                'missing_key' => $key,
                'data' => $analysisData
            ]);
            throw new Exception("Missing required key in analysis data: " . $key);
        }
    }
    
    debug_log('Analysis completed successfully', [
        'insights_count' => count($analysisData['insights']),
        'suggestions_count' => count($analysisData['optimization_suggestions'])
    ]);
    
    return $analysisData;
}

try {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($input['userId'])) {
        debug_log('Missing user ID in request', $input);
        throw new Exception('Missing user ID');
    }
    
    debug_log('Starting analysis', ['user_id' => $input['userId']]);
    
    // Get user preferences
    $prefQuery = "SELECT * FROM user_preferences WHERE user_id = ?";
    $stmt = $conn->prepare($prefQuery);
    $stmt->bind_param("i", $input['userId']);
    $stmt->execute();
    $preferences = $stmt->get_result()->fetch_assoc();
    
    debug_log('Retrieved user preferences', $preferences);
    
    // Get current month's events
    $eventsQuery = "SELECT e.*, c.name as category_name 
                   FROM calendar_events e
                   LEFT JOIN event_categories c ON e.category_id = c.id
                   WHERE e.user_id = ? AND e.start_date >= CURDATE()
                   ORDER BY e.start_date ASC";
    $stmt = $conn->prepare($eventsQuery);
    $stmt->bind_param("i", $input['userId']);
    $stmt->execute();
    $events = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    debug_log('Retrieved events for analysis', ['count' => count($events)]);

    // Format schedule data for Mistral
    $scheduleData = [
        'events' => array_map(function($event) {
            return [
                'id' => $event['id'],
                'title' => $event['title'],
                'category' => $event['category_name'],
                'start_time' => $event['start_date'],
                'end_time' => $event['end_date'],
                'is_optimized' => (bool)($event['is_ai_optimized'] ?? false)
            ];
        }, $events),
        'preferences' => [
            'focus_hours' => [
                'start' => $preferences['focus_start_time'],
                'end' => $preferences['focus_end_time']
            ],
            'break_duration' => (int)$preferences['break_duration'],
            'session_length' => (int)$preferences['session_length'],
            'priority_mode' => $preferences['priority_mode']
        ]
    ];

    debug_log('Prepared schedule data for Mistral', [
        'events_count' => count($scheduleData['events']),
        'preferences' => $scheduleData['preferences']
    ]);

    // Call Mistral LLM through localhost API
    $curl = curl_init();
    curl_setopt_array($curl, [
        CURLOPT_URL => "http://localhost:11434/api/generate",
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode([
            "model" => "mistral",
            "prompt" => json_encode([
                'task' => 'analyze_schedule',
                'data' => $scheduleData
            ])
        ])
    ]);
    
    debug_log('Calling Mistral API');
    $response = curl_exec($curl);
    $error = curl_error($curl);
    curl_close($curl);
    
    if ($error) {
        debug_log('Mistral API error', ['error' => $error]);
        throw new Exception("Failed to analyze schedule: " . $error);
    }
    
    debug_log('Received Mistral API response', ['response' => $response]);
    
    $result = json_decode($response, true);
    if (!$result || !isset($result['response'])) {
        debug_log('Invalid AI response format', ['response' => $response]);
        throw new Exception("Invalid AI response format");
    }
    
    // Parse AI suggestions
    $analysis = json_decode($result['response'], true);
    if (!$analysis) {
        debug_log('Failed to parse schedule analysis', ['response' => $result['response']]);
        throw new Exception("Failed to parse schedule analysis");
    }
    
    debug_log('Analysis request completed successfully', ['analysis' => $analysis]);
    echo json_encode([
        'success' => true,
        'analysis' => $analysis
    ]);
    
} catch (Exception $e) {
    debug_log('Analysis request failed', [
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ]);
    http_response_code(500);
    error_log("Schedule Analysis Error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
    exit;
}