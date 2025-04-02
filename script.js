// Table filtering
function filterTable(inputElement, tableId) {
    const filterValue = inputElement.value.toLowerCase();
    const table = document.getElementById(tableId);
    const rows = table.getElementsByTagName('tr');

    for (let i = 1; i < rows.length; i++) {
        const cells = rows[i].getElementsByTagName('td');
        let found = false;

        for (let j = 0; j < cells.length; j++) {
            const cellText = cells[j].textContent.toLowerCase();
            if (cellText.includes(filterValue)) {
                found = true;
                break;
            }
        }

        rows[i].style.display = found ? '' : 'none';
    }
}

// Modal handling
const modal = {
    show(modalId) {
        const element = document.getElementById(modalId);
        element.classList.remove('hidden');
        document.body.classList.add('overflow-hidden');
        
        // Fade in animation
        setTimeout(() => {
            element.classList.add('opacity-100');
            element.classList.remove('opacity-0');
        }, 10);
    },

    hide(modalId) {
        const element = document.getElementById(modalId);
        element.classList.add('opacity-0');
        element.classList.remove('opacity-100');
        
        // Fade out animation
        setTimeout(() => {
            element.classList.add('hidden');
            document.body.classList.remove('overflow-hidden');
        }, 300);
    }
};

// Form validation
function validateForm(formId) {
    const form = document.getElementById(formId);
    const requiredFields = form.querySelectorAll('[required]');
    let isValid = true;

    requiredFields.forEach(field => {
        if (!field.value.trim()) {
            field.classList.add('border-red-500', 'focus:ring-red-500');
            isValid = false;
        } else {
            field.classList.remove('border-red-500', 'focus:ring-red-500');
        }
    });

    return isValid;
}

// Notification toast
const toast = {
    show(message, type = 'success') {
        const colors = {
            success: 'bg-green-500',
            error: 'bg-red-500',
            warning: 'bg-yellow-500'
        };

        const toast = document.createElement('div');
        toast.className = `fixed top-4 right-4 p-4 rounded-md text-white ${colors[type]} shadow-lg transition-all transform translate-y-0 opacity-0`;
        toast.textContent = message;

        document.body.appendChild(toast);

        // Fade in
        setTimeout(() => {
            toast.classList.remove('opacity-0');
            toast.classList.add('opacity-100');
        }, 10);

        // Fade out and remove
        setTimeout(() => {
            toast.classList.add('opacity-0');
            setTimeout(() => toast.remove(), 300);
        }, 3000);
    }
};

// Live search with debounce
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

// Optimization functions
async function showOptimizeModal() {
    const { value: preferences } = await Swal.fire({
        title: 'Calendar Optimization',
        html: `
            <div class="space-y-4 text-left">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Preferred Study Time</label>
                    <select id="studyTime" class="w-full rounded-lg border-gray-300 shadow-sm">
                        <option value="morning">Morning (6AM-12PM)</option>
                        <option value="afternoon">Afternoon (12PM-5PM)</option>
                        <option value="evening">Evening (5PM-10PM)</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Learning Style</label>
                    <select id="learningStyle" class="w-full rounded-lg border-gray-300 shadow-sm">
                        <option value="focused">Focused (Longer sessions, shorter breaks)</option>
                        <option value="balanced">Balanced (Medium sessions and breaks)</option>
                        <option value="flexible">Flexible (Shorter sessions, longer breaks)</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Optimization Priority</label>
                    <select id="priority" class="w-full rounded-lg border-gray-300 shadow-sm">
                        <option value="energy">Energy Levels</option>
                        <option value="deadlines">Deadlines First</option>
                        <option value="category">Category-based</option>
                    </select>
                </div>
                <div class="bg-blue-50 p-4 rounded-lg">
                    <p class="text-sm text-blue-700">
                        AI will analyze your schedule and suggest optimizations based on:
                        <ul class="list-disc pl-5 mt-2 space-y-1">
                            <li>Your preferred working hours</li>
                            <li>Learning style patterns</li>
                            <li>Event categories and priorities</li>
                            <li>Break requirements</li>
                        </ul>
                    </p>
                </div>
            </div>
        `,
        showCancelButton: true,
        confirmButtonText: 'Analyze & Optimize',
        cancelButtonText: 'Cancel',
        showLoaderOnConfirm: true,
        preConfirm: () => {
            return {
                studyTime: document.getElementById('studyTime').value,
                learningStyle: document.getElementById('learningStyle').value,
                priority: document.getElementById('priority').value,
                userId: window.userId || 1
            };
        }
    });

    if (preferences) {
        await optimizeSchedule(preferences);
    }
}

async function optimizeSchedule(preferences) {
    try {
        // Show loading state
        Swal.fire({
            title: 'Analyzing Your Schedule',
            html: `
                <div class="space-y-4">
                    <div class="flex justify-center">
                        <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-purple-500"></div>
                    </div>
                    <p class="text-sm text-gray-600">AI is analyzing your schedule patterns...</p>
                    <div class="text-xs text-gray-500 space-y-1">
                        <div>• Evaluating event timing</div>
                        <div>• Checking for conflicts</div>
                        <div>• Optimizing break periods</div>
                    </div>
                </div>
            `,
            allowOutsideClick: false,
            showConfirmButton: false
        });

        const response = await fetch('/calendar/api/optimize.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ preferences })
        });

        if (!response.ok) {
            throw new Error('Optimization request failed');
        }

        const result = await response.json();
        if (!result.success) {
            throw new Error(result.error || 'Optimization failed');
        }

        // Show optimization results
        const { value: selectedChanges } = await Swal.fire({
            title: 'AI Optimization Results',
            html: `
                <div class="text-left space-y-6">
                    <div class="mb-4">
                        <h3 class="font-medium text-lg mb-2">Schedule Health:</h3>
                        <div class="grid grid-cols-2 gap-4">
                            ${formatHealthMetrics(result.schedule_health)}
                        </div>
                    </div>
                    
                    <div class="mb-4">
                        <h3 class="font-medium text-lg mb-2">AI Suggestions:</h3>
                        <div class="bg-purple-50 p-4 rounded-lg text-sm space-y-2">
                            ${formatSuggestions(result.suggestions)}
                        </div>
                    </div>
                    
                    <div>
                        <h3 class="font-medium text-lg mb-2">Proposed Changes:</h3>
                        <div class="space-y-2 max-h-60 overflow-y-auto">
                            ${formatProposedChanges(result.changes)}
                        </div>
                    </div>
                </div>
            `,
            width: '800px',
            showCancelButton: true,
            confirmButtonText: 'Apply Selected Changes',
            cancelButtonText: 'Cancel',
            didOpen: () => {
                initializeChangeToggles();
            },
            preConfirm: () => getSelectedChanges()
        });

        if (selectedChanges && selectedChanges.length > 0) {
            await applyChanges(selectedChanges);
            showNotification('Schedule optimized successfully!', 'success');
            setTimeout(() => location.reload(), 1500);
        }

    } catch (error) {
        console.error('Optimization error:', error);
        Swal.fire({
            icon: 'error',
            title: 'Optimization Failed',
            text: error.message
        });
    }
}

async function applyChanges(changes) {
    try {
        const response = await fetch('/calendar/api/apply-changes.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ changes })
        });

        const result = await response.json();
        if (!result.success) {
            throw new Error(result.error || 'Failed to apply changes');
        }
    } catch (error) {
        console.error('Error applying changes:', error);
        throw new Error('Failed to apply schedule changes: ' + error.message);
    }
}

function formatHealthMetrics(health) {
    const metrics = [
        { name: 'Focus Time', value: health.focus_time_utilization, color: 'blue' },
        { name: 'Break Compliance', value: health.break_compliance, color: 'green' },
        { name: 'Conflict Score', value: health.conflict_score, color: 'red', reverse: true },
        { name: 'Balance', value: health.balance_score, color: 'purple' }
    ];

    return metrics.map(metric => `
        <div class="bg-${metric.color}-50 p-3 rounded-lg">
            <div class="text-sm font-medium text-${metric.color}-700">${metric.name}</div>
            <div class="mt-1">
                <div class="w-full bg-${metric.color}-200 rounded-full h-2">
                    <div class="bg-${metric.color}-600 h-2 rounded-full" 
                         style="width: ${metric.reverse ? 100 - metric.value : metric.value}%">
                    </div>
                </div>
                <div class="text-xs text-${metric.color}-600 mt-1">
                    ${metric.value}${metric.reverse ? ' issues' : '%'}
                </div>
            </div>
        </div>
    `).join('');
}

function formatSuggestions(suggestions) {
    if (!Array.isArray(suggestions)) {
        return '<p class="text-gray-500">No suggestions available</p>';
    }
    return suggestions.map(s => `
        <p class="flex items-start">
            <i class="fas fa-lightbulb text-purple-500 mt-1 mr-2"></i>
            <span>${s}</span>
        </p>
    `).join('');
}

function formatProposedChanges(changes) {
    if (!changes || !changes.length) {
        return '<p class="text-gray-500">No changes suggested</p>';
    }

    return changes.map(change => `
        <div class="change-row p-4 rounded-lg border border-gray-200 hover:border-purple-300 transition-colors">
            <div class="flex items-center">
                <input type="checkbox" 
                       class="change-toggle h-4 w-4 text-purple-600 rounded border-gray-300 
                              focus:ring-purple-500 transition-all"
                       data-event-id="${change.event_id}"
                       data-new-time="${change.new_time}"
                       data-duration="${change.duration || ''}"
                       checked>
                <div class="ml-3 flex-1">
                    <div class="flex justify-between items-start">
                        <div>
                            <p class="font-medium text-gray-900">Event #${change.event_id}</p>
                            <p class="text-sm text-purple-600">
                                ${new Date(change.new_time).toLocaleString()}
                            </p>
                        </div>
                        ${change.duration ? `
                        <span class="text-xs text-gray-500 bg-gray-100 px-2 py-1 rounded">
                            ${change.duration} min
                        </span>
                        ` : ''}
                    </div>
                    ${change.reason ? `
                    <p class="mt-1 text-sm text-gray-600">${change.reason}</p>
                    ` : ''}
                </div>
            </div>
        </div>
    `).join('');
}

function initializeChangeToggles() {
    document.querySelectorAll('.change-toggle').forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            const row = this.closest('.change-row');
            if (row) {
                row.classList.toggle('bg-purple-50', this.checked);
                row.classList.toggle('border-purple-200', this.checked);
            }
        });
    });
}

function getSelectedChanges() {
    const selectedChanges = Array.from(document.querySelectorAll('.change-toggle:checked'))
        .map(checkbox => ({
            event_id: checkbox.dataset.eventId,
            new_time: checkbox.dataset.newTime,
            duration: checkbox.dataset.duration || undefined
        }));
    
    if (selectedChanges.length === 0) {
        throw new Error('Please select at least one change to apply');
    }
    
    return selectedChanges;
}

// Function to handle optimization response
function handleOptimizationResponse(response) {
    const optimizationResults = document.getElementById('optimizationResults');
    const preferencesForm = document.getElementById('preferencesForm');
    const aiSuggestions = document.getElementById('aiSuggestions');
    const proposedChanges = document.getElementById('proposedChanges');

    // Display suggestions
    aiSuggestions.innerHTML = response.suggestions
        .map(suggestion => `<li class="mb-2">${suggestion}</li>`)
        .join('');

    // Display proposed changes
    proposedChanges.innerHTML = formatProposedChanges(response.changes);

    // Show results, hide form
    preferencesForm.classList.add('hidden');
    optimizationResults.classList.remove('hidden');
}

// Function to apply selected changes
async function applySelectedChanges() {
    const selectedChanges = Array.from(document.querySelectorAll('.change-toggle:checked'))
        .map(checkbox => ({
            event_id: checkbox.dataset.eventId,
            new_time: checkbox.dataset.newTime,
            duration: checkbox.dataset.duration
        }));

    if (selectedChanges.length === 0) {
        return;
    }

    try {
        const response = await fetch('/calendar/api/apply-changes.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ changes: selectedChanges })
        });

        const result = await response.json();
        if (result.success) {
            Swal.fire({
                title: 'Success!',
                text: 'Schedule changes have been applied.',
                icon: 'success',
                showConfirmButton: false,
                timer: 1500
            }).then(() => {
                location.reload();
            });
        } else {
            throw new Error(result.error);
        }
    } catch (error) {
        Swal.fire({
            title: 'Error',
            text: 'Failed to apply changes: ' + error.message,
            icon: 'error'
        });
    }
}

// Setup Wizard Functionality
function showSetupWizard() {
    const wizard = document.getElementById('setupWizard');
    wizard.classList.remove('hidden');
}

function hideSetupWizard() {
    const wizard = document.getElementById('setupWizard');
    wizard.classList.add('hidden');
}

// Setup wizard navigation
document.addEventListener('DOMContentLoaded', function() {
    const setupWizard = document.getElementById('setupWizard');
    const wizardSteps = document.querySelectorAll('.wizard-step');
    const prevButton = document.getElementById('prevStep');
    const nextButton = document.getElementById('nextStep');
    const progressDots = document.querySelectorAll('#wizardSteps > div');
    let currentStep = 0;

    function updateWizardStep() {
        wizardSteps.forEach((step, index) => {
            step.classList.toggle('hidden', index !== currentStep);
        });

        progressDots.forEach((dot, index) => {
            dot.classList.toggle('bg-purple-600', index <= currentStep);
            dot.classList.toggle('bg-gray-300', index > currentStep);
        });

        prevButton.classList.toggle('hidden', currentStep === 0);
        nextButton.textContent = currentStep === wizardSteps.length - 1 ? 'Finish' : 'Next';
    }

    function collectPreferences() {
        return {
            focusStartTime: document.getElementById('focusStartTime').value,
            focusEndTime: document.getElementById('focusEndTime').value,
            chillStartTime: document.getElementById('chillStartTime').value,
            chillEndTime: document.getElementById('chillEndTime').value,
            breakDuration: parseInt(document.getElementById('breakDuration').value),
            sessionLength: parseInt(document.getElementById('sessionLength').value),
            priorityMode: document.querySelector('input[name="priorityMode"]:checked').value,
            systemPrompt: document.getElementById('systemPrompt').value
        };
    }

    async function savePreferences() {
        const preferences = collectPreferences();
        
        try {
            const response = await fetch('/calendar/api/save-preferences.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    userId: 1, // Replace with actual user ID from session
                    preferences: preferences
                })
            });

            const data = await response.json();
            
            if (data.success) {
                Swal.fire({
                    icon: 'success',
                    title: 'Setup Complete!',
                    text: 'Your preferences have been saved successfully.',
                    confirmButtonColor: '#6366F1'
                }).then(() => {
                    hideSetupWizard();
                    showCalendarAssistant(); // Show the chat interface after setup
                });
            } else {
                throw new Error(data.error || 'Failed to save preferences');
            }
        } catch (error) {
            Swal.fire({
                icon: 'error',
                title: 'Oops...',
                text: error.message,
                confirmButtonColor: '#6366F1'
            });
        }
    }

    // Event Listeners
    prevButton.addEventListener('click', () => {
        if (currentStep > 0) {
            currentStep--;
            updateWizardStep();
        }
    });

    nextButton.addEventListener('click', async () => {
        if (currentStep < wizardSteps.length - 1) {
            currentStep++;
            updateWizardStep();
        } else {
            await savePreferences();
        }
    });

    // Initialize slider outputs
    document.getElementById('breakDuration').addEventListener('input', function() {
        this.nextElementSibling.value = this.value + ' min';
    });

    document.getElementById('sessionLength').addEventListener('input', function() {
        this.nextElementSibling.value = this.value + ' min';
    });
});

// Calendar Assistant Chat Functionality
function showCalendarAssistant() {
    const assistant = document.getElementById('calendarAssistant');
    assistant.classList.remove('translate-x-full');
}

function hideCalendarAssistant() {
    const assistant = document.getElementById('calendarAssistant');
    assistant.classList.add('translate-x-full');
}

document.getElementById('toggleAssistant')?.addEventListener('click', function() {
    const assistant = document.getElementById('calendarAssistant');
    assistant.classList.toggle('translate-x-full');
});

// Chat functionality
document.getElementById('sendMessage')?.addEventListener('click', async function() {
    const input = document.getElementById('chatInput');
    const message = input.value.trim();
    
    if (!message) return;
    
    const chatMessages = document.getElementById('chatMessages');
    const processingIndicator = document.getElementById('processingIndicator');
    
    // Add user message
    chatMessages.innerHTML += `
        <div class="flex justify-end mb-3">
            <div class="bg-purple-100 text-purple-900 rounded-lg py-2 px-4 max-w-[80%]">
                ${escapeHtml(message)}
            </div>
        </div>
    `;
    
    input.value = '';
    chatMessages.scrollTop = chatMessages.scrollHeight;
    
    // Show processing indicator
    processingIndicator.classList.remove('hidden');
    
    try {
        const response = await fetch('/calendar/api/chat-assistant.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                message: message,
                userId: 1 // Replace with actual user ID from session
            })
        });

        const data = await response.json();
        
        if (data.success) {
            // Add assistant response
            chatMessages.innerHTML += `
                <div class="flex mb-3">
                    <div class="bg-gray-100 text-gray-900 rounded-lg py-2 px-4 max-w-[80%]">
                        ${escapeHtml(data.response)}
                    </div>
                </div>
            `;
            
            // Handle any actions
            if (data.action && data.action.action !== 'none') {
                handleAssistantAction(data.action);
            }
        } else {
            throw new Error(data.error || 'Failed to get response');
        }
        
    } catch (error) {
        chatMessages.innerHTML += `
            <div class="flex mb-3">
                <div class="bg-red-100 text-red-900 rounded-lg py-2 px-4 max-w-[80%]">
                    Sorry, I encountered an error: ${escapeHtml(error.message)}
                </div>
            </div>
        `;
    } finally {
        processingIndicator.classList.add('hidden');
        chatMessages.scrollTop = chatMessages.scrollHeight;
    }
});

function escapeHtml(str) {
    const div = document.createElement('div');
    div.textContent = str;
    return div.innerHTML;
}

async function handleAssistantAction(action) {
    switch (action.action) {
        case 'add':
        case 'move':
        case 'delete':
            await fetch('/calendar/api/apply-changes.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(action)
            });
            // Refresh calendar view
            location.reload();
            break;
    }
}

document.addEventListener('DOMContentLoaded', () => {
    // Initialize any search inputs with debounce
    const searchInputs = document.querySelectorAll('[data-search-table]');
    searchInputs.forEach(input => {
        const tableId = input.dataset.searchTable;
        input.addEventListener('input', debounce(e => filterTable(e.target, tableId), 300));
    });

    // Remove the initializeOptimization call since we don't need it
});