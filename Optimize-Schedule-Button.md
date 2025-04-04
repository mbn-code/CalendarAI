# Optimize Schedule Button - Quick Reference

A simplified guide to the "Optimize Schedule" feature in CalendarAI, showing how components interact to reorganize calendar events efficiently.

## Components Overview

### Button Implementation (index.php)
```html
<button id="optimizeBtn" class="px-5 py-2.5 bg-purple-500 text-white rounded-lg">
    <i class="fas fa-magic mr-2"></i> Optimize Schedule
</button>
```

```javascript
// Event listener in index.php
const optimizeBtn = document.getElementById('optimizeBtn');
if (optimizeBtn) {
    optimizeBtn.addEventListener('click', showOptimizeModal);
}
```

### Frontend Flow (script.js)
1. User clicks button → `showOptimizeModal()` displays preferences dialog
2. User confirms → `optimizeSchedule(preferences)` sends data to API
3. API responds → Results displayed, page refreshes with optimized schedule

```javascript
// Key functions in script.js
async function showOptimizeModal() {
    // Shows dialog with preferences options
    // When user confirms, calls optimizeSchedule()
}

async function optimizeSchedule(preferences) {
    // Sends POST request to /api/optimize.php
    // Displays success/error message based on response
}
```

### Backend Processing (api/optimize.php)
1. Receives preferences via POST
2. Fetches upcoming events for the user
3. Analyzes event gaps and inefficiencies
4. Updates event timing to fill gaps
5. Returns changes made to frontend

```php
// Main optimization algorithm
foreach ($events as $event) {
    // Skip breaks to preserve their timing
    // Find gaps between events
    // If gap > 1 hour, move event to better time
    // Mark event as AI-optimized
    // Track changes for response
}
```

## Visual Feedback

### CSS for Optimized Events
```css
.event-pill.ai-optimized {
    background-color: #f0ebfe !important;
    border-color: #d8ccfd !important;
    color: #6941c6 !important;
}

.event-pill.ai-optimized::after {
    content: '✨'; /* Sparkles indicator */
}
```

### Optimization Progress Indicators
- Each day shows an optimization percentage via gradient bar
- Events display special styling and icons to indicate optimization state

## File Relationships

1. **index.php** → Contains button UI and event listeners
2. **script.js** → Contains modal UI and API communication functions
3. **api/optimize.php** → Backend endpoint that processes optimization
4. **CSS (in index.php)** → Styling for optimized events and indicators

## Data Flow
```
[Button Click] → [Preference Modal] → [API Request] → [Database Updates] → [Visual Indicators]
```

That's it! The "Optimize Schedule" feature helps users automatically organize their calendar events to make better use of their time.