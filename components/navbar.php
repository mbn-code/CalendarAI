<?php
function renderNavbar() {
    $username = $_SESSION['username'] ?? 'Guest';
    $isAuthenticated = isset($_SESSION['user_id']);
    $userId = $_SESSION['user_id'] ?? 0; // Get user ID safely here
    
    $html = <<<EOT
    <nav class="fixed top-0 left-64 right-0 bg-white border-b z-20 h-16">
        <div class="h-full px-6 flex items-center justify-between">
            <div class="relative w-96">
                <input type="text" 
                       placeholder="Search events..." 
                       class="w-full pl-10 pr-4 py-2 rounded-lg border border-gray-200 focus:border-blue-500 focus:ring-1 focus:ring-blue-500">
                <i class="fas fa-search absolute left-3 top-3 text-gray-400"></i>
            </div>
            <div class="flex items-center space-x-4">
                <div class="relative">
                    <button id="settingsButton" 
                            class="p-2 hover:bg-gray-100 rounded-lg" 
                            title="Settings"
                            onclick="document.getElementById('settingsDropdown').classList.toggle('hidden')">
                        <i class="fas fa-cog text-gray-600"></i>
                    </button>
                    <div id="settingsDropdown" class="absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-lg py-1 hidden">
                        <div class="px-4 py-2 border-b">
                            <p class="text-sm font-semibold text-gray-700">Settings</p>
                        </div>
                        <div class="py-1">
                            <a href="#" onclick="event.preventDefault(); showOptimizeModal()" class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                <i class="fas fa-magic w-5"></i>
                                <span>Optimize Schedule</span>
                            </a>
                            <a href="#" onclick="event.preventDefault(); resetPreferences()" class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                <i class="fas fa-redo-alt w-5"></i>
                                <span>Reset Preferences</span>
                            </a>
EOT;
    if ($isAuthenticated) {
        $html .= <<<EOT
                            <a href="/CalendarAI/frontend/pages/auth/logout.php" class="flex items-center px-4 py-2 text-sm text-red-600 hover:bg-gray-100">
                                <i class="fas fa-sign-out-alt w-5"></i>
                                <span>Logout</span>
                            </a>
EOT;
    }
    $html .= <<<EOT
                        </div>
                    </div>
                </div>
                <button class="p-2 hover:bg-gray-100 rounded-lg">
                    <i class="fas fa-bell text-gray-600"></i>
                </button>
                <div class="flex items-center space-x-2">
                    <img src="https://ui-avatars.com/api/?name={$username}" class="w-8 h-8 rounded-full">
                    <span class="text-sm font-medium text-gray-700">{$username}</span>
                </div>
            </div>
        </div>
    </nav>

    <script>
    // Close dropdown when clicking outside
    document.addEventListener('click', function(event) {
        const dropdown = document.getElementById('settingsDropdown');
        const settingsButton = document.getElementById('settingsButton');
        
        if (!settingsButton.contains(event.target) && !dropdown.contains(event.target)) {
            dropdown.classList.add('hidden');
        }
    });

    function resetPreferences() {
        document.getElementById('settingsDropdown').classList.add('hidden');
        
        if (confirm('Are you sure you want to reset your calendar preferences? This will reset your AI assistant settings as well.')) {
            fetch('/CalendarAI/api/save-preferences.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    userId: {$userId},
                    preferences: { priorityMode: 'balanced' },
                    isBasicSetup: true
                })
            }).then(response => response.json())
              .then(data => {
                if (data.success) {
                    showNotification('Preferences have been reset successfully', 'success');
                    setTimeout(() => location.reload(), 1500);
                } else {
                    showNotification('Failed to reset preferences: ' + data.error, 'error');
                }
            }).catch(error => {
                console.error('Error:', error);
                showNotification('Failed to reset preferences', 'error');
            });
        }
    }

    function showCalendarAssistant() {
        document.getElementById('settingsDropdown').classList.add('hidden');
        const assistant = document.getElementById('calendarAssistant');
        if (assistant) {
            assistant.classList.remove('translate-x-full');
        }
    }
    </script>
EOT;
    return $html;
}
?>