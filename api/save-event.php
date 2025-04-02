<?php
require_once __DIR__ . '/../backend/db.php';

header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);

$stmt = $conn->prepare("INSERT INTO calendar_events (title, description, category_id, start_date) VALUES (?, ?, ?, ?)");
$stmt->bind_param("ssis", $data['title'], $data['desc'], $data['category'], $data['date']);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'id' => $conn->insert_id]);
} else {
    echo json_encode(['success' => false, 'error' => $conn->error]);
}
