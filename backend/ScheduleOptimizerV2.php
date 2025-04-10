<?php
/**
 * ScheduleOptimizerV2.php
 * 
 * Enhanced schedule optimization class using constraint-based global optimization
 * for more effective calendar scheduling and time management.
 */

class ScheduleOptimizerV2 {
    // Default optimization parameters
    private $defaultParams = [
        'min_break' => 15,             // Minimum break in minutes
        'max_consecutive_meetings' => 3, // Max consecutive events before requiring a break
        'focus_block_duration' => 60,  // Ideal focus block duration in minutes
        'break_frequency' => 4,        // Events before requiring a break
        'optimal_day_start' => '09:00', // Default workday start
        'optimal_day_end' => '17:00',  // Default workday end
        'spacing_buffer' => 15,        // Default spacing between events in minutes
        'preferred_meeting_duration' => 45 // Preferred meeting duration in minutes
    ];
      // Current parameters (merged from defaults and user preferences)
    private $params;
    
    // Events grouped by date
    private $eventsByDate = [];
    
    // Daily load tracker (total scheduled hours per day)
    private $dailyLoad = [];

    // Tracking metrics
    private $metrics = [
        'conflicts_resolved' => 0,
        'breaks_added' => 0,
        'events_moved' => 0,
        'events_split' => 0,
        'events_shortened' => 0,
        'paired_moves' => 0
    ];
    
    // List of generated changes
    private $changes = [];
    
    // Track paired events for animation
    private $pairedEvents = [];

    /**
     * Constructor
     * 
     * @param array $userPrefs User preferences to customize optimization
     * @param string $preset Selected optimization preset
     */
    public function __construct(array $userPrefs = [], string $preset = 'default') {
        // Initialize with default parameters
        $this->params = $this->defaultParams;
        
        // Apply preset-specific parameters
        $this->applyPreset($preset);
        
        // Override with user preferences (these take precedence)
        $this->applyUserPreferences($userPrefs);
        
        // Convert time-based parameters from minutes to seconds for internal calculations
        $this->convertTimeUnits();
    }

    /**
     * Apply preset-specific optimization parameters
     * 
     * @param string $preset The selected optimization preset
     */
    private function applyPreset(string $preset) {
        switch ($preset) {
            case 'busy_week':
                $this->params = array_merge($this->params, [
                    'min_break' => 15,
                    'max_consecutive_meetings' => 3,
                    'preferred_meeting_duration' => 45,
                    'focus_block_duration' => 90,
                    'spacing_buffer' => 10
                ]);
                break;
                
            case 'conflicts':
                $this->params = array_merge($this->params, [
                    'min_break' => 30,
                    'max_consecutive_meetings' => 2,
                    'conflict_resolution_priority' => 'high',
                    'spacing_buffer' => 15
                ]);
                break;
                
            case 'optimized':
                $this->params = array_merge($this->params, [
                    'min_break' => 30,
                    'max_consecutive_meetings' => 2,
                    'focus_block_duration' => 120,
                    'break_frequency' => 3,
                    'spacing_buffer' => 20
                ]);
                break;
                
            // Default preset uses the default parameters
        }
    }

    /**
     * Apply user preferences to override default and preset parameters
     * 
     * @param array $userPrefs User preferences from database
     */
    private function applyUserPreferences(array $userPrefs) {
        if (!empty($userPrefs)) {
            // Map user preference fields to parameter names
            $mappings = [
                'break_duration' => 'min_break',
                'session_length' => 'focus_block_duration',
                'focus_start_time' => 'optimal_day_start',
                'focus_end_time' => 'optimal_day_end'
            ];
            
            // Override parameters with user preferences
            foreach ($mappings as $prefKey => $paramKey) {
                if (isset($userPrefs[$prefKey]) && !empty($userPrefs[$prefKey])) {
                    $this->params[$paramKey] = $userPrefs[$prefKey];
                }
            }
            
            // Apply priority mode if specified
            if (isset($userPrefs['priority_mode'])) {
                switch ($userPrefs['priority_mode']) {
                    case 'focus':
                        // Prioritize longer focus blocks
                        $this->params['focus_block_duration'] = max($this->params['focus_block_duration'], 90);
                        $this->params['break_frequency'] = 5; // Less frequent breaks
                        break;
                        
                    case 'balance':
                        // Balance work and breaks
                        $this->params['break_frequency'] = 3;
                        break;
                        
                    case 'wellbeing':
                        // More breaks, shorter work periods
                        $this->params['min_break'] = max($this->params['min_break'], 20);
                        $this->params['break_frequency'] = 2; // More frequent breaks
                        $this->params['focus_block_duration'] = min($this->params['focus_block_duration'], 50);
                        break;
                }
            }
        }
    }
    
    /**
     * Convert minutes to seconds for time calculations
     */
    private function convertTimeUnits() {
        // Convert time-based parameters from minutes to seconds for calculations
        $this->params['min_break_seconds'] = $this->params['min_break'] * 60;
        $this->params['focus_block_seconds'] = $this->params['focus_block_duration'] * 60;
        $this->params['spacing_buffer_seconds'] = $this->params['spacing_buffer'] * 60;
        $this->params['preferred_meeting_seconds'] = $this->params['preferred_meeting_duration'] * 60;
    }
    
    /**
     * Add events to the optimizer
     * 
     * @param array $events List of calendar events to optimize
     * @return $this for method chaining
     */
    public function addEvents(array $events) {
        foreach ($events as $event) {
            $this->validateAndAddEvent($event);
        }
        
        // Sort events by date and start time
        foreach ($this->eventsByDate as &$dayEvents) {
            usort($dayEvents, function($a, $b) {
                return $a['start'] - $b['start'];
            });
        }
        
        return $this;
    }
    
    /**
     * Validate and add a single event to the optimizer
     * 
     * @param array $event Event data from database
     */
    private function validateAndAddEvent(array $event) {
        if (empty($event['start_date'])) {
            return;
        }
        
        $start = strtotime($event['start_date']);
        if ($start === false) {
            return;
        }
        
        $end = !empty($event['end_date']) && strtotime($event['end_date']) !== false 
            ? strtotime($event['end_date']) 
            : $start + 3600; // Default 1 hour
        
        // Extract the original date (YYYY-MM-DD)
        $date = date('Y-m-d', $start);
        
        // Determine event type (study session vs other)
        $isStudy = (stripos($event['title'] ?? '', 'study') !== false) || 
                   (stripos($event['category'] ?? '', 'study') !== false) ||
                   (stripos($event['description'] ?? '', 'study') !== false);
        
        // Add to events by date
        if (!isset($this->eventsByDate[$date])) {
            $this->eventsByDate[$date] = [];
            $this->dailyLoad[$date] = 0;
        }
        
        // Store processed event data
        $this->eventsByDate[$date][] = [
            'event' => $event,
            'start' => $start,
            'end' => $end,
            'duration' => $end - $start,
            'isStudy' => $isStudy,
            'isBreak' => (stripos($event['title'] ?? '', 'break') !== false),
            'isImmovable' => !empty($event['is_immovable'])
        ];
        
        // Update daily load
        $this->dailyLoad[$date] += ($end - $start) / 3600; // Hours
    }
    
    /**
     * Perform global schedule optimization
     * 
     * @param string $preset Selected optimization preset
     * @return $this for method chaining
     */
    public function optimize(string $preset = 'default') {
        if (empty($this->eventsByDate)) {
            return $this;
        }
        
        // 1. First pass - identify conflicts and gaps
        $this->identifyConflictsAndGaps();
        
        // 2. Second pass - resolve conflicts and schedule breaks
        foreach ($this->eventsByDate as $date => $dayEvents) {
            // Apply optimization strategies based on preset
            switch ($preset) {
                case 'busy_week':
                    $this->optimizeBusyWeek($date, $dayEvents);
                    break;
                    
                case 'conflicts':
                    $this->resolveConflicts($date, $dayEvents);
                    break;
                    
                case 'optimized':
                    $this->applyIdealSpacing($date, $dayEvents);
                    break;
                    
                default:
                    $this->basicOptimization($date, $dayEvents);
                    break;
            }
            
            // Always ensure mandatory breaks
            $this->ensureMandatoryBreaks($date);
            
            // Balance workload across days if needed
            $this->balanceDailyWorkload();
        }
        
        return $this;
    }
    
    /**
     * Identify conflicts and gaps in the current schedule
     */
    private function identifyConflictsAndGaps() {
        foreach ($this->eventsByDate as $date => $dayEvents) {
            $conflicts = [];
            $gaps = [];
            
            // Create a workday timeframe
            $workdayStart = strtotime($date . ' ' . $this->params['optimal_day_start']);
            $workdayEnd = strtotime($date . ' ' . $this->params['optimal_day_end']);
            
            // Check for conflicts
            for ($i = 0; $i < count($dayEvents); $i++) {
                $event = $dayEvents[$i];
                
                // Skip immovable events for conflict detection
                if ($event['isImmovable']) {
                    continue;
                }
                
                // Check if event is outside working hours
                if ($event['start'] < $workdayStart || $event['end'] > $workdayEnd) {
                    $this->changes[] = [
                        'event_id' => $event['event']['id'],
                        'issue' => 'outside_hours',
                        'current_start' => date('Y-m-d H:i:s', $event['start']),
                        'current_end' => date('Y-m-d H:i:s', $event['end']),
                        'workday_start' => date('Y-m-d H:i:s', $workdayStart),
                        'workday_end' => date('Y-m-d H:i:s', $workdayEnd)
                    ];
                }
                
                // Check for overlap with next event
                if ($i < count($dayEvents) - 1) {
                    $nextEvent = $dayEvents[$i + 1];
                    
                    if ($event['end'] > $nextEvent['start']) {
                        $conflicts[] = [
                            'event1' => $event,
                            'event2' => $nextEvent,
                            'overlap' => $event['end'] - $nextEvent['start']
                        ];
                    } else {
                        // Check for gaps
                        $gap = $nextEvent['start'] - $event['end'];
                        
                        if ($gap > $this->params['spacing_buffer_seconds'] * 3) {
                            $gaps[] = [
                                'after_event' => $event,
                                'before_event' => $nextEvent, 
                                'gap_duration' => $gap,
                                'gap_start' => $event['end'],
                                'gap_end' => $nextEvent['start']
                            ];
                        }
                    }
                }
            }
            
            // Store conflicts and gaps for this date
            $this->eventsByDate[$date]['_conflicts'] = $conflicts;
            $this->eventsByDate[$date]['_gaps'] = $gaps;
        }
    }
    
    /**
     * Optimization strategy for busy weeks
     * Focus on efficiency and compact scheduling
     * 
     * @param string $date The current date
     * @param array $dayEvents Events for this day
     */
    private function optimizeBusyWeek($date, $dayEvents) {
        // First resolve any conflicts
        $this->resolveConflicts($date, $dayEvents);
        
        // Then compact the schedule by utilizing gaps
        $this->compactSchedule($date);
        
        // If the day is extremely busy, move some events to less busy days
        if ($this->dailyLoad[$date] > 8) { // More than 8 hours of events
            $this->redistributeWorkload($date);
        }
    }
    
    /**
     * Resolve scheduling conflicts
     * 
     * @param string $date The current date
     * @param array $dayEvents Events for this day
     */
    private function resolveConflicts($date, $dayEvents) {
        if (empty($this->eventsByDate[$date]['_conflicts'])) {
            return;
        }
        
        $conflicts = $this->eventsByDate[$date]['_conflicts'];
        $workdayStart = strtotime($date . ' ' . $this->params['optimal_day_start']);
        $workdayEnd = strtotime($date . ' ' . $this->params['optimal_day_end']);
        
        foreach ($conflicts as $conflict) {
            // Skip if either event is immovable
            if ($conflict['event1']['isImmovable'] || $conflict['event2']['isImmovable']) {
                continue;
            }
            
            // Determine which event to move
            $moveEvent1 = $this->shouldMoveEvent($conflict['event1'], $conflict['event2']);
            $eventToMove = $moveEvent1 ? $conflict['event1'] : $conflict['event2'];
            $otherEvent = $moveEvent1 ? $conflict['event2'] : $conflict['event1'];
            
            // Calculate new start time (after the other event + buffer)
            $newStart = $otherEvent['end'] + $this->params['spacing_buffer_seconds'];
            
            // Ensure the event still fits in the workday, otherwise move to another day
            if ($newStart + $eventToMove['duration'] > $workdayEnd) {
                $this->moveEventToAnotherDay($eventToMove, $date);
            } else {
                $this->changes[] = [
                    'event_id' => $eventToMove['event']['id'],
                    'new_time' => date('Y-m-d H:i:s', $newStart),
                    'duration' => round($eventToMove['duration'] / 60),
                    'reason' => 'Resolved scheduling conflict',
                    'action' => 'move'
                ];
                
                $this->metrics['conflicts_resolved']++;
            }
        }
    }
    
    /**
     * Determine which event should be moved in a conflict
     * 
     * @param array $event1 First event
     * @param array $event2 Second event
     * @return bool True if event1 should be moved, false if event2
     */
    private function shouldMoveEvent($event1, $event2) {
        // Prioritize keeping study sessions in place
        if ($event1['isStudy'] && !$event2['isStudy']) {
            return false;
        } else if (!$event1['isStudy'] && $event2['isStudy']) {
            return true;
        }
        
        // Otherwise move the shorter event
        return $event1['duration'] <= $event2['duration'];
    }
    
    /**
     * Apply ideal spacing and timing to events
     * 
     * @param string $date The current date
     * @param array $dayEvents Events for this day
     */
    private function applyIdealSpacing($date, $dayEvents) {
        // First resolve conflicts
        $this->resolveConflicts($date, $dayEvents);
        
        $workdayStart = strtotime($date . ' ' . $this->params['optimal_day_start']);
        $workdayEnd = strtotime($date . ' ' . $this->params['optimal_day_end']);
        
        // Now optimize spacing
        $lastEndTime = $workdayStart;
        $processedEvents = [];
        
        // Process all non-immovable events first to calculate optimal positions
        foreach ($dayEvents as $event) {
            // Skip immovable events initially
            if ($event['isImmovable'] || isset($event['_processed'])) {
                continue;
            }
            
            // Calculate optimal start time
            $optimalStart = $lastEndTime + $this->params['spacing_buffer_seconds'];
            
            // If event is more than 15 minutes from optimal, reschedule it
            if (abs($event['start'] - $optimalStart) > 900) {
                $newStart = $optimalStart;
                
                // Ensure it fits in workday
                if ($newStart + $event['duration'] <= $workdayEnd) {
                    $this->changes[] = [
                        'event_id' => $event['event']['id'],
                        'new_time' => date('Y-m-d H:i:s', $newStart),
                        'duration' => round($event['duration'] / 60),
                        'reason' => 'Optimized spacing for better productivity',
                        'action' => 'move'
                    ];
                    
                    $lastEndTime = $newStart + $event['duration'];
                    $this->metrics['events_moved']++;
                } else {
                    // If it doesn't fit, try to move to another day
                    $this->moveEventToAnotherDay($event, $date);
                }
            } else {
                $lastEndTime = $event['end'];
            }
            
            $event['_processed'] = true;
            $processedEvents[] = $event;
        }
        
        // Now process immovable events and work around them
        foreach ($dayEvents as $event) {
            if (!$event['isImmovable'] || isset($event['_processed'])) {
                continue;
            }
            
            // For immovable events, just update the last end time
            $lastEndTime = max($lastEndTime, $event['end']);
        }
    }
    
    /**
     * Basic optimization strategy - resolves conflicts but minimal changes otherwise
     * 
     * @param string $date The current date
     * @param array $dayEvents Events for this day
     */
    private function basicOptimization($date, $dayEvents) {
        // Resolve conflicts first
        $this->resolveConflicts($date, $dayEvents);
        
        // Break up very long study sessions
        foreach ($dayEvents as $event) {
            if ($event['isStudy'] && $event['duration'] > $this->params['focus_block_seconds'] * 1.5) {
                $this->splitLongEvent($event, $date);
            }
        }
    }
    
    /**
     * Compact schedule by minimizing gaps
     * 
     * @param string $date Current date
     */
    private function compactSchedule($date) {
        if (empty($this->eventsByDate[$date]['_gaps'])) {
            return;
        }
        
        $gaps = $this->eventsByDate[$date]['_gaps'];
        
        // Only address large gaps (> 30 min)
        foreach ($gaps as $gap) {
            if ($gap['gap_duration'] > 1800) { // 30 minutes
                $eventAfterGap = $gap['before_event'];
                
                // Don't move immovable events
                if ($eventAfterGap['isImmovable']) {
                    continue;
                }
                
                // Move the event to fill the gap
                $newStart = $gap['gap_start'] + $this->params['spacing_buffer_seconds'];
                
                $this->changes[] = [
                    'event_id' => $eventAfterGap['event']['id'],
                    'new_time' => date('Y-m-d H:i:s', $newStart),
                    'duration' => round($eventAfterGap['duration'] / 60),
                    'reason' => 'Compacted schedule to improve efficiency',
                    'action' => 'move'
                ];
                
                $this->metrics['events_moved']++;
            }
        }
    }
    
    /**
     * Split a long event into smaller chunks
     * 
     * @param array $event The event to split
     * @param string $date Current date
     */
    private function splitLongEvent($event, $date) {
        // Don't split immovable events
        if ($event['isImmovable']) {
            return;
        }
        
        // Calculate optimal chunk size
        $optimalDuration = $this->params['focus_block_seconds'];
        
        // If event is less than 1.5x the optimal duration, just shorten it
        if ($event['duration'] <= $optimalDuration * 1.5) {
            $this->changes[] = [
                'event_id' => $event['event']['id'],
                'new_time' => date('Y-m-d H:i:s', $event['start']),
                'duration' => round($optimalDuration / 60),
                'reason' => 'Adjusted duration for optimal focus',
                'action' => 'move'
            ];
            
            $this->metrics['events_shortened']++;
            return;
        }
        
        // Otherwise split into multiple parts
        $remainingDuration = $event['duration'];
        $currentStart = $event['start'];
        $isFirstChunk = true;
        
        while ($remainingDuration > 0) {
            // Determine chunk duration (use optimal or remainder, whichever is smaller)
            $chunkDuration = min($optimalDuration, $remainingDuration);
            
            if ($isFirstChunk) {
                // First chunk modifies the original event
                $this->changes[] = [
                    'event_id' => $event['event']['id'],
                    'new_time' => date('Y-m-d H:i:s', $currentStart),
                    'duration' => round($chunkDuration / 60),
                    'reason' => 'Split into focused blocks for better productivity',
                    'action' => 'move'
                ];
                
                $isFirstChunk = false;
            } else {
                // Determine if this chunk should be on same day or next day
                $chunkEnd = $currentStart + $chunkDuration;
                $workdayEnd = strtotime($date . ' ' . $this->params['optimal_day_end']);
                
                if ($chunkEnd > $workdayEnd) {
                    // Move to next day
                    $tomorrow = date('Y-m-d', strtotime($date . ' +1 day'));
                    $tomorrowStart = strtotime($tomorrow . ' ' . $this->params['optimal_day_start']);
                    
                    $this->changes[] = [
                        'event_id' => 'new_split_event',
                        'title' => $event['event']['title'] . ' (continued)',
                        'new_time' => date('Y-m-d H:i:s', $tomorrowStart),
                        'duration' => round($chunkDuration / 60),
                        'reason' => 'Split long session across days for better focus',
                        'parent_id' => $event['event']['id'],
                        'action' => 'create'
                    ];
                    
                    // Update the daily load for the next day
                    $this->ensureDailyLoadInitialized($tomorrow);
                    $this->dailyLoad[$tomorrow] += $chunkDuration / 3600;
                } else {
                    // Create a new event for subsequent chunks on same day
                    $this->changes[] = [
                        'event_id' => 'new_split_event',
                        'title' => $event['event']['title'] . ' (continued)',
                        'new_time' => date('Y-m-d H:i:s', $currentStart),
                        'duration' => round($chunkDuration / 60),
                        'reason' => 'Split into focused blocks for better productivity',
                        'parent_id' => $event['event']['id'],
                        'action' => 'create'
                    ];
                }
            }
            
            // Update tracking variables
            $remainingDuration -= $chunkDuration;
            $currentStart += $chunkDuration + $this->params['min_break_seconds']; // Add break between chunks
            $this->metrics['events_split']++;
        }
    }
    
    /**
     * Ensure daily load tracking is initialized for a given date
     * 
     * @param string $date Date to initialize
     */
    private function ensureDailyLoadInitialized($date) {
        if (!isset($this->dailyLoad[$date])) {
            $this->dailyLoad[$date] = 0;
        }
        
        if (!isset($this->eventsByDate[$date])) {
            $this->eventsByDate[$date] = [];
        }
    }    /**
     * Move an event to another day with less load
     * 
     * @param array $event Event to move
     * @param string $currentDate Current date
     * @param string $pairId Optional ID for pairing events in the animation
     * @return string The pair ID used (new or provided)
     */
    private function moveEventToAnotherDay($event, $currentDate, $pairId = null) {
        // Only move non-immovable events
        if ($event['isImmovable']) {
            return null;
        }
        
        // Find a day with less load in the next 3 days
        $targetDate = null;
        $minLoad = PHP_FLOAT_MAX;
        
        for ($i = 1; $i <= 3; $i++) {
            $checkDate = date('Y-m-d', strtotime($currentDate . " +$i day"));
            $this->ensureDailyLoadInitialized($checkDate);
            
            if ($this->dailyLoad[$checkDate] < $minLoad) {
                $minLoad = $this->dailyLoad[$checkDate];
                $targetDate = $checkDate;
            }
        }
        
        if ($targetDate) {
            // Calculate new start time on target date
            $newStart = strtotime($targetDate . ' ' . $this->params['optimal_day_start']);
            
            // Generate a new pair ID if not provided
            if (!$pairId) {
                $pairId = 'pair_' . uniqid();
            }
            
            // Calculate animation properties for a premium feel
            $daysOffset = (strtotime($targetDate) - strtotime($currentDate)) / 86400; // Number of days moved
            $animationType = $this->getAnimationType($event);
            $animationDuration = min(1200, 800 + (abs($daysOffset) * 200)); // Longer animation for farther moves
            $animationEasing = "cubic-bezier(0.34, 1.56, 0.64, 1)"; // Bouncy premium easing
            $animationColor = $this->getEventColor($event);
            
            $this->changes[] = [
                'event_id' => $event['event']['id'],
                'new_time' => date('Y-m-d H:i:s', $newStart),
                'duration' => round($event['duration'] / 60),
                'reason' => "Moved to $targetDate to balance workload",
                'action' => 'move',
                'animation_pair_id' => $pairId,
                'from_date' => $currentDate,
                'to_date' => $targetDate,
                'animation' => [
                    'type' => $animationType,
                    'duration' => $animationDuration,
                    'easing' => $animationEasing,
                    'color' => $animationColor,
                    'highlight_path' => true,
                    'show_connector' => true,
                    'days_offset' => $daysOffset
                ]
            ];
            
            // Track this pair
            $this->pairedEvents[$pairId][] = $event['event']['id'];
            
            // Update daily load tracking
            $this->dailyLoad[$currentDate] -= $event['duration'] / 3600;
            $this->dailyLoad[$targetDate] += $event['duration'] / 3600;
            $this->metrics['events_moved']++;
            
            return $pairId;
        }
        
        return null;
    }
    
    /**
     * Get appropriate animation type based on event properties
     * 
     * @param array $event The event to animate
     * @return string Animation type name
     */
    private function getAnimationType($event) {
        $types = ['slide-fade', 'bounce', 'elastic', 'glide', 'arc-path'];
        
        // For study events, use more focused animations
        if ($event['isStudy']) {
            return 'arc-path';
        }
        
        // For breaks, use gentler animations
        if ($event['isBreak']) {
            return 'glide';
        }
        
        // For longer events, use more substantial animations
        if ($event['duration'] > 3600) {
            return 'bounce';
        }
        
        // Otherwise use slide-fade as default
        return 'slide-fade';
    }
    
    /**
     * Get a color for the event animation based on event type
     * 
     * @param array $event The event to animate
     * @return string Color in hexadecimal or rgba format
     */
    private function getEventColor($event) {
        // If the event has a category with color, use that
        if (isset($event['event']['category_id'])) {
            // Default category colors if not specified
            $categoryColors = [
                1 => '#4285F4', // Blue (Work)
                2 => '#EA4335', // Red (Important)
                3 => '#FBBC05', // Yellow (Personal)
                4 => '#34A853', // Green (Study)
                5 => '#9C27B0', // Purple (Focus)
                6 => '#FF9800', // Orange (Meetings)
                7 => '#795548', // Brown (Other)
                8 => '#607D8B'  // Blue Grey (Break)
            ];
            
            $categoryId = (int)$event['event']['category_id'];
            if (isset($categoryColors[$categoryId])) {
                return $categoryColors[$categoryId];
            }
        }
        
        // Otherwise base color on event type
        if ($event['isStudy']) {
            return '#34A853'; // Green for study
        } else if ($event['isBreak']) {
            return '#607D8B'; // Blue Grey for breaks
        } else {
            return '#4285F4'; // Blue as default
        }
    }
    
    /**
     * Ensure mandatory breaks are inserted throughout the day
     * 
     * @param string $date Current date
     */
    private function ensureMandatoryBreaks($date) {
        if (empty($this->eventsByDate[$date])) {
            return;
        }
        
        $dayEvents = $this->eventsByDate[$date];
        $workdayStart = strtotime($date . ' ' . $this->params['optimal_day_start']);
        $workdayEnd = strtotime($date . ' ' . $this->params['optimal_day_end']);
        
        // Track consecutive events
        $consecutiveEvents = 0;
        $lastEventEnd = $workdayStart;
        $eventTimes = [];
        
        // First collect all event times (including newly scheduled ones)
        foreach ($dayEvents as $event) {
            if (is_array($event) && isset($event['event'])) {
                $eventTimes[] = [
                    'start' => $event['start'],
                    'end' => $event['end'],
                    'isBreak' => $event['isBreak']
                ];
            }
        }
        
        // Add events from changes
        foreach ($this->changes as $change) {
            if (isset($change['new_time']) && strpos($change['new_time'], $date) === 0) {
                $newStart = strtotime($change['new_time']);
                $newEnd = $newStart + ($change['duration'] * 60);
                
                $eventTimes[] = [
                    'start' => $newStart,
                    'end' => $newEnd,
                    'isBreak' => (isset($change['event_id']) && $change['event_id'] === 'new_break')
                ];
            }
        }
        
        // Sort by start time
        usort($eventTimes, function($a, $b) {
            return $a['start'] - $b['start'];
        });
        
        // Process events to identify break needs
        $lastEventEnd = $workdayStart;
        $consecutiveEvents = 0;
        
        foreach ($eventTimes as $event) {
            // Reset counter if we already encountered a break
            if ($event['isBreak']) {
                $consecutiveEvents = 0;
                $lastEventEnd = $event['end'];
                continue;
            }
            
            $consecutiveEvents++;
            
            // Check if we need to add a break
            if ($consecutiveEvents >= $this->params['max_consecutive_meetings']) {
                // Only add break if there's sufficient gap after this event
                $breakStart = $event['end'] + 300; // 5 min after event
                $breakEnd = $breakStart + $this->params['min_break_seconds'];
                
                // Check if break fits before next event or end of day
                $nextEventStart = $workdayEnd;
                foreach ($eventTimes as $nextEvent) {
                    if ($nextEvent['start'] > $event['end'] && $nextEvent['start'] < $nextEventStart) {
                        $nextEventStart = $nextEvent['start'];
                    }
                }
                
                if ($breakEnd <= $nextEventStart - 300) { // Ensure 5 min gap after break too
                    $this->changes[] = [
                        'event_id' => 'new_break',
                        'title' => 'Break',
                        'new_time' => date('Y-m-d H:i:s', $breakStart),
                        'duration' => round($this->params['min_break_seconds'] / 60),
                        'reason' => 'Added scheduled break to improve productivity',
                        'action' => 'create'
                    ];
                    
                    $consecutiveEvents = 0;
                    $this->metrics['breaks_added']++;
                }
            }
            
            $lastEventEnd = $event['end'];
        }
    }
      /**
     * Balance workload across days when some days are overloaded
     */
    private function balanceDailyWorkload() {
        // Define daily load threshold for moving events
        $loadThreshold = 7; // 7 hours per day
        
        // Sort days by load to find overloaded days
        arsort($this->dailyLoad); // Sort in descending order
        
        // Process overloaded days
        foreach ($this->dailyLoad as $date => $load) {
            if ($load <= $loadThreshold) {
                continue; // Not overloaded
            }
            
            // This day is overloaded - move some events
            if (empty($this->eventsByDate[$date])) {
                continue;
            }
            
            // Identify movable events (prioritize non-study events)
            $movableEvents = [];
            foreach ($this->eventsByDate[$date] as $event) {
                if (is_array($event) && isset($event['event']) && !$event['isImmovable']) {
                    $movableEvents[] = $event;
                }
            }
            
            // Sort by priority for moving (non-study events first, then shorter events)
            usort($movableEvents, function($a, $b) {
                if ($a['isStudy'] !== $b['isStudy']) {
                    return $a['isStudy'] ? 1 : -1; // Non-study first
                }
                return $a['duration'] - $b['duration']; // Shorter first
            });
            
            // Calculate how much time needs to be moved
            $excessHours = $load - $loadThreshold;
            $secondsToMove = $excessHours * 3600;
            
            // Group events by similarity to move them in pairs
            $groupedEvents = $this->groupSimilarEvents($movableEvents);
            
            // First move grouped events (in pairs)
            foreach ($groupedEvents as $group) {
                if ($secondsToMove <= 0) {
                    break;
                }
                
                if (count($group) >= 2) {
                    // Generate a shared pair ID for this group
                    $pairId = 'pair_' . uniqid();
                    $this->metrics['paired_moves']++;
                    
                    // Move events in this group together (visually connected)
                    foreach ($group as $event) {
                        $this->moveEventToAnotherDay($event, $date, $pairId);
                        $secondsToMove -= $event['duration'];
                        
                        if ($secondsToMove <= 0) {
                            break;
                        }
                    }
                }
            }
            
            // Then move remaining events individually if needed
            if ($secondsToMove > 0) {
                foreach ($movableEvents as $event) {
                    // Skip events that have already been moved as part of a group
                    $alreadyMoved = false;
                    foreach ($this->pairedEvents as $pairId => $eventIds) {
                        if (in_array($event['event']['id'], $eventIds)) {
                            $alreadyMoved = true;
                            break;
                        }
                    }
                    
                    if (!$alreadyMoved) {
                        if ($secondsToMove <= 0) {
                            break;
                        }
                        
                        // Move this event to another day
                        $this->moveEventToAnotherDay($event, $date);
                        
                        // Update tracking
                        $secondsToMove -= $event['duration'];
                    }
                }
            }
        }
    }
    
    /**
     * Group similar events that should be moved together
     * 
     * @param array $events List of events to group
     * @return array Grouped events
     */
    private function groupSimilarEvents($events) {
        $groups = [];
        $processedEvents = [];
        
        // First group by category/type
        foreach ($events as $i => $event1) {
            if (in_array($i, $processedEvents)) {
                continue;
            }
            
            $group = [$event1];
            $processedEvents[] = $i;
            
            // Find similar events
            foreach ($events as $j => $event2) {
                if ($i == $j || in_array($j, $processedEvents)) {
                    continue;
                }
                
                // Check similarity (same category, similar duration, title similarity, etc.)
                if ($this->eventsAreSimilar($event1, $event2)) {
                    $group[] = $event2;
                    $processedEvents[] = $j;
                    
                    // Limit group size to 2-3 events for visual clarity
                    if (count($group) >= 3) {
                        break;
                    }
                }
            }
            
            if (count($group) > 1) {
                $groups[] = $group;
            }
        }
        
        // Add any remaining individual events
        foreach ($events as $i => $event) {
            if (!in_array($i, $processedEvents)) {
                $groups[] = [$event];
            }
        }
        
        return $groups;
    }
    
    /**
     * Determine if two events are similar enough to be paired in animation
     * 
     * @param array $event1 First event
     * @param array $event2 Second event
     * @return bool True if events should be paired
     */
    private function eventsAreSimilar($event1, $event2) {
        // Check for same category
        $sameCategoryOrType = false;
        
        // Check category field
        if (!empty($event1['event']['category']) && !empty($event2['event']['category'])) {
            $sameCategoryOrType = ($event1['event']['category'] == $event2['event']['category']);
        }
        
        // Check if both are study events
        if ($event1['isStudy'] && $event2['isStudy']) {
            $sameCategoryOrType = true;
        }
        
        // Check similarity in duration (within 20% of each other)
        $similarDuration = false;
        $durationRatio = max($event1['duration'], $event2['duration']) / 
                         max(1, min($event1['duration'], $event2['duration']));
        if ($durationRatio < 1.2) {
            $similarDuration = true;
        }
        
        // Check title similarity
        $titleSimilarity = false;
        $title1 = strtolower($event1['event']['title'] ?? '');
        $title2 = strtolower($event2['event']['title'] ?? '');
        
        if (!empty($title1) && !empty($title2)) {
            // Check for common words in titles
            $words1 = preg_split('/\W+/', $title1, -1, PREG_SPLIT_NO_EMPTY);
            $words2 = preg_split('/\W+/', $title2, -1, PREG_SPLIT_NO_EMPTY);
            
            $commonWords = array_intersect($words1, $words2);
            if (count($commonWords) > 0) {
                $titleSimilarity = true;
            }
        }
        
        // Return true if at least 2 of the 3 criteria match
        $criteriaMatched = ($sameCategoryOrType ? 1 : 0) + 
                          ($similarDuration ? 1 : 0) + 
                          ($titleSimilarity ? 1 : 0);
        
        return $criteriaMatched >= 2;
    }
    
    /**
     * Get the suggested changes from optimization
     * 
     * @return array List of changes
     */
    public function getChanges() {
        return $this->changes;
    }
    
    /**
     * Get metrics about the optimization process
     * 
     * @return array Metrics
     */
    public function getMetrics() {
        return $this->metrics;
    }
    
    /**
     * Calculate the health of the schedule after optimization
     * 
     * @return array Health metrics
     */
    public function calculateScheduleHealth() {
        $totalEvents = 0;
        foreach ($this->eventsByDate as $date => $events) {
            foreach ($events as $event) {
                if (is_array($event) && isset($event['event'])) {
                    $totalEvents++;
                }
            }
        }
        
        if ($totalEvents === 0) {
            return [
                'focus_time_utilization' => 100,
                'break_compliance' => 100,
                'conflict_score' => 0,
                'balance_score' => 100
            ];
        }
        
        // Calculate break compliance
        $breakRatio = $this->metrics['breaks_added'] / max(1, ceil($totalEvents / $this->params['break_frequency']));
        
        // Calculate balance score based on daily load variance
        $loadVariance = 0;
        if (count($this->dailyLoad) > 1) {
            $avgLoad = array_sum($this->dailyLoad) / count($this->dailyLoad);
            $loadVariances = [];
            
            foreach ($this->dailyLoad as $load) {
                $loadVariances[] = pow($load - $avgLoad, 2);
            }
            
            $loadVariance = array_sum($loadVariances) / count($this->dailyLoad);
        }
        
        // Higher variance means lower balance score
        $balanceScore = min(100, max(0, 100 - ($loadVariance * 25)));
        
        return [
            'focus_time_utilization' => min(100, round(($totalEvents - $this->metrics['conflicts_resolved']) / $totalEvents * 100)),
            'break_compliance' => min(100, round($breakRatio * 100)),
            'conflict_score' => max(0, $this->metrics['conflicts_resolved']),
            'balance_score' => round($balanceScore)
        ];
    }
    
    /**
     * Generate insights based on schedule changes
     * 
     * @param string $preset Selected optimization preset
     * @return array List of insights
     */
    public function generateInsights($preset) {
        $insights = [];
        $metrics = $this->metrics;
        $health = $this->calculateScheduleHealth();
        
        // Add preset-specific insights
        switch ($preset) {
            case 'busy_week':
                $insights[] = "Optimized schedule for maximum efficiency with minimal gaps";
                if ($metrics['events_moved'] > 0) {
                    $insights[] = "Moved {$metrics['events_moved']} events to create a more efficient schedule";
                }
                if ($metrics['breaks_added'] > 0) {
                    $insights[] = "Maintained essential breaks while maximizing productive time";
                }
                break;
                
            case 'conflicts':
                if ($metrics['conflicts_resolved'] > 0) {
                    $insights[] = "Resolved {$metrics['conflicts_resolved']} scheduling conflicts";
                }
                $insights[] = "Improved schedule flow by eliminating overlapping events";
                break;
                
            case 'optimized':
                $insights[] = "Applied best practices for optimal work-life balance";
                if ($metrics['breaks_added'] > 0) {
                    $insights[] = "Added {$metrics['breaks_added']} strategic breaks to enhance productivity";
                }
                if ($metrics['events_split'] > 0) {
                    $insights[] = "Divided {$metrics['events_split']} long sessions into more manageable focus blocks";
                }
                break;
                
            default:
                $insights[] = "Basic schedule optimization complete";
                if ($metrics['conflicts_resolved'] > 0) {
                    $insights[] = "Resolved {$metrics['conflicts_resolved']} scheduling conflicts";
                }
        }
        
        // Add health-based insights
        if ($health['focus_time_utilization'] > 80) {
            $insights[] = "Excellent focus time utilization at {$health['focus_time_utilization']}%";
        }
        
        if ($health['break_compliance'] < 60) {
            $insights[] = "Consider adding more breaks to improve productivity";
        }
        
        // Add workload distribution insights
        if ($metrics['events_moved'] > 0) {
            $insights[] = "Redistributed workload to achieve better daily balance";
        }
        
        return $insights;
    }
}
