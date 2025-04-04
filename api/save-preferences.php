<?php
require_once __DIR__ . '/../backend/db.php';
require_once __DIR__ . '/../config.php';

header('Content-Type: application/json');
session_start();

try {
    if (!isset($_SESSION['user_id'])) {
        throw new Exception('User not authenticated');
    }

    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    if (!$data) {
        throw new Exception('Invalid input data');
    }

    $userId = $_SESSION['user_id'];
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
            has_completed_setup = true"
        );
        
        if (!$stmt) {
            throw new Exception('Failed to prepare statement: ' . $conn->error);
        }
        
        $priorityMode = $prefs['priorityMode'] ?? 'balanced';
        $stmt->bind_param("is", $userId, $priorityMode);
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
            has_completed_setup = true"
        );
        
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
    }
    
    if (!$stmt->execute()) {
        throw new Exception('Failed to save preferences: ' . $stmt->error);
    }
    
    // Add debug log
    debug_log('Preferences saved successfully', [
        'user_id' => $userId,
        'is_basic_setup' => $isBasicSetup,
        'preferences' => $prefs
    ]);

    echo json_encode([
        'success' => true,
        'message' => 'Preferences saved successfully'
    ]);

} catch (Exception $e) {
    debug_log('Error saving preferences', [
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ]);

    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}