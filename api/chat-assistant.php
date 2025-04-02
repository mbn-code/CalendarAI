<?php
require_once('../backend/db.php');
header('Content-Type: application/json');

try {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($data['message']) || !isset($data['userId'])) {
        throw new Exception('Missing required data');
    }
    
    $userId = $data['userId'];
    $message = $data['message'];
    
    // Log user message
    $stmt = $pdo->prepare("
        INSERT INTO assistant_chat (user_id, message, is_user)
        VALUES (?, ?, TRUE)
    ");
    $stmt->execute([$userId, $message]);
    
    // Get user preferences
    $stmt = $pdo->prepare("
        SELECT * FROM user_preferences WHERE user_id = ?
    ");
    $stmt->execute([$userId]);
    $preferences = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Get active system prompt
    $stmt = $pdo->prepare("
        SELECT prompt_text FROM system_prompts 
        WHERE user_id = ? AND is_active = TRUE 
        ORDER BY created_at DESC LIMIT 1
    ");
    $stmt->execute([$userId]);
    $systemPrompt = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Analyze the message for potential actions
    $action = analyzeMessage($message, $preferences);
    
    // For now, return a simple response
    // In a real implementation, this would integrate with an AI service
    $response = "I understand you want to " . strtolower($message) . ". I'll help you with that.";
    
    // Log assistant response
    $stmt = $pdo->prepare("
        INSERT INTO assistant_chat (user_id, message, is_user)
        VALUES (?, ?, FALSE)
    ");
    $stmt->execute([$userId, $response]);
    
    echo json_encode([
        'success' => true,
        'response' => $response,
        'action' => $action
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

function analyzeMessage($message, $preferences) {
    // Simple message analysis to determine action type
    $message = strtolower($message);
    
    if (strpos($message, 'add') !== false || strpos($message, 'create') !== false) {
        return ['action' => 'add'];
    } elseif (strpos($message, 'move') !== false || strpos($message, 'reschedule') !== false) {
        return ['action' => 'move'];
    } elseif (strpos($message, 'delete') !== false || strpos($message, 'remove') !== false) {
        return ['action' => 'delete'];
    }
    
    return ['action' => 'none'];
}