<?php
function renderOptimizeModal() {
    return <<<HTML
    <div id="optimizeModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 overflow-auto">
        <div class="bg-white rounded-xl shadow-lg w-full max-w-3xl mx-auto my-8">
            <!-- Modal Header -->
            <div class="p-6 border-b">
                <h2 class="text-xl font-bold">Optimize Your Schedule</h2>
                <p class="text-gray-600 mt-2">Select optimization style and preferences</p>
            </div>

            <!-- Form Section -->
            <div id="preferencesForm" class="p-6">
                <!-- Optimization Presets -->
                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-700 mb-3">Choose Optimization Style:</label>
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                        <button type="button" onclick="selectOptimizationPreset('default')" 
                                class="preset-btn p-4 rounded-lg border-2 border-purple-200 hover:border-purple-500 text-center">
                            <i class="fas fa-magic text-2xl text-purple-500 mb-2"></i>
                            <span class="block text-sm font-medium">Default</span>
                            <span class="text-xs text-gray-500">Balanced optimization</span>
                        </button>
                        <button type="button" onclick="selectOptimizationPreset('busy_week')" 
                                class="preset-btn p-4 rounded-lg border-2 border-orange-200 hover:border-orange-500 text-center">
                            <i class="fas fa-calendar-week text-2xl text-orange-500 mb-2"></i>
                            <span class="block text-sm font-medium">Busy Week</span>
                            <span class="text-xs text-gray-500">High efficiency</span>
                        </button>
                        <button type="button" onclick="selectOptimizationPreset('conflicts')" 
                                class="preset-btn p-4 rounded-lg border-2 border-red-200 hover:border-red-500 text-center">
                            <i class="fas fa-exclamation-triangle text-2xl text-red-500 mb-2"></i>
                            <span class="block text-sm font-medium">Resolve Conflicts</span>
                            <span class="text-xs text-gray-500">Fix overlaps</span>
                        </button>
                        <button type="button" onclick="selectOptimizationPreset('optimized')" 
                                class="preset-btn p-4 rounded-lg border-2 border-green-200 hover:border-green-500 text-center">
                            <i class="fas fa-check-circle text-2xl text-green-500 mb-2"></i>
                            <span class="block text-sm font-medium">Best Practice</span>
                            <span class="text-xs text-gray-500">Ideal schedule</span>
                        </button>
                    </div>
                </div>

                <div id="daySelection" class="mb-6">
                    <p class="text-gray-700 mb-3">Select days to apply optimization:</p>
                    <div class="flex space-x-4 mb-4">
                        <button type="button" onclick="selectDayPreset('weekdays')" 
                                class="px-4 py-2 text-white bg-blue-500 rounded-lg hover:bg-blue-600">
                            Weekdays
                        </button>
                        <button type="button" onclick="selectDayPreset('weekends')" 
                                class="px-4 py-2 text-white bg-green-500 rounded-lg hover:bg-green-600">
                            Weekends
                        </button>
                        <button type="button" onclick="selectDayPreset('all')" 
                                class="px-4 py-2 text-white bg-purple-500 rounded-lg hover:bg-purple-600">
                            All Days
                        </button>
                    </div>
                    <div id="calendarDays" class="grid grid-cols-7 gap-2">
                        <!-- Days will be dynamically populated -->
                    </div>
                </div>

                <form class="space-y-4">
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
                    
                    <div class="flex items-center mt-4">
                        <input type="checkbox" id="autoApply" name="autoApply" class="h-5 w-5 rounded border-gray-300 text-purple-600 focus:ring-purple-500">
                        <label for="autoApply" class="ml-2 block text-sm text-gray-700">
                            Automatically apply changes immediately <span class="text-xs text-gray-500">(Skip preview)</span>
                        </label>
                    </div>
                </form>
            </div>

            <!-- Loading State -->
            <div id="optimizationLoading" class="hidden p-6">
                <div class="flex flex-col items-center">
                    <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-purple-500"></div>
                    <p class="mt-4 text-gray-600">Optimizing your schedule...</p>
                </div>
            </div>

            <!-- Results Section -->
            <div id="optimizationResults" class="hidden p-6">
                <div class="space-y-6">
                    <!-- AI Suggestions -->
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900 mb-2">AI Suggestions</h3>
                        <ul id="aiSuggestions" class="space-y-2 text-gray-700">
                            <!-- Will be populated dynamically -->
                        </ul>
                    </div>

                    <!-- Health Metrics -->
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900 mb-2">Schedule Health</h3>
                        <div id="optimizationStats" class="grid grid-cols-2 gap-4">
                            <!-- Will be populated dynamically -->
                        </div>
                    </div>

                    <!-- Proposed Changes -->
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900 mb-2">Proposed Changes</h3>
                        <div id="proposedChanges" class="space-y-3">
                            <!-- Will be populated dynamically -->
                        </div>
                    </div>
                </div>
            </div>

            <!-- Footer Actions -->
            <div class="p-6 border-t bg-gray-50 flex justify-between">
                <button onclick="modal.hide('optimizeModal')" 
                        class="px-4 py-2 text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200">
                    Cancel
                </button>
                <div class="space-x-2">
                    <button id="optimizeScheduleBtn" onclick="runOptimization()" 
                            class="px-4 py-2 text-white bg-purple-500 rounded-lg hover:bg-purple-600">
                        Start Optimization
                    </button>
                    <button id="applyChangesBtn" onclick="applySelectedChanges()" 
                            class="hidden px-4 py-2 text-white bg-green-500 rounded-lg hover:bg-green-600">
                        Apply Selected Changes
                    </button>
                </div>
            </div>
        </div>
    </div>
HTML;
}
?>
