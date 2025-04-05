<?php
/**
 * Debug Sidebar Component
 * This sidebar displays logs and debugging information for developers
 */

function renderDebugSidebar($active_logs = []) {
    // Only show in debug mode
    if (!defined('DEBUG') || DEBUG !== true) {
        return '';
    }

    // Read the latest logs
    $log_file = __DIR__ . '/../logs/debug.log';
    $logs = [];
    $log_types = [
        'error' => [],
        'warning' => [],
        'debug' => [],
        'info' => []
    ];
    
    if (file_exists($log_file)) {
        $log_content = file_get_contents($log_file);
        $log_lines = explode("\n", $log_content);
        $log_lines = array_filter($log_lines);
        
        // Process only the most recent 100 log entries
        $log_lines = array_slice($log_lines, -100);
        
        foreach ($log_lines as $line) {
            if (empty(trim($line))) continue;
            
            $type = 'debug';
            if (strpos($line, 'ERROR') !== false || strpos($line, 'Fatal error') !== false || strpos($line, 'Exception') !== false) {
                $type = 'error';
            } elseif (strpos($line, 'WARNING') !== false || strpos($line, 'Warning') !== false) {
                $type = 'warning';
            } elseif (strpos($line, 'INFO') !== false) {
                $type = 'info';
            }
            
            // Add to type-specific array
            $log_types[$type][] = $line;
            
            // Add to general logs array
            $logs[] = [
                'type' => $type,
                'content' => htmlspecialchars($line)
            ];
        }
    }
    
    // Reverse to show newest first
    $logs = array_reverse($logs);
    
    // Count log entries by type
    $counts = [
        'error' => count($log_types['error']),
        'warning' => count($log_types['warning']),
        'debug' => count($log_types['debug']),
        'info' => count($log_types['info']),
        'total' => count($logs)
    ];
    
    // Populate active tab if not specified
    if (empty($active_logs)) {
        $active_logs = $counts['error'] > 0 ? 'error' : 'all';
    }
    
    ob_start();
    ?>
    <div id="debugSidebar" class="fixed left-0 top-0 bottom-0 bg-gray-800 text-white w-64 transform -translate-x-64 transition-transform duration-300 ease-in-out z-50 flex flex-col">
        <div class="bg-gray-900 p-4 flex justify-between items-center border-b border-gray-700">
            <h2 class="text-lg font-semibold flex items-center">
                <i class="fas fa-bug mr-2"></i> Debug Console
            </h2>
            <button id="closeDebugSidebar" class="text-gray-400 hover:text-white">
                <i class="fas fa-times"></i>
            </button>
        </div>
        
        <div class="p-2 bg-gray-700 flex space-x-1 text-xs overflow-x-auto">
            <button class="debug-tab px-2 py-1 rounded <?= $active_logs === 'all' ? 'bg-gray-900 text-white' : 'bg-gray-600 text-gray-300' ?>" 
                    data-tab="all">All (<?= $counts['total'] ?>)</button>
            <button class="debug-tab px-2 py-1 rounded <?= $active_logs === 'error' ? 'bg-gray-900 text-white' : 'bg-gray-600 text-gray-300' ?>" 
                    data-tab="error">Errors (<?= $counts['error'] ?>)</button>
            <button class="debug-tab px-2 py-1 rounded <?= $active_logs === 'warning' ? 'bg-gray-900 text-white' : 'bg-gray-600 text-gray-300' ?>" 
                    data-tab="warning">Warnings (<?= $counts['warning'] ?>)</button>
            <button class="debug-tab px-2 py-1 rounded <?= $active_logs === 'debug' ? 'bg-gray-900 text-white' : 'bg-gray-600 text-gray-300' ?>" 
                    data-tab="debug">Debug (<?= $counts['debug'] ?>)</button>
            <button class="debug-tab px-2 py-1 rounded <?= $active_logs === 'info' ? 'bg-gray-900 text-white' : 'bg-gray-600 text-gray-300' ?>" 
                    data-tab="info">Info (<?= $counts['info'] ?>)</button>
        </div>
        
        <div class="flex-grow overflow-auto bg-gray-800 p-1">
            <div class="debug-content <?= $active_logs === 'all' ? '' : 'hidden' ?>" data-content="all">
                <?php if (empty($logs)): ?>
                    <div class="text-gray-500 italic text-sm p-4 text-center">No logs found</div>
                <?php else: ?>
                    <?php foreach ($logs as $log): ?>
                        <div class="text-xs font-mono p-1 mb-1 rounded 
                                    <?= $log['type'] === 'error' ? 'bg-red-900/30 text-red-200' : '' ?>
                                    <?= $log['type'] === 'warning' ? 'bg-yellow-900/30 text-yellow-200' : '' ?>
                                    <?= $log['type'] === 'debug' ? 'bg-gray-700/50 text-gray-200' : '' ?>
                                    <?= $log['type'] === 'info' ? 'bg-blue-900/30 text-blue-200' : '' ?>">
                            <?= $log['content'] ?>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            
            <?php foreach (['error', 'warning', 'debug', 'info'] as $type): ?>
                <div class="debug-content hidden" data-content="<?= $type ?>">
                    <?php if (empty($log_types[$type])): ?>
                        <div class="text-gray-500 italic text-sm p-4 text-center">No <?= $type ?> logs found</div>
                    <?php else: ?>
                        <?php foreach (array_reverse($log_types[$type]) as $log_line): ?>
                            <div class="text-xs font-mono p-1 mb-1 rounded 
                                        <?= $type === 'error' ? 'bg-red-900/30 text-red-200' : '' ?>
                                        <?= $type === 'warning' ? 'bg-yellow-900/30 text-yellow-200' : '' ?>
                                        <?= $type === 'debug' ? 'bg-gray-700/50 text-gray-200' : '' ?>
                                        <?= $type === 'info' ? 'bg-blue-900/30 text-blue-200' : '' ?>">
                                <?= htmlspecialchars($log_line) ?>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
        
        <div class="bg-gray-900 p-3 border-t border-gray-700">
            <div class="flex justify-between items-center">
                <button id="clearLogs" class="text-xs px-2 py-1 bg-gray-700 hover:bg-gray-600 rounded text-gray-200">
                    Clear Logs
                </button>
                <button id="refreshLogs" class="text-xs px-2 py-1 bg-gray-700 hover:bg-gray-600 rounded text-gray-200">
                    <i class="fas fa-sync-alt mr-1"></i> Refresh
                </button>
                <button id="downloadLogs" class="text-xs px-2 py-1 bg-gray-700 hover:bg-gray-600 rounded text-gray-200">
                    <i class="fas fa-download mr-1"></i> Export
                </button>
            </div>
            <div class="mt-3 flex items-center">
                <label class="text-xs mr-2">Auto-refresh:</label>
                <select id="refreshInterval" class="bg-gray-700 text-gray-200 text-xs p-1 rounded border border-gray-600">
                    <option value="0">Off</option>
                    <option value="5">5s</option>
                    <option value="10">10s</option>
                    <option value="30">30s</option>
                    <option value="60">60s</option>
                </select>
            </div>
        </div>
    </div>
    
    <div id="toggleDebugSidebar" class="fixed top-4 left-4 bg-gray-800 text-white p-2 rounded-full shadow-lg cursor-pointer hover:bg-gray-700 z-50">
        <i class="fas fa-bug"></i>
    </div>
    
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Debug sidebar toggle
        const debugSidebar = document.getElementById('debugSidebar');
        const toggleDebug = document.getElementById('toggleDebugSidebar');
        const closeDebug = document.getElementById('closeDebugSidebar');
        
        toggleDebug.addEventListener('click', function() {
            debugSidebar.classList.toggle('-translate-x-64');
        });
        
        closeDebug.addEventListener('click', function() {
            debugSidebar.classList.add('-translate-x-64');
        });
        
        // Tab switching
        document.querySelectorAll('.debug-tab').forEach(tab => {
            tab.addEventListener('click', function() {
                // Update active tab styling
                document.querySelectorAll('.debug-tab').forEach(t => {
                    t.classList.remove('bg-gray-900', 'text-white');
                    t.classList.add('bg-gray-600', 'text-gray-300');
                });
                
                this.classList.remove('bg-gray-600', 'text-gray-300');
                this.classList.add('bg-gray-900', 'text-white');
                
                // Show correct content
                const tabType = this.getAttribute('data-tab');
                document.querySelectorAll('.debug-content').forEach(content => {
                    content.classList.add('hidden');
                });
                document.querySelector(`.debug-content[data-content="${tabType}"]`).classList.remove('hidden');
            });
        });
        
        // Clear logs
        document.getElementById('clearLogs').addEventListener('click', function() {
            fetch('/CalendarAI/api/clear-logs.php', {
                method: 'POST'
            }).then(response => response.json())
              .then(data => {
                if (data.success) {
                    document.querySelectorAll('.debug-content').forEach(content => {
                        content.innerHTML = '<div class="text-gray-500 italic text-sm p-4 text-center">Logs cleared</div>';
                    });
                } else {
                    console.error('Failed to clear logs:', data.error);
                }
              });
        });
        
        // Refresh logs
        document.getElementById('refreshLogs').addEventListener('click', function() {
            location.reload();
        });
        
        // Download logs
        document.getElementById('downloadLogs').addEventListener('click', function() {
            window.open('/CalendarAI/api/download-logs.php', '_blank');
        });
        
        // Auto-refresh
        const refreshSelect = document.getElementById('refreshInterval');
        let refreshTimer;
        
        refreshSelect.addEventListener('change', function() {
            const seconds = parseInt(this.value, 10);
            
            // Clear existing timer
            if (refreshTimer) {
                clearInterval(refreshTimer);
                refreshTimer = null;
            }
            
            // Set new timer if not "Off"
            if (seconds > 0) {
                refreshTimer = setInterval(() => {
                    location.reload();
                }, seconds * 1000);
            }
        });
        
        // Save to local storage for persistence
        if (localStorage.getItem('debugSidebarOpen') === 'true') {
            debugSidebar.classList.remove('-translate-x-64');
        }
        
        toggleDebug.addEventListener('click', function() {
            const isOpen = !debugSidebar.classList.contains('-translate-x-64');
            localStorage.setItem('debugSidebarOpen', isOpen);
        });
    });
    </script>
    <?php
    return ob_get_clean();
}
?>