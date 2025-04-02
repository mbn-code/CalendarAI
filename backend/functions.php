<?php
require_once __DIR__ . '/db.php';

function getEventStats() {
    global $conn;
    
    $stats = [
        'total' => 0,
        'upcoming' => 0,
        'categories' => 0
    ];
    
    // Get total events
    $result = $conn->query("SELECT COUNT(*) as total FROM calendar_events");
    $stats['total'] = $result->fetch_assoc()['total'];
    
    // Get upcoming events
    $result = $conn->query("SELECT COUNT(*) as upcoming FROM calendar_events WHERE start_date >= CURDATE()");
    $stats['upcoming'] = $result->fetch_assoc()['upcoming'];
    
    // Get category count
    $result = $conn->query("SELECT COUNT(*) as categories FROM event_categories");
    $stats['categories'] = $result->fetch_assoc()['categories'];
    
    return $stats;
}

function getRecentEvents($limit = 5) {
    global $conn;
    
    $query = "SELECT e.*, c.name as category_name, c.color as category_color 
              FROM calendar_events e 
              LEFT JOIN event_categories c ON e.category_id = c.id 
              WHERE e.start_date >= CURDATE() 
              ORDER BY e.start_date ASC 
              LIMIT ?";
              
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $limit);
    $stmt->execute();
    
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}
