<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/frontend/pages/auth/middleware.php';
require_once __DIR__ . '/header/header.php';
require_once __DIR__ . '/backend/db.php';
require_once __DIR__ . '/components/sidebar.php';
require_once __DIR__ . '/components/navbar.php';
require_once __DIR__ . '/components/setup-wizard.php';
require_once __DIR__ . '/components/calendar-assistant.php';

// Require authentication for accessing the calendar
requireAuth();

// Get calendar parameters
$month = isset($_GET['month']) ? (int)$_GET['month'] : (int)date('m');
$year = isset($_GET['year']) ? (int)$_GET['year'] : (int)date('Y');

// Initialize setup state
$hasCompletedSetup = false;
$setupCheck = $conn->prepare("SELECT has_completed_setup FROM user_preferences WHERE user_id = ?");
$setupCheck->bind_param("i", $_SESSION['user_id']);
$setupCheck->execute();
$result = $setupCheck->get_result();

if ($row = $result->fetch_assoc()) {
    $hasCompletedSetup = (bool)$row['has_completed_setup'];
} else {
    // If no preferences exist, insert default record
    $stmt = $conn->prepare("
        INSERT INTO user_preferences 
        (user_id, focus_start_time, focus_end_time, chill_start_time, chill_end_time, break_duration, session_length, priority_mode, has_completed_setup)
        VALUES (?, '09:00', '17:00', '17:00', '22:00', 15, 120, 'balanced', false)
    ");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
}

$firstDayOfMonth = mktime(0, 0, 0, $month, 1, $year);
$numberDays = date('t', $firstDayOfMonth);
$firstDayOfWeek = date('w', $firstDayOfMonth);

// Fetch events for current month with user_id filter
$events_query = "SELECT * FROM calendar_events WHERE MONTH(start_date) = ? AND YEAR(start_date) = ? AND user_id = ?";
$stmt = $conn->prepare($events_query);
$stmt->bind_param("iii", $month, $year, $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();
$events = $result->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Calendar</title>
    
    <!-- Dependencies -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/toastify-js"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/toastify-js/src/toastify.min.css">
    <link href="https://cdn.jsdelivr.net/npm/@sweetalert2/theme-minimal/minimal.css" rel="stylesheet">
    <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.15.4/css/all.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="script.js"></script>
    
    <!-- Ensure showNotification is available -->
    <script>
    if (typeof showNotification !== 'function') {
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
    }
    </script>

    <style>
        .calendar-day {
            transition: all 0.2s ease-in-out;
            position: relative;
            overflow-y: auto;
            min-height: 120px;
            max-height: 300px;
            scrollbar-width: thin;
        }
        
        .calendar-day::-webkit-scrollbar {
            width: 4px;
        }
        
        .calendar-day::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 4px;
        }
        
        .calendar-day::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 4px;
        }
        
        .calendar-day:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }
        
        .event-pill {
            transition: all 0.2s ease;
            font-size: 0.75rem;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            margin-bottom: 0.25rem;
            position: relative;
            padding-right: 20px;
        }
        
        .event-pill.ai-optimized {
            background-color: #f0ebfe !important;
            border-color: #d8ccfd !important;
            color: #6941c6 !important;
        }
        
        .event-pill.ai-optimized::after {
            content: '✨';
            position: absolute;
            right: 4px;
            top: 50%;
            transform: translateY(-50%);
            font-size: 10px;
        }
        
        .event-pill.human-ai-altered {
            background-color: #ecfdf5 !important;
            border-color: #6ee7b7 !important;
            color: #047857 !important;
        }
        
        .event-pill.human-ai-altered::after {
            content: '👤';
            position: absolute;
            right: 4px;
            top: 50%;
            transform: translateY(-50%);
            font-size: 10px;
        }
        
        .event-pill:hover {
            transform: scale(1.02);
        }
        
        .event-pill .event-time {
            font-size: 0.65rem;
            opacity: 0.8;
            margin-left: 4px;
        }
        
        .optimization-indicator {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 3px;
            background: linear-gradient(90deg, #6ee7b7 var(--optimized-percent), transparent var(--optimized-percent));
            transition: all 0.3s ease;
        }
        
        .today-highlight {
            position: absolute;
            top: 4px;
            right: 4px;
            width: 6px;
            height: 6px;
            border-radius: 50%;
            background-color: #3b82f6;
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
</head>
<body class="bg-gray-50">
    <?= renderSidebar('calendar') ?>
    <?= renderNavbar() ?>
    
    <!-- Setup Wizard -->
    <?= renderSetupWizard() ?>
    
    <!-- Calendar Assistant -->
    <?= renderCalendarAssistant() ?>
    
    <main class="ml-64 pt-16 min-h-screen">
        <div class="p-6">
            <div class="bg-white rounded-xl shadow-lg">
                <!-- Calendar Header -->
                <div class="flex justify-between items-center mb-8 p-6">
                    <h1 class="text-3xl font-bold text-gray-800 tracking-tight">
                        <?php echo date('F Y', $firstDayOfMonth); ?>
                    </h1>
                    <div class="flex gap-3">
                        <?php
                        $prevMonth = $month - 1;
                        $prevYear = $year;
                        if ($prevMonth < 1) {
                            $prevMonth = 12;
                            $prevYear--;
                        }
                        
                        $nextMonth = $month + 1;
                        $nextYear = $year;
                        if ($nextMonth > 12) {
                            $nextMonth = 1;
                            $nextYear++;
                        }
                        ?>
                        <a href="?month=<?php echo $prevMonth; ?>&year=<?php echo $prevYear; ?>" 
                           class="px-5 py-2.5 bg-gray-50 text-gray-700 rounded-lg hover:bg-gray-100 
                                  transition-all duration-200 shadow-sm font-medium">
                            <i class="fas fa-chevron-left mr-1"></i> Previous
                        </a>
                        <a href="?month=<?php echo $nextMonth; ?>&year=<?php echo $nextYear; ?>" 
                           class="px-5 py-2.5 bg-gray-50 text-gray-700 rounded-lg hover:bg-gray-100 
                                  transition-all duration-200 shadow-sm font-medium">
                            Next <i class="fas fa-chevron-right ml-1"></i>
                        </a>
                    </div>
                </div>

                <!-- Add Event Button -->
                <div class="mb-6 p-6 flex justify-between items-center">
                    <button id="addEventBtn" 
                            class="px-5 py-2.5 bg-blue-500 text-white rounded-lg hover:bg-blue-600 
                                   transition-all duration-200 shadow-sm font-medium flex items-center">
                        <i class="fas fa-plus mr-2"></i> Add Event
                    </button>
                    <button id="optimizeBtn" 
                            class="px-5 py-2.5 bg-purple-500 text-white rounded-lg hover:bg-purple-600 
                                   transition-all duration-200 shadow-sm font-medium flex items-center">
                        <i class="fas fa-magic mr-2"></i> Optimize Schedule
                    </button>
                </div>

                <!-- Calendar Grid -->
                <div class="grid grid-cols-7 gap-4 p-6">
                    <?php
                    // Week days header
                    $weekDays = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
                    foreach ($weekDays as $day) {
                        echo "<div class='text-center font-semibold py-3 text-gray-600'>$day</div>";
                    }

                    // Fill in empty days from previous month
                    for ($i = 0; $i < $firstDayOfWeek; $i++) {
                        echo "<div class='p-4 rounded-lg bg-gray-50 opacity-50'></div>";
                    }

                    // Fill in days of current month
                    for ($day = 1; $day <= $numberDays; $day++) {
                        $currentDate = mktime(0, 0, 0, $month, $day, $year);
                        $dateStr = date('Y-m-d', $currentDate);
                        $isToday = date('Y-m-d') === $dateStr;
                        
                        // Count optimized events for this day
                        $dayEvents = array_filter($events, function($event) use ($dateStr) {
                            return date('Y-m-d', strtotime($event['start_date'])) === $dateStr;
                        });
                        
                        $totalEvents = count($dayEvents);
                        $optimizedEvents = count(array_filter($dayEvents, function($event) {
                            return isset($event['is_ai_optimized']) && $event['is_ai_optimized'] == 1;
                        }));
                        
                        $optimizedPercent = $totalEvents > 0 ? ($optimizedEvents / $totalEvents) * 100 : 0;
                        
                        echo "<div class='calendar-day p-3 rounded-lg border border-gray-100 hover:border-blue-200 relative' data-date='$dateStr'>
                                <div class='optimization-indicator' style='--optimized-percent: {$optimizedPercent}%'></div>
                                " . ($isToday ? "<div class='today-highlight'></div>" : "") . "
                                <span class='block text-center mb-2 font-medium " . ($isToday ? 'text-blue-600' : 'text-gray-700') . "'>$day</span>
                                <div class='space-y-1'>";
                        
                        // Display events for this day
                        foreach ($dayEvents as $event) {
                            $eventTitle = htmlspecialchars($event['title']);
                            $isOptimized = isset($event['is_ai_optimized']) && $event['is_ai_optimized'] == 1;
                            $isHumanAltered = isset($event['is_human_ai_altered']) && $event['is_human_ai_altered'] == 1;
                            $optimizedClass = $isOptimized ? 'ai-optimized' : ($isHumanAltered ? 'human-ai-altered' : '');
                            $eventTime = date('H:i', strtotime($event['start_date']));
                            
                            echo "<div class='event-pill px-2 py-1 rounded-md bg-blue-50 text-blue-700 
                                           border border-blue-100 cursor-pointer {$optimizedClass}' 
                                      data-event-id='{$event['id']}' 
                                      title='{$eventTitle} - {$eventTime}'>
                                    <i class='fas fa-circle text-[8px] mr-1 align-middle'></i>
                                    <span class='event-title'>{$eventTitle}</span>
                                    <span class='event-time'>{$eventTime}</span>
                                  </div>";
                        }
                        
                        echo "</div></div>";
                    }
                    ?>
                </div>
            </div>
        </div>
    </main>

    <script>
    // Ensure all event listeners are initialized after DOM load
    function initializeEventListeners() {
        const setupWizard = document.getElementById('setupWizard');
        const wizardSteps = document.querySelectorAll('.wizard-step');
        const prevButton = document.getElementById('prevStep');
        const nextButton = document.getElementById('nextStep');
        const skipButton = document.getElementById('skipSetup');
        const assistant = document.getElementById('calendarAssistant');
        const toggleButton = document.getElementById('toggleAssistant');
        const chatInput = document.getElementById('chatInput');
        const sendButton = document.getElementById('sendMessage');
        const messagesContainer = document.getElementById('chatMessages');
        let currentStep = 1;

        // Add optimize button handler
        const optimizeBtn = document.getElementById('optimizeBtn');
        if (optimizeBtn) {
            optimizeBtn.addEventListener('click', showOptimizeModal);
        }

        // Add event button handler
        const addEventBtn = document.getElementById('addEventBtn');
        if (addEventBtn) {
            addEventBtn.addEventListener('click', () => {
                // Event add logic will go here
            });
        }

        if (prevButton && nextButton) {
            prevButton.addEventListener('click', () => {
                if (currentStep > 1) {
                    currentStep--;
                    updateWizardStep();
                }
            });

            nextButton.addEventListener('click', async () => {
                // ...existing next button logic...
            });
        }

        if (skipButton) {
            skipButton.addEventListener('click', async () => {
                // ...existing skip button logic...
            });
        }

        if (toggleButton && assistant) {
            toggleButton.addEventListener('click', () => {
                assistant.classList.toggle('translate-x-0');
                assistant.classList.toggle('translate-x-full');
                toggleButton.querySelector('i').classList.toggle('fa-chevron-left');
                toggleButton.querySelector('i').classList.toggle('fa-chevron-right');
            });
        }

        if (sendButton && chatInput) {
            sendButton.addEventListener('click', () => {
                const message = chatInput.value.trim();
                if (message) {
                    sendMessage(message);
                    chatInput.value = '';
                }
            });

            chatInput.addEventListener('keypress', (e) => {
                if (e.key === 'Enter' && !e.shiftKey) {
                    e.preventDefault();
                    sendButton.click();
                }
            });
        }

        // Initialize event pills
        document.querySelectorAll('.event-pill').forEach(pill => {
            if (pill.classList.contains('human-ai-altered')) {
                pill.style.cursor = 'pointer';
                pill.addEventListener('click', function() {
                    showEventDetails(this.dataset.eventId);
                });
            }
        });
    }

    // Wait for DOM to be ready before initializing
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initializeEventListeners);
    } else {
        initializeEventListeners();
    }
    </script>
</body>
</html>