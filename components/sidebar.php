<?php
function renderSidebar($activePage = 'calendar') {
    $dashboardClass = $activePage === 'dashboard' ? 'bg-blue-50 text-blue-600' : 'text-gray-700';
    $calendarClass = $activePage === 'calendar' ? 'bg-blue-50 text-blue-600' : 'text-gray-700';
    $eventsClass = $activePage === 'events' ? 'bg-blue-50 text-blue-600' : 'text-gray-700';
    $categoriesClass = $activePage === 'categories' ? 'bg-blue-50 text-blue-600' : 'text-gray-700';
    
    return <<<HTML
    <div class="fixed left-0 top-0 h-full w-64 bg-white shadow-lg z-30">
        <div class="p-4 border-b">
            <h1 class="text-xl font-bold text-gray-800">Calendar Pro</h1>
        </div>
        <nav class="p-4">
            <ul class="space-y-2">
                <li>
                    <a href="/calendar/dashboard.php" 
                       class="flex items-center p-3 rounded-lg hover:bg-gray-50 {$dashboardClass}">
                        <i class="fas fa-home w-5"></i>
                        <span class="ml-3">Dashboard</span>
                    </a>
                </li>
                <li>
                    <a href="/calendar/index.php" 
                       class="flex items-center p-3 rounded-lg hover:bg-gray-50 {$calendarClass}">
                        <i class="fas fa-calendar w-5"></i>
                        <span class="ml-3">Calendar</span>
                    </a>
                </li>
                <li>
                    <a href="/calendar/events.php" 
                       class="flex items-center p-3 rounded-lg hover:bg-gray-50 {$eventsClass}">
                        <i class="fas fa-calendar-check w-5"></i>
                        <span class="ml-3">Events</span>
                    </a>
                </li>
                <li>
                    <a href="/calendar/categories.php" 
                       class="flex items-center p-3 rounded-lg hover:bg-gray-50 {$categoriesClass}">
                        <i class="fas fa-tags w-5"></i>
                        <span class="ml-3">Categories</span>
                    </a>
                </li>
            </ul>
        </nav>
    </div>
HTML;
}
?>
