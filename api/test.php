<?php
require_once __DIR__ . '/../config.php';
header('Content-Type: application/json');

try {
    debug_log('Testing API endpoints and Mistral connectivity');
    
    // Test Mistral API connection
    $curl = curl_init();
    curl_setopt_array($curl, [
        CURLOPT_URL => "http://localhost:11434/api/generate",
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode([
            "model" => "mistral",
            "prompt" => "Say 'test successful'",
            "stream" => false
        ]),
        CURLOPT_TIMEOUT => 5,
        CURLOPT_CONNECTTIMEOUT => 3
    ]);
    
    debug_log('Testing Mistral API connection');
    $response = curl_exec($curl);
    $error = curl_error($curl);
    $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    curl_close($curl);
    
    $result = [
        'success' => true,
        'message' => 'API test successful',
        'debug' => DEBUG,
        'mistral' => [
            'connected' => !$error && $httpCode === 200,
            'status_code' => $httpCode,
            'error' => $error ?: null
        ]
    ];
    
    debug_log('API test completed', $result);
    echo json_encode($result);
    
} catch (Exception $e) {
    debug_log('API test failed', [
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ]);
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'API test failed: ' . $e->getMessage()
    ]);
}
