<?php
require_once __DIR__ . '/middleware.php';

// Clear all session data and redirect to login
session_destroy();
header('Location: /CalendarAI/frontend/pages/auth/login/login.php');
exit();