<?php

require_once __DIR__ . '/../components/navbar.php';

// Authentication check
function checkAuth() {
    $userType = $_SESSION['user_type'] ?? '';
    $isAuthenticated = false;
    
    switch ($userType) {
        case 'admin':
            $isAuthenticated = isset($_SESSION['admin_id']);
            break;
        case 'teacher':
            $isAuthenticated = isset($_SESSION['teacher_id']);
            break;
        case 'student':
            $isAuthenticated = isset($_SESSION['student_id']);
            break;
    }
    
    if (!$isAuthenticated) {
        header('Location: /Muffin/index.php');
        exit();
    }
}

function renderHeader($title = 'Home') {
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title><?= $title ?></title>
        
        <!-- Dependencies -->
        <script src="https://cdn.tailwindcss.com"></script>
        <script src="https://cdn.jsdelivr.net/npm/toastify-js"></script>
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/toastify-js/src/toastify.min.css">
        <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.15.4/css/all.css">
        
        <!-- Global notification function -->
        <script>
        window.showNotification = function(message, type = 'success') {
            const colors = {
                success: '#10B981',
                error: '#EF4444',
                info: '#3B82F6',
                warning: '#F59E0B'
            };
            
            Toastify({
                text: message,
                duration: 3000,
                gravity: "top",
                position: "right",
                backgroundColor: colors[type] || colors.info,
                stopOnFocus: true,
                className: "rounded-lg"
            }).showToast();
        }
        </script>

        <!-- Custom Styles -->
        <style>
            :root {
                --primary: #115ec2;
                --accent: #0099ff;
                --secondary: #aeaeae;
                --background: #f5f5f5;
                --CWD: #687560;
            }

            /* Wizard & Assistant Styles */
            .wizard-step {
                transition: all 0.3s ease;
            }

            /* Enhanced AI-optimized event styles */
            .event-pill.ai-optimized {
                box-shadow: 0 0 8px rgba(124, 58, 237, 0.3);
                transition: all 0.3s ease;
            }
            
            .event-pill.ai-optimized:hover {
                transform: scale(1.05);
                box-shadow: 0 0 12px rgba(124, 58, 237, 0.4);
            }

            /* Preset-specific optimized event styles */
            .event-pill.ai-optimized.preset-default {
                background-color: #f0ebfe !important;
                border-color: #d8ccfd !important;
                color: #6941c6 !important;
                box-shadow: 0 0 8px rgba(124, 58, 237, 0.3);
            }

            .event-pill.ai-optimized.preset-busy {
                background-color: #fef3c7 !important;
                border-color: #fcd34d !important;
                color: #92400e !important;
                box-shadow: 0 0 8px rgba(251, 191, 36, 0.3);
            }

            .event-pill.ai-optimized.preset-conflicts {
                background-color: #fee2e2 !important;
                border-color: #fca5a5 !important;
                color: #991b1b !important;
                box-shadow: 0 0 8px rgba(239, 68, 68, 0.3);
            }

            .event-pill.ai-optimized.preset-optimized {
                background-color: #d1fae5 !important;
                border-color: #6ee7b7 !important;
                color: #065f46 !important;
                box-shadow: 0 0 8px rgba(16, 185, 129, 0.3);
            }

            .event-pill.ai-optimized:hover {
                transform: scale(1.05);
            }

            .chat-message-user {
                background-color: #f3f4f6;
                border-radius: 1rem 1rem 0.25rem 1rem;
                padding: 0.75rem 1rem;
                margin-left: auto;
                max-width: 80%;
            }

            .chat-message-assistant {
                background-color: #f5f3ff;
                border-radius: 1rem 1rem 1rem 0.25rem;
                padding: 0.75rem 1rem;
                margin-right: auto;
                max-width: 80%;
            }
        </style>

        <!-- Tailwind Custom Components -->
        <style type="text/tailwindcss">
            @layer components {
                .btn-primary {
                    @apply w-full py-3 px-4 bg-primary text-white rounded-lg shadow-md text-lg 
                    transition-all duration-200 hover:opacity-90 hover:shadow-lg;
                }

                .profile-card {
                    @apply bg-white shadow-lg rounded-2xl p-4 sm:p-6 mx-4 sm:mx-auto 
                    flex flex-col md:flex-row gap-6 max-w-4xl;
                }
            }
        </style>

        <!-- Animation Styles -->
        <style>
            @keyframes slideIn {
                from {
                    opacity: 0;
                    transform: translateX(100%);
                }
                to {
                    opacity: 1;
                    transform: translateX(0);
                }
            }
            @keyframes slideOut {
                from {
                    opacity: 1;
                    transform: translateX(0);
                }
                to {
                    opacity: 0;
                    transform: translateX(100%);
                }
            }
            .animate-slideIn {
                animation: slideIn 0.5s forwards;
            }
            .animate-slideOut {
                animation: slideOut 0.5s forwards;
            }
        </style>
    </head>
    <body class="bg-background min-h-screen">
    <?php
    // Render the navbar
    echo renderNavbar();
}
?>

