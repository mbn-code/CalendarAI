<?php
function renderDemoControls() {
    ?>
    <div id="demoControls" class="fixed bottom-4 right-4 z-50">
        <button onclick="toggleDemoPanel()" class="bg-purple-600 text-white p-2 rounded-full shadow-lg hover:bg-purple-700">
            <i class="fas fa-tools"></i>
        </button>
        
        <div id="demoPanel" class="hidden absolute bottom-12 right-0 bg-white rounded-lg shadow-xl p-4 w-64">
            <h3 class="text-lg font-semibold mb-3">Demo Controls</h3>
            
            <div class="space-y-2">
                <button onclick="loadPreset('default')" 
                        class="w-full text-left px-3 py-2 rounded hover:bg-gray-100">
                    🔄 Load Default State
                </button>
                <button onclick="loadPreset('busy_week')" 
                        class="w-full text-left px-3 py-2 rounded hover:bg-gray-100">
                    📅 Load Busy Week
                </button>
                <button onclick="loadPreset('conflicts')" 
                        class="w-full text-left px-3 py-2 rounded hover:bg-gray-100">
                    ⚠️ Load Schedule Conflicts
                </button>
                <button onclick="loadPreset('optimized')" 
                        class="w-full text-left px-3 py-2 rounded hover:bg-gray-100">
                    ✨ Load Optimized Schedule
                </button>
                <hr class="my-2">
                <button onclick="resetCalendar()" 
                        class="w-full text-left px-3 py-2 rounded hover:bg-gray-100 text-red-600">
                    🗑️ Reset Calendar
                </button>
            </div>
        </div>
    </div>

    <script>
    function toggleDemoPanel() {
        const panel = document.getElementById('demoPanel');
        panel.classList.toggle('hidden');
    }

    async function loadPreset(preset) {
        try {
            // Remove existing preset classes from all events
            document.querySelectorAll('.event-pill').forEach(pill => {
                pill.classList.remove('preset-default', 'preset-busy', 'preset-conflicts', 'preset-optimized');
            });

            const response = await fetch('/CalendarAI/api/demo/load-state.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `preset=${preset}`
            });
            
            const data = await response.json();
            if (data.success) {
                // Add appropriate preset class based on the loaded preset
                document.querySelectorAll('.event-pill.ai-optimized').forEach(pill => {
                    switch(preset) {
                        case 'busy_week':
                            pill.classList.add('preset-busy');
                            break;
                        case 'conflicts':
                            pill.classList.add('preset-conflicts');
                            break;
                        case 'optimized':
                            pill.classList.add('preset-optimized');
                            break;
                        default:
                            pill.classList.add('preset-default');
                    }
                });

                showNotification(data.message, 'success');
                location.reload();
            } else {
                throw new Error(data.error);
            }
        } catch (error) {
            showNotification(error.message, 'error');
        }
    }

    async function resetCalendar() {
        if (!confirm('Are you sure you want to reset the calendar? This will remove all events.')) {
            return;
        }
        
        try {
            const response = await fetch('/CalendarAI/api/demo/reset-data.php', {
                method: 'POST'
            });
            
            const data = await response.json();
            if (data.success) {
                // Remove all preset classes when resetting
                document.querySelectorAll('.event-pill').forEach(pill => {
                    pill.classList.remove('preset-default', 'preset-busy', 'preset-conflicts', 'preset-optimized');
                });

                showNotification(data.message, 'success');
                location.reload();
            } else {
                throw new Error(data.error);
            }
        } catch (error) {
            showNotification(error.message, 'error');
        }
    }
    </script>
    <?php
}