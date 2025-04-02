<?php
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);

require_once __DIR__ . '/../backend/db.php';
header('Content-Type: application/json');

function getScheduleAnalysis($userId) {
    global $conn;
    
    // Get user preferences
    $prefQuery = "SELECT * FROM user_preferences WHERE user_id = ?";
    $stmt = $conn->prepare($prefQuery);
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $preferences = $stmt->get_result()->fetch_assoc();
    
    // Get current month's events
    $eventsQuery = "SELECT e.*, c.name as category_name 
                   FROM calendar_events e
                   LEFT JOIN event_categories c ON e.category_id = c.id
                   WHERE MONTH(e.start_date) = MONTH(CURRENT_DATE())
                   AND YEAR(e.start_date) = YEAR(CURRENT_DATE())
                   ORDER BY e.start_date";
    $events = $conn->query($eventsQuery)->fetch_all(MYSQLI_ASSOC);
    
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
    
    $response = curl_exec($curl);
    $error = curl_error($curl);
    $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    curl_close($curl);
    
    if ($error) {
        throw new Exception("Failed to analyze schedule: " . $error);
    }
    
    if ($httpCode !== 200) {
        throw new Exception("AI model returned non-200 status code: " . $httpCode);
    }
    
    $result = json_decode($response, true);
    if (!$result) {
        throw new Exception("Failed to parse AI model response: " . json_last_error_msg());
    }
    
    if (!isset($result['response'])) {
        // Log the raw response for debugging
        error_log("Invalid AI response format: " . print_r($response, true));
        throw new Exception("Invalid response structure from AI model");
    }
    
    $analysisData = json_decode($result['response'], true);
    if (!$analysisData) {
        error_log("Failed to parse analysis data: " . json_last_error_msg());
        error_log("Raw response content: " . $result['response']);
        throw new Exception("Failed to parse schedule analysis data");
    }
    
    // Validate the required structure
    $requiredKeys = ['insights', 'optimization_suggestions', 'schedule_health'];
    foreach ($requiredKeys as $key) {
        if (!isset($analysisData[$key])) {
            throw new Exception("Missing required key in analysis data: " . $key);
        }
    }
    
    return $analysisData;
}

try {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($input['userId'])) {
        throw new Exception('Missing user ID');
    }
    
    $analysis = getScheduleAnalysis($input['userId']);
    
    echo json_encode([
        'success' => true,
        'analysis' => $analysis
    ]);
    exit;

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
    exit;
}