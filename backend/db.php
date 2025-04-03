<?php
require_once __DIR__ . '/../config.php';

try {
    $conn = new mysqli('localhost', 'root', '2671', 'calendar');
    
    if ($conn->connect_error) {
        if (DEBUG) {
            debug_log('Database connection failed', [
                'error' => $conn->connect_error,
                'host' => 'localhost',
                'database' => 'calendar_ai'
            ]);
        }
        throw new Exception("Connection failed: " . $conn->connect_error);
    }
    
    // Set character set
    if (!$conn->set_charset("utf8mb4")) {
        if (DEBUG) {
            debug_log('Error setting charset', [
                'error' => $conn->error
            ]);
        }
        throw new Exception("Error setting charset: " . $conn->error);
    }
    
    if (DEBUG) {
        // Ensure test user exists in debug mode
        $testUserQuery = "INSERT IGNORE INTO users (id, name, email) VALUES (1, 'Test User', 'test@example.com')";
        if (!$conn->query($testUserQuery)) {
            debug_log('Error creating test user', [
                'error' => $conn->error
            ]);
        }
        
        // Ensure test user preferences exist
        $testPrefsQuery = "INSERT IGNORE INTO user_preferences 
            (user_id, focus_start_time, focus_end_time, chill_start_time, chill_end_time, 
             break_duration, session_length, priority_mode) 
            VALUES 
            (1, '09:00:00', '17:00:00', '18:00:00', '22:00:00', 15, 45, 'balanced')";
        if (!$conn->query($testPrefsQuery)) {
            debug_log('Error creating test user preferences', [
                'error' => $conn->error
            ]);
        }
    }
    
} catch (Exception $e) {
    if (DEBUG) {
        debug_log('Database initialization error', [
            'message' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
    }
    die("Database connection failed: " . $e->getMessage());
}