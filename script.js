// Global variables
let selectedOptimizationPreset = 'default';

// Add debug utilities at the top of the file
const DEBUG = true;  // Match PHP config

function debugLog(message, data = null) {
    if (!DEBUG) return;
    
    const logMessage = {
        timestamp: new Date().toISOString(),
        message,
        data
    };
    
    console.log('[Calendar Debug]', logMessage);
}

// Function to extract debug info from API responses
function getDebugInfo(response) {
    if (!DEBUG) return null;
    
    const debugHeader = response.headers.get('X-Debug-Info');
    if (debugHeader) {
        try {
            return JSON.parse(atob(debugHeader));
        } catch (e) {
            console.warn('Failed to parse debug info:', e);
            return debugHeader;
        }
    }
    return null;
}

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
    initializeOptimizeModal(); // Ensure only the new modal with selectable days is shown
}

async function optimizeSchedule(preferences) {
    try {
        const response = await fetch('/CalendarAI/api/optimize.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ preferences })
        });

        const result = await response.json();

        if (!result.success) {
            throw new Error(result.message);
        }

        if (result.changes.length > 0) {
            Swal.fire({
                title: 'Optimization Complete',
                html: result.changes.map(change => `<p>Event ID ${change.event_id} moved to ${change.new_time} - ${change.reason}</p>`).join(''),
                icon: 'success'
            });
        } else {
            Swal.fire({
                title: 'No Changes Needed',
                text: 'Your schedule is already optimized.',
                icon: 'info'
            });
        }
    } catch (error) {
        Swal.fire({
            title: 'Optimization Failed',
            text: error.message,
            icon: 'error'
        });
    }
}

async function applyChanges(changes) {
    try {
        debugLog('Applying schedule changes', changes);
        
        const response = await fetch('/CalendarAI/api/apply-changes.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ changes })
        });

        const debugInfo = getDebugInfo(response);
        if (debugInfo) {
            debugLog('Server debug information for changes', debugInfo);
        }

        const result = await response.json();
        debugLog('Changes application result', result);

        if (!result.success) {
            throw new Error(result.error || 'Failed to apply changes');
        }
    } catch (error) {
        debugLog('Error applying changes', {
            message: error.message,
            stack: error.stack
        });
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
            duration: checkbox.dataset.duration || undefined
        }));

    if (selectedChanges.length === 0) {
        Swal.fire({
            title: 'No Changes Selected',
            text: 'Please select at least one change to apply.',
            icon: 'warning'
        });
        return;
    }

    try {
        debugLog('Applying selected changes', selectedChanges);
        
        const response = await fetch('/CalendarAI/api/apply-changes.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ changes: selectedChanges })
        });

        // Check for HTTP errors
        if (!response.ok) {
            const errorText = await response.text();
            debugLog('Server error response', { status: response.status, body: errorText });
            throw new Error(`Server error (${response.status}): ${errorText}`);
        }
        
        const responseText = await response.text();
        debugLog('Raw server response', responseText);
        
        // Parse JSON safely
        let result;
        try {
            result = JSON.parse(responseText);
        } catch (parseError) {
            debugLog('JSON parse error', { 
                error: parseError.message,
                responseText: responseText.substring(0, 1000) // Log first 1000 chars
            });
            throw new Error(`Failed to parse server response: ${parseError.message}`);
        }
        
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
            throw new Error(result.error || 'Failed to apply changes');
        }
    } catch (error) {
        debugLog('Error applying changes', {
            message: error.message,
            stack: error.stack
        });
        
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
    let currentStep = 0; // Start at first step

    function updateWizardStep() {
        // Hide all steps
        wizardSteps.forEach((step, index) => {
            step.classList.toggle('hidden', index !== currentStep);
        });

        // Update progress dots
        progressDots.forEach((dot, index) => {
            dot.classList.toggle('bg-purple-600', index <= currentStep);
            dot.classList.toggle('bg-gray-300', index > currentStep);
        });

        // Show/hide Previous button
        prevButton.classList.toggle('hidden', currentStep === 0);
        
        // Update Next/Finish button
        nextButton.textContent = currentStep === wizardSteps.length - 1 ? 'Complete Setup' : 'Next';
    }

    // Ensure wizard starts at first step
    updateWizardStep();

    // Event Listeners
    prevButton.addEventListener('click', () => {
        if (currentStep > 0) {
            currentStep--;
            updateWizardStep();
        }
    });

    nextButton.addEventListener('click', async () => {
        if (currentStep < wizardSteps.length - 1) {
            // Validate current step before proceeding
            const currentWizardStep = wizardSteps[currentStep];
            const inputs = currentWizardStep.querySelectorAll('input, select, textarea');
            let isValid = true;

            inputs.forEach(input => {
                if (input.type === 'time' && !input.value) {
                    isValid = false;
                    input.classList.add('border-red-500');
                } else {
                    input.classList.remove('border-red-500');
                }
            });

            if (!isValid) {
                showNotification('Please fill in all required fields', 'error');
                return;
            }

            currentStep++;
            updateWizardStep();
        } else {
            // On final step, save preferences
            await savePreferences();
        }
    });

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
            const response = await fetch('/CalendarAI/api/save-preferences.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    preferences: preferences,
                    isBasicSetup: document.querySelector('button#skipSetup') !== null
                })
            });

            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            const data = await response.json();
            
            if (data.success) {
                Swal.fire({
                    icon: 'success',
                    title: 'Setup Complete!',
                    text: 'Your preferences have been saved successfully.',
                    confirmButtonColor: '#6366F1'
                }).then(() => {
                    hideSetupWizard();
                    location.reload(); // Reload to reflect new setup status
                });
            } else {
                throw new Error(data.error || 'Failed to save preferences');
            }
        } catch (error) {
            console.error('Save preferences error:', error);
            Swal.fire({
                icon: 'error',
                title: 'Oops...',
                text: error.message,
                confirmButtonColor: '#6366F1'
            });
        }
    }

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
        const response = await fetch('/CalendarAI/api/chat-assistant.php', {
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
            await fetch('/CalendarAI/api/apply-changes.php', {
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

// Declutter functionality
async function showDeclutterModal() {
    const { value: deletableEvents } = await Swal.fire({
        title: 'Declutter Your Calendar',
        html: `
            <div class="space-y-4 text-left">
                <p>Select events you want to delete:</p>
                <div id="declutterEvents" class="space-y-2">
                    <!-- Events will be dynamically populated here -->
                </div>
            </div>
        `,
        showCancelButton: true,
        confirmButtonText: 'Delete Selected',
        cancelButtonText: 'Cancel',
        preConfirm: () => {
            const selectedEvents = Array.from(document.querySelectorAll('.declutter-checkbox:checked'))
                .map(checkbox => checkbox.dataset.eventId);
            return selectedEvents;
        }
    });

    if (deletableEvents && deletableEvents.length > 0) {
        await deleteEvents(deletableEvents);
    }
}

async function deleteEvents(eventIds) {
    try {
        const response = await fetch('/CalendarAI/api/delete-events.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ eventIds })
        });

        const result = await response.json();

        if (!result.success) {
            throw new Error(result.message);
        }

        Swal.fire({
            title: 'Declutter Complete',
            text: 'Selected events have been deleted.',
            icon: 'success'
        });

        location.reload();
    } catch (error) {
        Swal.fire({
            title: 'Declutter Failed',
            text: error.message,
            icon: 'error'
        });
    }
}

// Sidebar AI Chat functionality
async function handleChatAssistantInput(message) {
    try {
        const response = await fetch('/CalendarAI/api/chat-assistant.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ message })
        });

        const result = await response.json();

        if (!result.success) {
            throw new Error(result.message);
        }

        // Display the assistant's response
        const chatMessages = document.getElementById('chatMessages');
        chatMessages.innerHTML += `
            <div class="flex mb-3">
                <div class="bg-gray-100 text-gray-900 rounded-lg py-2 px-4 max-w-[80%]">
                    ${result.response}
                </div>
            </div>
        `;

        chatMessages.scrollTop = chatMessages.scrollHeight;
    } catch (error) {
        Swal.fire({
            title: 'Chat Assistant Error',
            text: error.message,
            icon: 'error'
        });
    }
}

// Event listener for chat input
const chatInput = document.getElementById('chatInput');
const sendMessageButton = document.getElementById('sendMessage');

sendMessageButton.addEventListener('click', () => {
    const message = chatInput.value.trim();
    if (message) {
        handleChatAssistantInput(message);
        chatInput.value = '';
    }
});

document.addEventListener('DOMContentLoaded', () => {
    // Initialize any search inputs with debounce
    const searchInputs = document.querySelectorAll('[data-search-table]');
    searchInputs.forEach(input => {
        const tableId = input.dataset.searchTable;
        input.addEventListener('input', debounce(e => filterTable(e.target, tableId), 300));
    });

    // Initialize chat functionality
    const chatInput = document.getElementById('chatInput');
    const sendMessageButton = document.getElementById('sendMessage');
    const toggleAssistantButton = document.getElementById('toggleAssistant');

    if (sendMessageButton && chatInput) {
        sendMessageButton.addEventListener('click', () => {
            const message = chatInput.value.trim();
            if (message) {
                handleChatAssistantInput(message);
                chatInput.value = '';
            }
        });
    }

    if (toggleAssistantButton) {
        toggleAssistantButton.addEventListener('click', function() {
            const assistant = document.getElementById('calendarAssistant');
            if (assistant) {
                assistant.classList.toggle('translate-x-full');
            }
        });
    }

    // Initialize slider outputs
    const breakDuration = document.getElementById('breakDuration');
    const sessionLength = document.getElementById('sessionLength');

    if (breakDuration) {
        breakDuration.addEventListener('input', function() {
            if (this.nextElementSibling) {
                this.nextElementSibling.value = this.value + ' min';
            }
        });
    }

    if (sessionLength) {
        sessionLength.addEventListener('input', function() {
            if (this.nextElementSibling) {
                this.nextElementSibling.value = this.value + ' min';
            }
        });
    }

    // Initialize optimization modal if it exists
    const optimizeBtn = document.getElementById('optimizeBtn');
    if (optimizeBtn) {
        optimizeBtn.addEventListener('click', initializeOptimizeModal);
    }
});

function populateCalendarDays() {
    const calendarDaysContainer = document.getElementById('calendarDays');
    if (!calendarDaysContainer) return;

    const today = new Date();
    const nextMonth = new Date(today);
    nextMonth.setMonth(today.getMonth() + 1);

    const days = [];
    for (let d = new Date(today); d < nextMonth; d.setDate(d.getDate() + 1)) {
        const dateStr = d.toISOString().split('T')[0];
        days.push(`
            <div class="day-item border rounded-lg p-2 text-center cursor-pointer hover:bg-purple-100" 
                 data-date="${dateStr}" onclick="toggleDaySelection(this)">
                ${d.getDate()}
            </div>
        `);
    }

    calendarDaysContainer.innerHTML = days.join('');
}

function toggleDaySelection(dayElement) {
    dayElement.classList.toggle('bg-purple-200');
    dayElement.classList.toggle('selected');
}

function applyDaySelection() {
    const selectedDays = Array.from(document.querySelectorAll('.day-item.selected'))
        .map(day => day.dataset.date);

    if (selectedDays.length === 0) {
        Swal.fire({
            title: 'No Days Selected',
            text: 'Please select at least one day to optimize.',
            icon: 'warning'
        });
        return;
    }

    Swal.fire({
        title: 'Optimizing...',
        text: `Optimizing the following days: ${selectedDays.join(', ')}`,
        icon: 'info',
        showConfirmButton: false,
        allowOutsideClick: false
    });

    fetch('/CalendarAI/api/optimize.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({ days: selectedDays })
    })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                Swal.fire({
                    title: 'Optimization Complete',
                    text: 'Your selected days have been optimized.',
                    icon: 'success'
                });
            } else {
                throw new Error(data.error || 'Optimization failed.');
            }
        })
        .catch(error => {
            Swal.fire({
                title: 'Error',
                text: error.message,
                icon: 'error'
            });
        });
}

// Initialize calendar days when the modal is shown
function initializeOptimizeModal() {
    populateCalendarDays();
    modal.show('optimizeModal');
}

function applyDayAndPreferenceSelection() {
    const selectedDays = Array.from(document.querySelectorAll('.day-item.selected'))
        .map(day => day.dataset.date);

    if (selectedDays.length === 0) {
        Swal.fire({
            title: 'No Days Selected',
            text: 'Please select at least one day to optimize.',
            icon: 'warning'
        });
        return;
    }

    const preferencesForm = document.getElementById('preferencesForm');
    const formData = new FormData(preferencesForm);
    const preferences = Object.fromEntries(formData.entries());

    Swal.fire({
        title: 'Optimizing...',
        text: `Optimizing the following days: ${selectedDays.join(', ')}`,
        icon: 'info',
        showConfirmButton: false,
        allowOutsideClick: false
    });

    fetch('/CalendarAI/api/optimize.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({ days: selectedDays, preferences })
    })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                Swal.fire({
                    title: 'Optimization Complete',
                    text: 'Your selected days and preferences have been optimized.',
                    icon: 'success'
                });
            } else {
                throw new Error(data.error || 'Optimization failed.');
            }
        })
        .catch(error => {
            Swal.fire({
                title: 'Error',
                text: error.message,
                icon: 'error'
            });
        });
}

function selectPreset(preset) {
    const dayElements = document.querySelectorAll('.day-item');
    const today = new Date();

    dayElements.forEach(dayElement => {
        const date = new Date(dayElement.dataset.date);
        const dayOfWeek = date.getDay();

        let shouldSelect = false;

        if (preset === 'weekdays' && dayOfWeek >= 1 && dayOfWeek <= 5) {
            shouldSelect = true;
        } else if (preset === 'weekends' && (dayOfWeek === 0 || dayOfWeek === 6)) {
            shouldSelect = true;
        } else if (preset === 'fridays' && dayOfWeek === 5) {
            shouldSelect = true;
        }

        if (shouldSelect) {
            dayElement.classList.add('bg-purple-200', 'selected');
        } else {
            dayElement.classList.remove('bg-purple-200', 'selected');
        }
    });
}

function displayOptimizationResult(data) {
    const aiSuggestions = document.getElementById('aiSuggestions');
    const optimizationStats = document.getElementById('optimizationStats');
    const proposedChanges = document.getElementById('proposedChanges');

    // Display AI suggestions
    aiSuggestions.innerHTML = data.analysis.insights
        .map(insight => `<li class="mb-2">${insight}</li>`)
        .join('');

    // Display health metrics
    const health = data.analysis.schedule_health;
    optimizationStats.innerHTML = formatHealthMetrics(health);

    // Display proposed changes
    proposedChanges.innerHTML = formatProposedChanges(data.changes);

    // Initialize change toggles
    initializeChangeToggles();

    // Show results, hide loading
    document.getElementById('optimizationLoading').classList.add('hidden');
    document.getElementById('optimizationResults').classList.remove('hidden');
}

// Function to run optimization process
function selectOptimizationPreset(preset) {
    // Remove active state from all preset buttons
    document.querySelectorAll('.preset-btn').forEach(btn => {
        btn.classList.remove('ring-2', 'ring-offset-2');
        btn.classList.remove('ring-purple-500', 'ring-orange-500', 'ring-red-500', 'ring-green-500');
    });

    // Add active state to selected preset button
    const btn = document.querySelector(`[onclick="selectOptimizationPreset('${preset}')"]`);
    btn.classList.add('ring-2', 'ring-offset-2');
    switch(preset) {
        case 'busy_week':
            btn.classList.add('ring-orange-500');
            break;
        case 'conflicts':
            btn.classList.add('ring-red-500');
            break;
        case 'optimized':
            btn.classList.add('ring-green-500');
            break;
        default:
            btn.classList.add('ring-purple-500');
    }

    selectedOptimizationPreset = preset;
}

function selectDayPreset(preset) {
    const dayElements = document.querySelectorAll('.day-item');
    dayElements.forEach(dayElement => {
        const date = new Date(dayElement.dataset.date);
        const dayOfWeek = date.getDay();
        
        let shouldSelect = false;
        switch(preset) {
            case 'weekdays':
                shouldSelect = dayOfWeek >= 1 && dayOfWeek <= 5;
                break;
            case 'weekends':
                shouldSelect = dayOfWeek === 0 || dayOfWeek === 6;
                break;
            case 'all':
                shouldSelect = true;
                break;
        }
        
        if (shouldSelect) {
            dayElement.classList.add('bg-purple-200', 'selected');
        } else {
            dayElement.classList.remove('bg-purple-200', 'selected');
        }
    });
}

async function runOptimization() {
    const selectedDays = Array.from(document.querySelectorAll('.day-item.selected'))
        .map(day => day.dataset.date);

    if (selectedDays.length === 0) {
        Swal.fire({
            title: 'No Days Selected',
            text: 'Please select at least one day to optimize.',
            icon: 'warning'
        });
        return;
    }

    // Show loading state
    document.getElementById('preferencesForm').classList.add('hidden');
    document.getElementById('optimizationResults').classList.add('hidden');
    document.getElementById('optimizationLoading').classList.remove('hidden');
    document.getElementById('optimizeScheduleBtn').classList.add('hidden');

    // Collect form data
    const form = document.querySelector('#preferencesForm form');
    const formData = new FormData(form);
    const preferences = Object.fromEntries(formData.entries());

    // Always force auto_apply to true to ensure changes are applied immediately
    const autoApply = true; // Force this to true regardless of checkbox state

    // Add selected days and preset to preferences
    preferences.days = selectedDays;
    preferences.preset = selectedOptimizationPreset;
    preferences.auto_apply = autoApply;

    debugLog('Sending optimization request', preferences);
    
    try {
        // Send optimization request
        const response = await fetch('/CalendarAI/api/optimize.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(preferences)
        });
        
        // Check for HTTP errors
        if (!response.ok) {
            const errorText = await response.text();
            debugLog('Server error response', { status: response.status, body: errorText });
            throw new Error(`Server error (${response.status}): ${errorText}`);
        }

        const responseText = await response.text();
        debugLog('Raw server response', responseText);
        
        // Parse the JSON response safely
        let data;
        try {
            data = JSON.parse(responseText);
        } catch (parseError) {
            debugLog('JSON parse error', { 
                error: parseError.message,
                responseText: responseText.substring(0, 1000) // Log first 1000 chars
            });
            throw new Error(`Failed to parse server response: ${parseError.message}`);
        }
        
        // Hide loading
        document.getElementById('optimizationLoading').classList.add('hidden');
        
        if (data.success) {
            // If auto-applied, show success message and reload
            if (autoApply && data.changes_applied > 0) {
                Swal.fire({
                    title: 'Schedule Optimized!',
                    text: `Successfully applied ${data.changes_applied} changes to your schedule.`,
                    icon: 'success',
                    confirmButtonColor: '#10B981'
                }).then(() => {
                    location.reload(); // Reload to show updated schedule
                });
                return;
            }
            
            // Show results and apply changes button
            document.getElementById('optimizationResults').classList.remove('hidden');
            if (data.changes && data.changes.length > 0) {
                document.getElementById('applyChangesBtn').classList.remove('hidden');
            }
            
            // Display the optimization results
            displayOptimizationResult(data);
        } else {
            throw new Error(data.error || 'Optimization failed');
        }
    } catch (error) {
        // Handle errors
        document.getElementById('optimizationLoading').classList.add('hidden');
        document.getElementById('preferencesForm').classList.remove('hidden');
        document.getElementById('optimizeScheduleBtn').classList.remove('hidden');
        
        debugLog('Optimization error', {
            message: error.message,
            stack: error.stack
        });
        
        Swal.fire({
            title: 'Optimization Error',
            text: error.message,
            icon: 'error'
        });
    }
}