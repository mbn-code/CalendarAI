-- Reset tables
TRUNCATE TABLE events;
TRUNCATE TABLE event_preferences;
TRUNCATE TABLE optimization_history;

-- Insert a busy week schedule
INSERT INTO events (title, description, start_time, end_time, is_recurring, category) VALUES
('Morning Standup', 'Daily team standup', '2025-04-08 09:00:00', '2025-04-08 09:30:00', 1, 'work'),
('Client Meeting', 'Project review with client', '2025-04-08 10:00:00', '2025-04-08 11:30:00', 0, 'work'),
('Team Lunch', 'Team building lunch', '2025-04-08 12:00:00', '2025-04-08 13:00:00', 0, 'break'),
('Code Review', 'Sprint code review', '2025-04-08 14:00:00', '2025-04-08 15:00:00', 0, 'work'),
('Tech Talk', 'Internal knowledge sharing', '2025-04-08 15:30:00', '2025-04-08 16:30:00', 0, 'work'),
('Gym Session', 'Personal training', '2025-04-08 17:30:00', '2025-04-08 18:30:00', 1, 'personal'),

('Morning Standup', 'Daily team standup', '2025-04-09 09:00:00', '2025-04-09 09:30:00', 1, 'work'),
('Sprint Planning', 'Bi-weekly sprint planning', '2025-04-09 10:00:00', '2025-04-09 12:00:00', 0, 'work'),
('Quick Lunch', 'Grab lunch', '2025-04-09 12:00:00', '2025-04-09 12:30:00', 1, 'break'),
('Development', 'Focus time', '2025-04-09 13:00:00', '2025-04-09 16:00:00', 0, 'work'),
('Dentist', 'Regular checkup', '2025-04-09 16:30:00', '2025-04-09 17:30:00', 0, 'personal'),

('Morning Standup', 'Daily team standup', '2025-04-10 09:00:00', '2025-04-10 09:30:00', 1, 'work'),
('Project Demo', 'Client demonstration', '2025-04-10 11:00:00', '2025-04-10 12:00:00', 0, 'work'),
('Team Lunch', 'Team social', '2025-04-10 12:30:00', '2025-04-10 13:30:00', 0, 'break'),
('Design Review', 'UX/UI review session', '2025-04-10 14:00:00', '2025-04-10 15:30:00', 0, 'work'),
('1:1 Meeting', 'Manager catch-up', '2025-04-10 16:00:00', '2025-04-10 16:30:00', 1, 'work'),
('Evening Run', 'Training for marathon', '2025-04-10 17:30:00', '2025-04-10 18:30:00', 1, 'personal');

-- Insert preferences
INSERT INTO event_preferences (category, preferred_time_start, preferred_time_end, priority) VALUES
('work', '09:00:00', '17:00:00', 1),
('break', '12:00:00', '14:00:00', 2),
('personal', '17:00:00', '20:00:00', 3);