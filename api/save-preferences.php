<?php
// Start output buffering to prevent any unwanted output
ob_start();

require_once('../backend/db.php');

// Ensure proper JSON response headers
header('Content-Type: application/json');
header('Cache-Control: no-cache, must-revalidate');

// Set error handling
error_reporting(E_ALL);
ini_set('display_errors', 0);

function sendJsonResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    if (ob_get_length()) ob_clean();
    echo json_encode($data);
    exit;
}

try {
    // Validate JSON input
    $rawInput = file_get_contents('php://input');
    if (!$rawInput) {
        throw new Exception('No input data received');
    }
    
    $data = json_decode($rawInput, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Invalid JSON input: ' . json_last_error_msg());
    }
    
    if (!isset($data['userId']) || !isset($data['preferences'])) {
        throw new Exception('Missing required data (userId or preferences)');
    }
    
    $userId = (int)$data['userId'];
    if ($userId <= 0) {
        throw new Exception('Invalid user ID');
    }

    $prefs = $data['preferences'];
    $isBasicSetup = isset($data['isBasicSetup']) && $data['isBasicSetup'] === true;
    
    if ($isBasicSetup) {
        // For basic setup, save default parameters
        $stmt = $conn->prepare("
            INSERT INTO user_preferences 
            (user_id, focus_start_time, focus_end_time, chill_start_time, chill_end_time, 
             break_duration, session_length, priority_mode, has_completed_setup)
            VALUES (?, '09:00', '17:00', '17:00', '22:00', 15, 120, ?, true)
            ON DUPLICATE KEY UPDATE
            focus_start_time = VALUES(focus_start_time),
            focus_end_time = VALUES(focus_end_time),
            chill_start_time = VALUES(chill_start_time),
            chill_end_time = VALUES(chill_end_time),
            break_duration = VALUES(break_duration),
            session_length = VALUES(session_length),
            priority_mode = VALUES(priority_mode),
            has_completed_setup = true
        ");
        
        if (!$stmt) {
            throw new Exception('Failed to prepare statement: ' . $conn->error);
        }
        
        $priorityMode = $prefs['priorityMode'] ?? 'balanced';
        $stmt->bind_param("is", $userId, $priorityMode);
        
        if (!$stmt->execute()) {
            throw new Exception('Failed to save preferences: ' . $stmt->error);
        }

        // Save default system prompt
        $defaultPrompt = 'You are a helpful calendar assistant who helps optimize schedules with basic functionality.';
        $promptStmt = $conn->prepare("
            INSERT INTO system_prompts (user_id, prompt_text, is_active)
            VALUES (?, ?, true)
        ");
        
        if (!$promptStmt) {
            throw new Exception('Failed to prepare prompt statement: ' . $conn->error);
        }
        
        $promptStmt->bind_param("is", $userId, $defaultPrompt);
        if (!$promptStmt->execute()) {
            throw new Exception('Failed to save system prompt: ' . $promptStmt->error);
        }
    } else {
        // Validate all required fields for full setup
        $requiredFields = [
            'focusStartTime' => 'Focus start time',
            'focusEndTime' => 'Focus end time',
            'chillStartTime' => 'Chill start time',
            'chillEndTime' => 'Chill end time',
            'breakDuration' => 'Break duration',
            'sessionLength' => 'Session length',
            'priorityMode' => 'Priority mode'
        ];
        
        foreach ($requiredFields as $field => $label) {
            if (!isset($prefs[$field])) {
                throw new Exception("Missing required field: {$label}");
            }
        }
        
        $stmt = $conn->prepare("
            INSERT INTO user_preferences 
            (user_id, focus_start_time, focus_end_time, chill_start_time, chill_end_time, 
             break_duration, session_length, priority_mode, has_completed_setup)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, true)
            ON DUPLICATE KEY UPDATE
            focus_start_time = VALUES(focus_start_time),
            focus_end_time = VALUES(focus_end_time),
            chill_start_time = VALUES(chill_start_time),
            chill_end_time = VALUES(chill_end_time),
            break_duration = VALUES(break_duration),
            session_length = VALUES(session_length),
            priority_mode = VALUES(priority_mode),
            has_completed_setup = true
        ");
        
        if (!$stmt) {
            throw new Exception('Failed to prepare statement: ' . $conn->error);
        }
        
        $stmt->bind_param("issssiis", 
            $userId,
            $prefs['focusStartTime'],
            $prefs['focusEndTime'],
            $prefs['chillStartTime'],
            $prefs['chillEndTime'],
            $prefs['breakDuration'],
            $prefs['sessionLength'],
            $prefs['priorityMode']
        );
        
        if (!$stmt->execute()) {
            throw new Exception('Failed to save preferences: ' . $stmt->error);
        }
    }
    
    sendJsonResponse([
        'success' => true,
        'message' => 'Preferences saved successfully'
    ]);
    
} catch (Exception $e) {
    error_log('Save Preferences Error: ' . $e->getMessage());
    sendJsonResponse([
        'success' => false,
        'error' => $e->getMessage()
    ], 400);
}