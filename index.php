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
require_once __DIR__ . '/components/optimize-modal.php';
require_once __DIR__ . '/components/demo-controls.php';  // Add this line

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
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    screens: {
                        'xs': '480px', // Extra small screens
                    },
                    boxShadow: {
                        'vercel': '0 4px 12px rgba(0, 0, 0, 0.05), 0 1px 2px rgba(0, 0, 0, 0.1)',
                    }
                }
            }
        }
    </script>
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

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const optimizeBtn = document.getElementById('optimizeBtn');
            if (optimizeBtn) {
                optimizeBtn.addEventListener('click', initializeOptimizeModal);
            }
        });
    </script>

    <style>
        /* Base styles */
        html {
            scroll-behavior: smooth;
        }
        
        body {
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }
        
        /* Sidebar transitions */
        .sidebar-open {
            transform: translateX(0);
        }
        
        .sidebar-closed {
            transform: translateX(-100%);
        }
        
        @media (min-width: 768px) {
            .sidebar-closed {
                transform: translateX(0);
            }
        }
        
        /* Calendar day styling */
        .calendar-day {
            transition: all 0.2s ease-in-out;
            position: relative;
            overflow-y: auto;
            min-height: 90px;
            max-height: 300px;
            scrollbar-width: thin;
        }
        
        @media (min-width: 768px) {
            .calendar-day {
                min-height: 120px;
            }
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
        
        /* Event pill styling */
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
        
        /* Calendar indicators */
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
        
        /* Chat UI */
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
        
        /* Mobile optimizations */
        @media (max-width: 767px) {
            .calendar-grid-mobile {
                grid-template-columns: repeat(1, minmax(0, 1fr));
            }
            
            .event-pill {
                padding-right: 16px;
            }
            
            .event-pill::after {
                right: 2px;
                font-size: 8px;
            }
        }
        
        /* Custom animations */
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        
        .animate-fade-in {
            animation: fadeIn 0.3s ease-in-out;
        }
    </style>
</head>
<body class="bg-gray-50">
    <!-- Mobile menu toggle button -->
    <button id="mobileMenuToggle" class="md:hidden fixed top-4 left-4 z-50 p-2 bg-white rounded-lg shadow-vercel">
        <i class="fas fa-bars text-gray-700"></i>
    </button>
    
    <!-- Sidebar rendered with responsive classes -->
    <div id="sidebar" class="transition-transform duration-300 transform sidebar-closed md:sidebar-open">
        <?= renderSidebar('calendar') ?>
    </div>
    
    <?= renderNavbar() ?>
    
    <!-- Setup Wizard -->
    <?= renderSetupWizard() ?>
    
    <!-- Calendar Assistant -->
    <?= renderCalendarAssistant() ?>
    
    <!-- Render Optimize Modal -->
    <?= renderOptimizeModal() ?>
    
    <!-- Main Content - Responsive layout -->
    <main class="md:ml-64 pt-16 min-h-screen transition-all duration-300">
        <div class="p-4 md:p-6">
            <div class="bg-white rounded-xl shadow-vercel animate-fade-in">
                <!-- Calendar Header - Responsive -->
                <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 p-4 md:p-6 border-b">
                    <h1 class="text-2xl md:text-3xl font-bold text-gray-800 tracking-tight">
                        <?php echo date('F Y', $firstDayOfMonth); ?>
                    </h1>
                    <div class="flex gap-2 w-full sm:w-auto">
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
                           class="flex-1 sm:flex-none text-center px-3 sm:px-4 py-2 bg-gray-50 text-gray-700 rounded-lg hover:bg-gray-100 
                                  transition-all duration-200 shadow-sm text-sm font-medium">
                            <i class="fas fa-chevron-left mr-1"></i><span class="hidden xs:inline">Previous</span>
                        </a>
                        <a href="?month=<?php echo $nextMonth; ?>&year=<?php echo $nextYear; ?>" 
                           class="flex-1 sm:flex-none text-center px-3 sm:px-4 py-2 bg-gray-50 text-gray-700 rounded-lg hover:bg-gray-100 
                                  transition-all duration-200 shadow-sm text-sm font-medium">
                            <span class="hidden xs:inline">Next</span><i class="fas fa-chevron-right ml-1"></i>
                        </a>
                    </div>
                </div>

                <!-- Action Buttons - Responsive -->
                <div class="p-4 md:p-6 flex flex-wrap gap-3 justify-between items-center">
                    <button id="addEventBtn" 
                            class="w-full xs:w-auto px-4 py-2.5 bg-blue-500 text-white rounded-lg hover:bg-blue-600 
                                   transition-all duration-200 shadow-sm font-medium flex items-center justify-center">
                        <i class="fas fa-plus mr-2"></i> Add Event
                    </button>
                    <button id="optimizeBtn" 
                            class="w-full xs:w-auto px-4 py-2.5 bg-purple-500 text-white rounded-lg hover:bg-purple-600 
                                   transition-all duration-200 shadow-sm font-medium flex items-center justify-center">
                        <i class="fas fa-magic mr-2"></i><span class="hidden xs:inline">Optimize</span><span class="xs:hidden">Optimize Schedule</span>
                    </button>
                </div>

                <!-- Calendar View Controls - Mobile Only -->
                <div class="md:hidden px-4 pb-4 flex gap-2">
                    <button id="viewMonth" class="flex-1 py-2 bg-purple-50 text-purple-700 rounded-lg font-medium text-sm">
                        Month
                    </button>
                    <button id="viewWeek" class="flex-1 py-2 bg-gray-50 text-gray-600 hover:bg-gray-100 rounded-lg font-medium text-sm">
                        Week
                    </button>
                    <button id="viewDay" class="flex-1 py-2 bg-gray-50 text-gray-600 hover:bg-gray-100 rounded-lg font-medium text-sm">
                        Day
                    </button>
                </div>

                <!-- Calendar Grid - Responsive -->
                <div id="calendarGrid" class="grid grid-cols-1 sm:grid-cols-7 gap-2 md:gap-4 p-4 md:p-6">
                    <?php
                    // Week days header - Only visible on tablet and up
                    $weekDays = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
                    foreach ($weekDays as $day) {
                        echo "<div class='hidden sm:block text-center font-semibold py-2 md:py-3 text-gray-600'>$day</div>";
                    }

                    // Fill in empty days from previous month - Only visible on tablet and up
                    for ($i = 0; $i < $firstDayOfWeek; $i++) {
                        echo "<div class='hidden sm:block p-4 rounded-lg bg-gray-50 opacity-50'></div>";
                    }

                    // Fill in days of current month
                    for ($day = 1; $day <= $numberDays; $day++) {
                        $currentDate = mktime(0, 0, 0, $month, $day, $year);
                        $dateStr = date('Y-m-d', $currentDate);
                        $isToday = date('Y-m-d') === $dateStr;
                        $dayName = date('D', $currentDate); // Get day name for mobile view
                        
                        // Count optimized events for this day
                        $dayEvents = array_filter($events, function($event) use ($dateStr) {
                            return date('Y-m-d', strtotime($event['start_date'])) === $dateStr;
                        });
                        
                        $totalEvents = count($dayEvents);
                        $optimizedEvents = count(array_filter($dayEvents, function($event) {
                            return isset($event['is_ai_optimized']) && $event['is_ai_optimized'] == 1;
                        }));
                        
                        $optimizedPercent = $totalEvents > 0 ? ($optimizedEvents / $totalEvents) * 100 : 0;
                        
                        // Mobile view includes day name
                        $dayHeader = "<div class='flex justify-between items-center mb-2'>" .
                                     "<span class='sm:hidden font-medium text-gray-600'>$dayName</span>" .
                                     "<span class='block text-center font-medium " . ($isToday ? 'text-blue-600' : 'text-gray-700') . "'>$day</span>" .
                                     "</div>";
                        
                        echo "<div class='calendar-day p-2 md:p-3 rounded-lg border border-gray-100 hover:border-blue-200 relative " . 
                             ($isToday ? 'border-blue-300 bg-blue-50/30' : '') . "' data-date='$dateStr'>
                                <div class='optimization-indicator' style='--optimized-percent: {$optimizedPercent}%'></div>
                                " . ($isToday ? "<div class='today-highlight'></div>" : "") . 
                                $dayHeader .
                                "<div class='space-y-1'>";
                        
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
                        
                        if (count($dayEvents) === 0) {
                            echo "<div class='text-xs text-gray-400 italic text-center py-2'>No events</div>";
                        }
                        
                        echo "</div></div>";
                    }
                    ?>
                </div>
            </div>
        </div>
        
        <?php
        // Add demo controls at the end of the main content
        if (isset($_SESSION['is_admin']) && $_SESSION['is_admin']) {
            echo renderDemoControls();
        }
        ?>
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

        // Mobile menu toggle
        const mobileMenuToggle = document.getElementById('mobileMenuToggle');
        const sidebar = document.getElementById('sidebar');
        
        if (mobileMenuToggle && sidebar) {
            mobileMenuToggle.addEventListener('click', () => {
                sidebar.classList.toggle('sidebar-closed');
                sidebar.classList.toggle('sidebar-open');
                
                // Update toggle icon
                const icon = mobileMenuToggle.querySelector('i');
                if (icon) {
                    icon.classList.toggle('fa-bars');
                    icon.classList.toggle('fa-times');
                }
            });
        }
        
        // Mobile view switcher
        const viewMonth = document.getElementById('viewMonth');
        const viewWeek = document.getElementById('viewWeek');
        const viewDay = document.getElementById('viewDay');
        const calendarGrid = document.getElementById('calendarGrid');
        
        if (viewMonth && viewWeek && viewDay && calendarGrid) {
            viewMonth.addEventListener('click', () => {
                viewMonth.classList.add('bg-purple-50', 'text-purple-700');
                viewMonth.classList.remove('bg-gray-50', 'text-gray-600');
                viewWeek.classList.remove('bg-purple-50', 'text-purple-700');
                viewWeek.classList.add('bg-gray-50', 'text-gray-600');
                viewDay.classList.remove('bg-purple-50', 'text-purple-700');
                viewDay.classList.add('bg-gray-50', 'text-gray-600');
                
                // Show all days
                document.querySelectorAll('.calendar-day').forEach(day => {
                    day.style.display = 'block';
                });
                calendarGrid.classList.remove('calendar-grid-mobile');
            });
            
            viewWeek.addEventListener('click', () => {
                viewMonth.classList.remove('bg-purple-50', 'text-purple-700');
                viewMonth.classList.add('bg-gray-50', 'text-gray-600');
                viewWeek.classList.add('bg-purple-50', 'text-purple-700');
                viewWeek.classList.remove('bg-gray-50', 'text-gray-600');
                viewDay.classList.remove('bg-purple-50', 'text-purple-700');
                viewDay.classList.add('bg-gray-50', 'text-gray-600');
                
                // Only show current week
                const today = new Date();
                const currentWeekStart = new Date(today);
                currentWeekStart.setDate(today.getDate() - today.getDay());
                
                document.querySelectorAll('.calendar-day').forEach(day => {
                    const dayDate = new Date(day.dataset.date);
                    const dayOfWeek = dayDate.getDay();
                    const weekStart = new Date(dayDate);
                    weekStart.setDate(dayDate.getDate() - dayOfWeek);
                    
                    if (weekStart.toDateString() === currentWeekStart.toDateString()) {
                        day.style.display = 'block';
                    } else {
                        day.style.display = 'none';
                    }
                });
                
                calendarGrid.classList.add('calendar-grid-mobile');
            });
            
            viewDay.addEventListener('click', () => {
                viewMonth.classList.remove('bg-purple-50', 'text-purple-700');
                viewMonth.classList.add('bg-gray-50', 'text-gray-600');
                viewWeek.classList.remove('bg-purple-50', 'text-purple-700');
                viewWeek.classList.add('bg-gray-50', 'text-gray-600');
                viewDay.classList.add('bg-purple-50', 'text-purple-700');
                viewDay.classList.remove('bg-gray-50', 'text-gray-600');
                
                // Only show today
                const today = new Date().toISOString().split('T')[0];
                
                document.querySelectorAll('.calendar-day').forEach(day => {
                    if (day.dataset.date === today) {
                        day.style.display = 'block';
                    } else {
                        day.style.display = 'none';
                    }
                });
                
                calendarGrid.classList.add('calendar-grid-mobile');
            });
        }

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
            pill.addEventListener('click', function() {
                showEventDetails(this.dataset.eventId);
            });
        });
        
        // Handle click outside sidebar on mobile to close it
        document.addEventListener('click', (e) => {
            const isMobile = window.innerWidth < 768;
            if (isMobile && sidebar && sidebar.classList.contains('sidebar-open')) {
                const isClickInsideSidebar = sidebar.contains(e.target);
                const isClickOnToggle = mobileMenuToggle && mobileMenuToggle.contains(e.target);
                
                if (!isClickInsideSidebar && !isClickOnToggle) {
                    sidebar.classList.remove('sidebar-open');
                    sidebar.classList.add('sidebar-closed');
                    
                    // Update toggle icon
                    const icon = mobileMenuToggle.querySelector('i');
                    if (icon) {
                        icon.classList.remove('fa-times');
                        icon.classList.add('fa-bars');
                    }
                }
            }
        });
        
        // Handle window resize to toggle mobile/desktop view
        window.addEventListener('resize', () => {
            if (window.innerWidth >= 768) {
                if (sidebar) {
                    sidebar.classList.remove('sidebar-closed');
                    sidebar.classList.add('sidebar-open');
                }
            }
        });
    }

    // Show event details when clicking on an event
    function showEventDetails(eventId) {
        if (!eventId) return;
        
        fetch(`/CalendarAI/api/get-event.php?id=${eventId}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const event = data.event;
                    const isOptimized = event.is_ai_optimized == 1;
                    const isHumanAltered = event.is_human_ai_altered == 1;
                    
                    let badgeHtml = '';
                    if (isOptimized) {
                        badgeHtml = '<span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-purple-100 text-purple-800 ml-2">✨ AI Optimized</span>';
                    } else if (isHumanAltered) {
                        badgeHtml = '<span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-green-100 text-green-800 ml-2">👤 Human Adjusted</span>';
                    }
                    
                    Swal.fire({
                        title: event.title + badgeHtml,
                        html: `
                            <div class="text-left">
                                <p class="mb-2"><strong>Date:</strong> ${new Date(event.start_date).toLocaleDateString()}</p>
                                <p class="mb-2"><strong>Time:</strong> ${new Date(event.start_date).toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'})}</p>
                                <p class="mb-4"><strong>Description:</strong> ${event.description || 'No description'}</p>
                                <div class="border-t pt-4">
                                    <p class="text-sm text-gray-500">Created on ${new Date(event.created_at).toLocaleDateString()}</p>
                                </div>
                            </div>
                        `,
                        confirmButtonText: 'Close',
                        showCancelButton: true,
                        cancelButtonText: 'Edit',
                        customClass: {
                            confirmButton: 'bg-purple-500 text-white px-4 py-2 rounded-lg hover:bg-purple-600',
                            cancelButton: 'bg-gray-200 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-300'
                        },
                        buttonsStyling: false
                    }).then((result) => {
                        if (result.dismiss === Swal.DismissReason.cancel) {
                            // Handle edit logic
                        }
                    });
                } else {
                    showNotification('Failed to load event details: ' + data.error, 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showNotification('Failed to load event details', 'error');
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