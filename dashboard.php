<?php
require_once __DIR__ . '/header/header.php';
require_once __DIR__ . '/backend/db.php';
require_once __DIR__ . '/backend/functions.php';
require_once __DIR__ . '/components/sidebar.php';
require_once __DIR__ . '/components/navbar.php';
require_once __DIR__ . '/components/setup-wizard.php';
require_once __DIR__ . '/components/calendar-assistant.php';

$stats = getEventStats();
$recentEvents = getRecentEvents();

// Check if user has completed setup
$hasCompletedSetup = false;
if (isset($_SESSION['user_id'])) {
    $setupCheck = $conn->prepare("SELECT has_completed_setup FROM user_preferences WHERE user_id = ?");
    $setupCheck->bind_param("i", $_SESSION['user_id']);
    $setupCheck->execute();
    $result = $setupCheck->get_result();
    if ($row = $result->fetch_assoc()) {
        $hasCompletedSetup = (bool)$row['has_completed_setup'];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Calendar Pro</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.15.4/css/all.css">
</head>
<body class="bg-gray-50">
    <?= renderSidebar('dashboard') ?>
    <?= renderNavbar() ?>
    
    <!-- Setup Wizard -->
    <?= renderSetupWizard() ?>
    
    <!-- Calendar Assistant -->
    <?= renderCalendarAssistant() ?>
    
    <main class="ml-64 pt-16 min-h-screen">
        <div class="p-6">
            <h1 class="text-2xl font-bold text-gray-800 mb-6">Dashboard</h1>
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                <!-- Statistics Cards -->
                <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
                    <div class="flex items-center">
                        <i class="fas fa-calendar-check text-blue-500 text-3xl"></i>
                        <div class="ml-4">
                            <h3 class="text-sm font-medium text-gray-500">Total Events</h3>
                            <p class="text-2xl font-bold text-gray-800"><?= $stats['total'] ?></p>
                        </div>
                    </div>
                </div>
                
                <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
                    <div class="flex items-center">
                        <i class="fas fa-clock text-green-500 text-3xl"></i>
                        <div class="ml-4">
                            <h3 class="text-sm font-medium text-gray-500">Upcoming Events</h3>
                            <p class="text-2xl font-bold text-gray-800"><?= $stats['upcoming'] ?></p>
                        </div>
                    </div>
                </div>
                
                <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
                    <div class="flex items-center">
                        <i class="fas fa-tags text-purple-500 text-3xl"></i>
                        <div class="ml-4">
                            <h3 class="text-sm font-medium text-gray-500">Categories</h3>
                            <p class="text-2xl font-bold text-gray-800"><?= $stats['categories'] ?></p>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Recent Events -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                <h2 class="text-lg font-bold text-gray-800 mb-4">Recent Events</h2>
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="text-left text-sm font-medium text-gray-500 border-b">
                                <th class="pb-3 px-4">Event</th>
                                <th class="pb-3 px-4">Category</th>
                                <th class="pb-3 px-4">Date</th>
                                <th class="pb-3 px-4">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recentEvents as $event): ?>
                            <tr class="border-b">
                                <td class="py-3 px-4"><?= htmlspecialchars($event['title']) ?></td>
                                <td class="py-3 px-4">
                                    <span class="px-2 py-1 rounded-full text-xs" 
                                          style="background-color: <?= $event['category_color'] ?>25; color: <?= $event['category_color'] ?>;">
                                        <?= htmlspecialchars($event['category_name']) ?>
                                    </span>
                                </td>
                                <td class="py-3 px-4"><?= date('M d, Y', strtotime($event['start_date'])) ?></td>
                                <td class="py-3 px-4">
                                    <?php
                                    $today = date('Y-m-d');
                                    $eventDate = date('Y-m-d', strtotime($event['start_date']));
                                    if ($eventDate < $today):
                                    ?>
                                        <span class="px-2 py-1 bg-gray-50 text-gray-700 rounded-full text-xs">Past</span>
                                    <?php elseif ($eventDate == $today): ?>
                                        <span class="px-2 py-1 bg-blue-50 text-blue-700 rounded-full text-xs">Today</span>
                                    <?php else: ?>
                                        <span class="px-2 py-1 bg-green-50 text-green-700 rounded-full text-xs">Upcoming</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>
    
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Initialize based on setup status and URL parameter
        const hasCompletedSetup = <?= json_encode($hasCompletedSetup) ?>;
        const showSetupParam = new URLSearchParams(window.location.search).get('setup');
        
        if (!hasCompletedSetup || showSetupParam === 'true') {
            showSetupWizard();
        }
        
        // Calendar Assistant initialization
        const assistant = document.getElementById('calendarAssistant');
        const toggleButton = document.getElementById('toggleAssistant');
        
        if (toggleButton) {
            toggleButton.addEventListener('click', () => {
                assistant.classList.toggle('translate-x-0');
                assistant.classList.toggle('translate-x-full');
                toggleButton.querySelector('i').classList.toggle('fa-chevron-left');
                toggleButton.querySelector('i').classList.toggle('fa-chevron-right');
            });
        }

        // Handle skip setup with notification
        const skipButton = document.getElementById('skipSetup');
        if (skipButton) {
            skipButton.addEventListener('click', async () => {
                const basicPreferences = {
                    priorityMode: 'balanced',
                    systemPrompt: 'You are a helpful calendar assistant who helps optimize schedules with basic functionality.'
                };

                try {
                    const response = await fetch('/calendar/api/save-preferences.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({
                            userId: <?= $_SESSION['user_id'] ?? 0 ?>,
                            preferences: basicPreferences,
                            isBasicSetup: true
                        })
                    });

                    const result = await response.json();
                    if (result.success) {
                        document.getElementById('setupWizard').classList.add('hidden');
                        showNotification('Using basic calendar assistant features', 'info');
                        showCalendarAssistant();
                    } else {
                        throw new Error(result.error);
                    }
                } catch (error) {
                    console.error('Failed to skip setup:', error);
                    showNotification('Failed to skip setup. Please try again.', 'error');
                }
            });
        }

        // Helper function to show calendar assistant
        function showCalendarAssistant() {
            if (assistant) {
                assistant.classList.remove('translate-x-full');
            }
        }
    });
    </script>
</body>
</html>
