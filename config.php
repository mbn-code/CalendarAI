<?php
// Debug configuration
define('DEBUG', true);  // Set to false in production

// Ensure logs directory exists and is writable
$logsDir = __DIR__ . '/logs';
if (!file_exists($logsDir)) {
    mkdir($logsDir, 0777, true);
}
if (!is_writable($logsDir)) {
    chmod($logsDir, 0777);
}

// Set error log path
ini_set('error_log', $logsDir . '/debug.log');

// Debug helper functions
function sanitize_debug_data($data) {
    if (is_array($data) || is_object($data)) {
        $clean = [];
        foreach ((array)$data as $key => $value) {
            $clean[$key] = sanitize_debug_data($value);
        }
        return $clean;
    }
    
    if (is_string($data)) {
        // Remove sensitive data patterns
        $data = preg_replace('/password["\']?\s*:\s*["\']([^"\']*)["\']/i', 'password: "[REDACTED]"', $data);
        $data = preg_replace('/token["\']?\s*:\s*["\']([^"\']*)["\']/i', 'token: "[REDACTED]"', $data);
        return htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    }
    
    return $data;
}

function format_debug_data($data) {
    if (is_array($data) || is_object($data)) {
        return json_encode(sanitize_debug_data($data), JSON_PRETTY_PRINT);
    }
    return sanitize_debug_data((string)$data);
}

function debug_log($message, $data = null) {
    if (!DEBUG) return;
    
    $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);
    $caller = $backtrace[0];
    
    $logMessage = sprintf(
        "[%s] %s:%d - %s",
        date('Y-m-d H:i:s'),
        basename($caller['file']),
        $caller['line'],
        $message
    );
    
    if ($data !== null) {
        $formattedData = format_debug_data($data);
        $logMessage .= "\nData: " . $formattedData;
    }
    
    error_log($logMessage);
    
    // Also output to browser if it's an API call
    if (strpos($_SERVER['REQUEST_URI'], '/api/') !== false) {
        $debugInfo = [
            'timestamp' => date('Y-m-d H:i:s'),
            'file' => basename($caller['file']),
            'line' => $caller['line'],
            'message' => $message,
            'data' => $data !== null ? sanitize_debug_data($data) : null
        ];
        
        header('X-Debug-Info: ' . base64_encode(json_encode($debugInfo)));
    }
}