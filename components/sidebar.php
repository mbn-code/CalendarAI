<?php
function renderSidebar($active = 'calendar') {
    $isAuthenticated = isset($_SESSION['user_id']);
    $userRole = $_SESSION['user_role'] ?? '';
    
    // Pre-calculate active classes
    $calendarClass = $active === 'calendar' ? 'bg-purple-50 text-purple-700' : 'text-gray-700 hover:bg-gray-50';
    $dashboardClass = $active === 'dashboard' ? 'bg-purple-50 text-purple-700' : 'text-gray-700 hover:bg-gray-50';
    $settingsClass = $active === 'settings' ? 'bg-purple-50 text-purple-700' : 'text-gray-700 hover:bg-gray-50';
    
    $html = <<<EOT
    <aside class="fixed left-0 top-0 w-64 h-screen bg-white border-r">
        <div class="flex flex-col h-full">
            <div class="p-6 border-b">
                <h1 class="text-2xl font-bold text-purple-600">CalendarAI</h1>
            </div>
            
            <nav class="flex-1 p-4 space-y-2">
                <a href="/CalendarAI/index.php" 
                   class="flex items-center px-4 py-2.5 text-sm font-medium rounded-lg {$calendarClass}">
                    <i class="fas fa-calendar-alt w-5"></i>
                    <span class="ml-3">Calendar</span>
                </a>

                <a href="/CalendarAI/dashboard.php" 
                   class="flex items-center px-4 py-2.5 text-sm font-medium rounded-lg {$dashboardClass}">
                    <i class="fas fa-chart-bar w-5"></i>
                    <span class="ml-3">Dashboard</span>
                </a>
EOT;

    if ($userRole === 'admin') {
        $html .= <<<EOT
                <a href="/CalendarAI/admin/settings.php" 
                   class="flex items-center px-4 py-2.5 text-sm font-medium rounded-lg {$settingsClass}">
                    <i class="fas fa-cogs w-5"></i>
                    <span class="ml-3">Admin Settings</span>
                </a>
EOT;
    }

    $html .= <<<EOT
            </nav>
            
            <div class="p-4 border-t">
EOT;

    if ($isAuthenticated) {
        $html .= <<<EOT
                <a href="/CalendarAI/frontend/pages/profile/profile.php" 
                   class="flex items-center px-4 py-2.5 text-sm font-medium text-gray-700 rounded-lg hover:bg-gray-50">
                    <i class="fas fa-user-circle w-5"></i>
                    <span class="ml-3">My Profile</span>
                </a>
                <a href="/CalendarAI/frontend/pages/auth/logout.php" 
                   class="flex items-center px-4 py-2.5 text-sm font-medium text-red-600 rounded-lg hover:bg-red-50">
                    <i class="fas fa-sign-out-alt w-5"></i>
                    <span class="ml-3">Logout</span>
                </a>
EOT;
    } else {
        $html .= <<<EOT
                <a href="/CalendarAI/frontend/pages/auth/login/login.php" 
                   class="flex items-center px-4 py-2.5 text-sm font-medium text-purple-600 rounded-lg hover:bg-purple-50">
                    <i class="fas fa-sign-in-alt w-5"></i>
                    <span class="ml-3">Login</span>
                </a>
EOT;
    }

    $html .= <<<EOT
            </div>
        </div>
    </aside>
EOT;

    return $html;
}
?>
