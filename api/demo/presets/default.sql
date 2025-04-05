-- Reset tables
TRUNCATE TABLE events;
TRUNCATE TABLE event_preferences;
TRUNCATE TABLE optimization_history;

-- Insert sample events for a typical week
INSERT INTO events (title, description, start_time, end_time, is_recurring, category) VALUES
('Team Meeting', 'Weekly team sync-up', '2025-04-08 10:00:00', '2025-04-08 11:00:00', 1, 'work'),
('Lunch Break', 'Daily lunch break', '2025-04-08 12:00:00', '2025-04-08 13:00:00', 1, 'break'),
('Project Planning', 'Quarterly planning session', '2025-04-09 14:00:00', '2025-04-09 15:30:00', 0, 'work'),
('Gym', 'Workout session', '2025-04-10 17:00:00', '2025-04-10 18:00:00', 1, 'personal');

-- Insert default preferences
INSERT INTO event_preferences (category, preferred_time_start, preferred_time_end, priority) VALUES
('work', '09:00:00', '17:00:00', 1),
('break', '12:00:00', '14:00:00', 2),
('personal', '17:00:00', '20:00:00', 3);