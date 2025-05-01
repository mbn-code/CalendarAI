<?php
require_once __DIR__ . '/../config.php';

try {
    // Create mysqli connection
    $dbHost = 'localhost';
    $dbUser = 'root';
    $dbPass = ''; // Default XAMPP password - CHANGE IF YOURS IS DIFFERENT
    $dbName = 'calendar'; // Use consistent database name

    $conn = new mysqli($dbHost, $dbUser, $dbPass, $dbName);

    if ($conn->connect_error) {
        if (DEBUG) {
            debug_log('Database connection failed (mysqli)', [
                'error' => $conn->connect_error,
                'host' => $dbHost,
                'database' => $dbName
            ]);
        }
        throw new Exception("Connection failed (mysqli): " . $conn->connect_error);
    }

    // Set character set
    if (!$conn->set_charset("utf8mb4")) {
        if (DEBUG) {
            debug_log('Error setting charset (mysqli)', [
                'error' => $conn->error
            ]);
        }
        // Don't throw exception here, maybe log and continue
        error_log("Warning: Error setting charset (mysqli): " . $conn->error);
    }

    // Also create PDO connection for scripts that use it
    // Use the same credentials and database name
    $dsn = "mysql:host={$dbHost};dbname={$dbName};charset=utf8mb4";
    $pdo = new PDO($dsn, $dbUser, $dbPass); // Use variables defined above
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    // Removed debug code that inserted conflicting test user/preferences
    // The default admin user (ID 1) inserted by database.sql can be used.

} catch (PDOException $e) { // Catch PDO specific exceptions
    if (DEBUG) {
        debug_log('Database initialization error (PDO)', [
            'message' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
    }
    // Provide a more specific error for PDO connection issues
    die("Database connection failed (PDO): " . $e->getMessage());

} catch (Exception $e) { // Catch general exceptions (like mysqli connection)
    if (DEBUG) {
        debug_log('Database initialization error', [
            'message' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
    }
    die("Database connection failed: " . $e->getMessage());
}