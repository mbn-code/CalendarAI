<?php
function renderSetupWizard() {
    return <<<HTML
    <div id="setupWizard" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50">
        <div class="relative top-10 mx-auto max-w-3xl bg-white rounded-xl shadow-lg p-6">
            <h2 class="text-2xl font-bold mb-6">Calendar Assistant Setup</h2>
            
            <!-- Progress Dots -->
            <div id="wizardSteps" class="flex justify-center space-x-2 mb-8">
                <div class="w-3 h-3 rounded-full bg-purple-600"></div>
                <div class="w-3 h-3 rounded-full bg-gray-300"></div>
                <div class="w-3 h-3 rounded-full bg-gray-300"></div>
                <div class="w-3 h-3 rounded-full bg-gray-300"></div>
            </div>
            
            <!-- Info about skipping -->
            <div class="bg-blue-50 border border-blue-100 rounded-lg p-4 mb-6">
                <p class="text-sm text-blue-700 flex items-center">
                    <i class="fas fa-info-circle mr-2"></i>
                    You can skip the setup and use basic calendar assistant features. Setting up preferences will enable more personalized assistance.
                </p>
            </div>

            <!-- Wizard Steps -->
            <div class="wizard-steps space-y-6">
                <!-- Step 1: Focus and Chill Times -->
                <div class="wizard-step" data-step="1">
                    <h3 class="text-lg font-semibold mb-4">When are you most productive?</h3>
                    <div class="grid grid-cols-2 gap-6">
                        <div class="space-y-4">
                            <label class="block">
                                <span class="text-gray-700">Focus Start Time</span>
                                <input type="time" id="focusStartTime" value="09:00" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                            </label>
                            <label class="block">
                                <span class="text-gray-700">Focus End Time</span>
                                <input type="time" id="focusEndTime" value="17:00" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                            </label>
                        </div>
                        <div class="space-y-4">
                            <label class="block">
                                <span class="text-gray-700">Chill Start Time</span>
                                <input type="time" id="chillStartTime" value="17:00" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                            </label>
                            <label class="block">
                                <span class="text-gray-700">Chill End Time</span>
                                <input type="time" id="chillEndTime" value="22:00" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                            </label>
                        </div>
                    </div>
                </div>
                
                <!-- Step 2: Break and Session Preferences -->
                <div class="wizard-step hidden" data-step="2">
                    <h3 class="text-lg font-semibold mb-4">Study and Break Preferences</h3>
                    <div class="space-y-6">
                        <div class="space-y-2">
                            <label class="block text-gray-700">Break Duration (minutes)</label>
                            <div class="flex items-center gap-3">
                                <input type="range" id="breakDuration" min="5" max="30" step="5" value="15" 
                                       class="flex-1" oninput="this.nextElementSibling.value = this.value + ' min'">
                                <output class="text-gray-600 w-16">15 min</output>
                            </div>
                        </div>
                        <div class="space-y-2">
                            <label class="block text-gray-700">Study Session Length (minutes)</label>
                            <div class="flex items-center gap-3">
                                <input type="range" id="sessionLength" min="30" max="180" step="15" value="120" 
                                       class="flex-1" oninput="this.nextElementSibling.value = this.value + ' min'">
                                <output class="text-gray-600 w-16">120 min</output>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Step 3: Priority Mode -->
                <div class="wizard-step hidden" data-step="3">
                    <h3 class="text-lg font-semibold mb-4">Scheduling Priority</h3>
                    <div class="space-y-4">
                        <label class="block p-4 border rounded-lg hover:border-purple-300 cursor-pointer">
                            <input type="radio" name="priorityMode" value="deadlines" class="mr-2">
                            <span class="font-medium">Prioritize Deadlines</span>
                            <p class="text-sm text-gray-600 mt-1">Focus on meeting deadlines and exam preparation first</p>
                        </label>
                        <label class="block p-4 border rounded-lg hover:border-purple-300 cursor-pointer">
                            <input type="radio" name="priorityMode" value="balanced" class="mr-2" checked>
                            <span class="font-medium">Balanced Schedule</span>
                            <p class="text-sm text-gray-600 mt-1">Equal focus on all tasks with smart time distribution</p>
                        </label>
                        <label class="block p-4 border rounded-lg hover:border-purple-300 cursor-pointer">
                            <input type="radio" name="priorityMode" value="flexible" class="mr-2">
                            <span class="font-medium">Flexible Learning</span>
                            <p class="text-sm text-gray-600 mt-1">Adapt schedule based on your energy levels and preferences</p>
                        </label>
                    </div>
                </div>
                
                <!-- Step 4: System Prompt -->
                <div class="wizard-step hidden" data-step="4">
                    <h3 class="text-lg font-semibold mb-4">AI Assistant Configuration</h3>
                    <div class="space-y-4">
                        <p class="text-gray-600">Configure how your AI calendar assistant should behave and what to prioritize.</p>
                        <textarea id="systemPrompt" rows="6" 
                                  class="w-full rounded-md border-gray-300 shadow-sm"
                                  placeholder="You are a helpful calendar assistant who helps me optimize my schedule. When suggesting changes, consider that..."></textarea>
                        <div class="text-sm text-gray-500">
                            Tips:
                            <ul class="list-disc pl-5 space-y-1 mt-2">
                                <li>Mention your preferred learning style</li>
                                <li>Specify any scheduling constraints</li>
                                <li>Include personal preferences</li>
                                <li>Note activities that need special attention</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Navigation Buttons -->
            <div class="flex justify-between mt-8">
                <div class="space-x-3">
                    <button id="skipSetup" onclick="skipSetup()" 
                            class="px-4 py-2 text-gray-600 bg-gray-100 rounded-lg hover:bg-gray-200">
                        Skip Setup
                    </button>
                    <button id="prevStep" class="hidden px-4 py-2 text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200">
                        Previous
                    </button>
                </div>
                <button id="nextStep" class="px-4 py-2 text-white bg-purple-600 rounded-lg hover:bg-purple-700">
                    Next
                </button>
            </div>
        </div>
    </div>
    
    <script>
    async function skipSetup() {
        try {
            const response = await fetch('/CalendarAI/api/save-preferences.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    preferences: {
                        priorityMode: 'balanced'
                    },
                    isBasicSetup: true
                })
            });

            const data = await response.json();
            
            if (data.success) {
                const setupWizard = document.getElementById('setupWizard');
                setupWizard.classList.add('hidden');
                showNotification('Basic setup completed successfully', 'success');
                setTimeout(() => location.reload(), 1500);
            } else {
                throw new Error(data.error || 'Failed to complete basic setup');
            }
        } catch (error) {
            console.error('Skip setup error:', error);
            showNotification('Failed to complete basic setup: ' + error.message, 'error');
        }
    }

    // Add validation helper
    function validateStep(stepNumber) {
        const step = document.querySelector(`.wizard-step[data-step="${stepNumber}"]`);
        if (!step) return true;

        let isValid = true;
        const inputs = step.querySelectorAll('input[type="time"], input[type="number"], input[type="range"], input[type="radio"]:checked, textarea');
        
        inputs.forEach(input => {
            if (!input.value) {
                input.classList.add('border-red-500');
                isValid = false;
            } else {
                input.classList.remove('border-red-500');
            }
        });

        return isValid;
    }
    </script>
HTML;
}
?>