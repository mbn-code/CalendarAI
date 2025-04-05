-- Reset tables
TRUNCATE TABLE events;
TRUNCATE TABLE event_preferences;
TRUNCATE TABLE optimization_history;

-- Insert perfectly optimized schedule
INSERT INTO events (title, description, start_time, end_time, is_recurring, category, is_ai_optimized) VALUES
-- Day 1 - optimized
('Morning Standup', 'Daily team sync', '2025-04-08 09:00:00', '2025-04-08 09:30:00', 1, 'work', 1),
('Focus Time', 'Deep work block', '2025-04-08 09:45:00', '2025-04-08 11:45:00', 0, 'work', 1),
('Lunch Break', 'Recharge time', '2025-04-08 12:00:00', '2025-04-08 13:00:00', 1, 'break', 1),
('Team Meeting', 'Weekly sync', '2025-04-08 14:00:00', '2025-04-08 15:00:00', 1, 'work', 1),
('Exercise', 'Gym session', '2025-04-08 17:30:00', '2025-04-08 18:30:00', 1, 'personal', 1),

-- Day 2 - optimized
('Client Workshop', 'Product review', '2025-04-09 10:00:00', '2025-04-09 11:30:00', 0, 'work', 1),
('Quick Lunch', 'Light meal', '2025-04-09 12:00:00', '2025-04-09 12:30:00', 1, 'break', 1),
('Development', 'Code implementation', '2025-04-09 13:00:00', '2025-04-09 16:00:00', 0, 'work', 1),
('Team Social', 'Coffee chat', '2025-04-09 16:15:00', '2025-04-09 17:00:00', 0, 'break', 1),
('Yoga Class', 'Mind & body', '2025-04-09 17:30:00', '2025-04-09 18:30:00', 1, 'personal', 1),

-- Day 3 - optimized
('Project Planning', 'Sprint planning', '2025-04-10 09:30:00', '2025-04-10 11:00:00', 0, 'work', 1),
('Code Review', 'PR reviews', '2025-04-10 11:15:00', '2025-04-10 12:00:00', 0, 'work', 1),
('Lunch Break', 'Team lunch', '2025-04-10 12:15:00', '2025-04-10 13:15:00', 1, 'break', 1),
('Client Meeting', 'Status update', '2025-04-10 14:00:00', '2025-04-10 15:00:00', 0, 'work', 1),
('Documentation', 'Write tech docs', '2025-04-10 15:15:00', '2025-04-10 16:45:00', 0, 'work', 1),
('Evening Run', 'Cardio session', '2025-04-10 17:15:00', '2025-04-10 18:15:00', 1, 'personal', 1);

-- Insert optimized preferences
INSERT INTO event_preferences (category, preferred_time_start, preferred_time_end, priority) VALUES
('work', '09:00:00', '17:00:00', 1),
('break', '12:00:00', '14:00:00', 2),
('personal', '17:00:00', '20:00:00', 3);

-- Insert optimization history
INSERT INTO optimization_history (event_id, original_start, original_end, optimized_start, optimized_end, optimization_reason)
SELECT id, 
       DATE_SUB(start_time, INTERVAL 30 MINUTE),
       DATE_SUB(end_time, INTERVAL 30 MINUTE),
       start_time,
       end_time,
       'Optimized for better work-life balance and reduced context switching'
FROM events WHERE is_ai_optimized = 1;