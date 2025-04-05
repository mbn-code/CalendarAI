-- Reset tables
TRUNCATE TABLE events;
TRUNCATE TABLE event_preferences;
TRUNCATE TABLE optimization_history;

-- Insert schedule with obvious conflicts
INSERT INTO events (title, description, start_time, end_time, is_recurring, category) VALUES
-- Day 1 conflicts
('Team Meeting', 'Weekly sync', '2025-04-08 10:00:00', '2025-04-08 11:30:00', 1, 'work'),
('Client Call', 'Project update', '2025-04-08 11:00:00', '2025-04-08 12:00:00', 0, 'work'),
('Lunch Break', 'Team lunch', '2025-04-08 12:00:00', '2025-04-08 13:00:00', 1, 'break'),
('Project Review', 'Sprint review', '2025-04-08 12:30:00', '2025-04-08 13:30:00', 0, 'work'),

-- Day 2 conflicts
('Morning Meeting', 'Department sync', '2025-04-09 09:00:00', '2025-04-09 10:30:00', 0, 'work'),
('Training Session', 'New tool training', '2025-04-09 10:00:00', '2025-04-09 11:00:00', 0, 'work'),
('Lunch', 'Quick lunch', '2025-04-09 13:00:00', '2025-04-09 14:00:00', 1, 'break'),
('Code Review', 'Review PRs', '2025-04-09 13:30:00', '2025-04-09 14:30:00', 0, 'work'),
('Team Building', 'Office games', '2025-04-09 14:00:00', '2025-04-09 15:00:00', 0, 'work'),

-- Day 3 with evening conflicts
('Project Demo', 'Client demo', '2025-04-10 16:00:00', '2025-04-10 17:00:00', 0, 'work'),
('Gym Class', 'HIIT workout', '2025-04-10 16:30:00', '2025-04-10 17:30:00', 1, 'personal'),
('Evening Meeting', 'International call', '2025-04-10 17:00:00', '2025-04-10 18:00:00', 0, 'work');

-- Insert preferences that highlight the conflicts
INSERT INTO event_preferences (category, preferred_time_start, preferred_time_end, priority) VALUES
('work', '09:00:00', '17:00:00', 1),
('break', '12:00:00', '14:00:00', 3),
('personal', '17:00:00', '20:00:00', 2);