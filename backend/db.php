<?php

$conn = new mysqli('localhost', 'root', '2671', 'calendar');

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>