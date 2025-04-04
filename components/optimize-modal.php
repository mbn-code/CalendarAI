<?php
function renderOptimizeModal() {
    return <<<HTML
    <div id="optimizeModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50">
        <div class="relative top-20 mx-auto max-w-2xl bg-white rounded-xl shadow-lg p-6 transform transition-all">
            <h2 class="text-xl font-bold mb-4">Optimize Your Calendar</h2>
            <div id="daySelection" class="mb-6">
                <p class="text-gray-700 mb-3">Select specific days within the next month to optimize:</p>
                <div id="calendarDays" class="grid grid-cols-7 gap-2">
                    <!-- Days will be dynamically populated here -->
                </div>
            </div>
            <form id="preferencesForm" class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Preferred Study Time</label>
                    <select name="studyTime" class="w-full rounded-lg border-gray-300 shadow-sm">
                        <option value="morning">Morning (6AM-12PM)</option>
                        <option value="afternoon">Afternoon (12PM-5PM)</option>
                        <option value="evening">Evening (5PM-10PM)</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Break Duration (minutes)</label>
                    <input type="number" name="breakDuration" value="30" min="15" max="60" 
                           class="w-full rounded-lg border-gray-300 shadow-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Study Session Length (minutes)</label>
                    <input type="number" name="sessionLength" value="120" min="30" max="240" 
                           class="w-full rounded-lg border-gray-300 shadow-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Priority</label>
                    <select name="priority" class="w-full rounded-lg border-gray-300 shadow-sm">
                        <option value="deadlines">Prioritize Deadlines</option>
                        <option value="balanced">Balanced Schedule</option>
                        <option value="flexible">Flexible Learning</option>
                    </select>
                </div>
            </form>
            <div class="flex justify-end space-x-3 mt-6">
                <button type="button" onclick="closeOptimizeModal()" 
                        class="px-4 py-2 text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200">
                    Cancel
                </button>
                <button type="button" onclick="applyDayAndPreferenceSelection()" 
                        class="px-4 py-2 text-white bg-purple-500 rounded-lg hover:bg-purple-600">
                    Optimize
                </button>
            </div>
        </div>
    </div>
HTML;
}
?>
