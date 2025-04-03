<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function requireAuth() {
    if (!isset($_SESSION['user_id'])) {
        header('Location: /CalendarAI/frontend/pages/auth/login/login.php');
        exit();
    }
}

function requireGuest() {
    if (isset($_SESSION['user_id'])) {
        header('Location: /CalendarAI/index.php');
        exit();
    }
}

function isAuthenticated() {
    return isset($_SESSION['user_id']);
}

function isAdmin() {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
}

function logout() {
    session_destroy();
    header('Location: /CalendarAI/frontend/pages/auth/login/login.php');
    exit();
}