-- Temporarily disable foreign key checks
SET FOREIGN_KEY_CHECKS = 0;

-- Clear existing data
TRUNCATE TABLE assistant_chat;
TRUNCATE TABLE calendar_events;
DELETE FROM event_categories;
ALTER TABLE event_categories AUTO_INCREMENT = 1;

-- Re-enable foreign key checks
SET FOREIGN_KEY_CHECKS = 1;

-- Populate categories
INSERT INTO event_categories (name, color) VALUES 
('Classes', '#FF4444'),         -- Red for important classes
('Assignments', '#F6BF26'),     -- Yellow for deadlines
('Study Group', '#33B679'),     -- Green for collaborative work
('Project Work', '#039BE5'),    -- Blue for development work
('Lab Sessions', '#7986CB'),    -- Purple for practical work
('Events', '#8E24AA'),         -- Deep Purple for special events
('Personal', '#616161'),        -- Gray for personal items
('Exams', '#D50000'),          -- Deep Red for critical items
('Meetings', '#0B8043'),        -- Dark Green for meetings
('Workshops', '#3F51B5'),       -- Indigo for workshops
('Research', '#795548'),        -- Brown for research activities
('Tutorials', '#FF5722');       -- Deep Orange for tutorials

-- Generate date series for 4 months
CREATE TEMPORARY TABLE date_series (date_value DATE);
INSERT INTO date_series
SELECT DATE_ADD(CURDATE(), INTERVAL nums.num DAY)
FROM (
    SELECT a.N + b.N * 10 + c.N * 100 AS num
    FROM (SELECT 0 AS N UNION SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5 UNION SELECT 6 UNION SELECT 7 UNION SELECT 8 UNION SELECT 9) a,
         (SELECT 0 AS N UNION SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5 UNION SELECT 6 UNION SELECT 7 UNION SELECT 8 UNION SELECT 9) b,
         (SELECT 0 AS N UNION SELECT 1) c
    WHERE a.N + b.N * 10 + c.N * 100 < 120
) nums;

-- Insert regular weekday classes (Monday-Friday)
INSERT INTO calendar_events (user_id, title, description, category_id, start_date, end_date, all_day)
SELECT 
    1,
    CASE DAYOFWEEK(d.date_value)
        WHEN 2 THEN 'Data Structures'
        WHEN 3 THEN 'Algorithms'
        WHEN 4 THEN 'Database Systems'
        WHEN 5 THEN 'Computer Networks'
        WHEN 6 THEN 'Software Engineering'
    END,
    'Regular class session',
    1,
    DATE_ADD(d.date_value, INTERVAL h.hour HOUR),
    DATE_ADD(d.date_value, INTERVAL h.hour + 1 HOUR),
    0
FROM date_series d
CROSS JOIN (SELECT 8 AS hour UNION SELECT 9 UNION SELECT 10 UNION SELECT 11) h
WHERE DAYOFWEEK(d.date_value) BETWEEN 2 AND 6;

-- Insert lab sessions
INSERT INTO calendar_events (user_id, title, description, category_id, start_date, end_date, all_day)
SELECT 
    1,
    CONCAT(
        CASE DAYOFWEEK(d.date_value)
            WHEN 2 THEN 'Programming'
            WHEN 3 THEN 'Database'
            WHEN 4 THEN 'Networks'
            WHEN 5 THEN 'Systems'
            WHEN 6 THEN 'Web Dev'
        END,
        ' Lab'
    ),
    'Practical session',
    5,
    DATE_ADD(d.date_value, INTERVAL h.hour HOUR),
    DATE_ADD(d.date_value, INTERVAL h.hour + 2 HOUR),
    0
FROM date_series d
CROSS JOIN (SELECT 13 AS hour UNION SELECT 15) h
WHERE DAYOFWEEK(d.date_value) BETWEEN 2 AND 6;

-- Insert study groups
INSERT INTO calendar_events (user_id, title, description, category_id, start_date, end_date, all_day)
SELECT 
    1,
    CONCAT(
        ELT(FLOOR(1 + RAND() * 5), 'Algorithm', 'Project', 'Theory', 'Systems', 'Research'),
        ' Study Group'
    ),
    'Group study session',
    3,
    DATE_ADD(d.date_value, INTERVAL h.hour HOUR),
    DATE_ADD(d.date_value, INTERVAL h.hour + 1.5 HOUR),
    0
FROM date_series d
CROSS JOIN (SELECT 17 AS hour UNION SELECT 18 UNION SELECT 19) h
WHERE DAYOFWEEK(d.date_value) IN (2, 4, 6);

-- Insert project work sessions
INSERT INTO calendar_events (user_id, title, description, category_id, start_date, end_date, all_day)
SELECT 
    1,
    CONCAT(
        ELT(FLOOR(1 + RAND() * 4), 'Frontend', 'Backend', 'Database', 'Testing'),
        ' Development'
    ),
    'Project development work',
    4,
    DATE_ADD(d.date_value, INTERVAL h.hour HOUR),
    DATE_ADD(d.date_value, INTERVAL h.hour + 2 HOUR),
    0
FROM date_series d
CROSS JOIN (SELECT 14 AS hour UNION SELECT 16) h
WHERE DAYOFWEEK(d.date_value) IN (2, 3, 5);

-- Insert daily personal activities
INSERT INTO calendar_events (user_id, title, description, category_id, start_date, end_date, all_day)
SELECT 
    1,
    ELT(FLOOR(1 + RAND() * 5), 'Gym Session', 'Lunch Break', 'Coffee Break', 'Reading', 'Meditation'),
    'Personal time',
    7,
    DATE_ADD(d.date_value, INTERVAL h.hour HOUR),
    DATE_ADD(d.date_value, INTERVAL h.hour + 1 HOUR),
    0
FROM date_series d
CROSS JOIN (SELECT 7 AS hour UNION SELECT 12 UNION SELECT 20) h;

-- Insert workshops (Tuesday and Thursday)
INSERT INTO calendar_events (user_id, title, description, category_id, start_date, end_date, all_day)
SELECT 
    1,
    CASE DAYOFWEEK(date_value)
        WHEN 3 THEN 'Tech Workshop'
        WHEN 5 THEN 'Research Seminar'
    END,
    'Weekly session',
    10,
    DATE_ADD(date_value, INTERVAL 15 HOUR),
    DATE_ADD(date_value, INTERVAL 17 HOUR),
    0
FROM date_series
WHERE DAYOFWEEK(date_value) IN (3, 5);

-- Insert research activities (Monday and Wednesday)
INSERT INTO calendar_events (user_id, title, description, category_id, start_date, end_date, all_day)
SELECT 
    1,
    'Research Work',
    'Paper review and research',
    11,
    DATE_ADD(date_value, INTERVAL 13 HOUR),
    DATE_ADD(date_value, INTERVAL 15 HOUR),
    0
FROM date_series
WHERE DAYOFWEEK(date_value) IN (2, 4);

-- Insert random all-day events (100 events)
INSERT INTO calendar_events (user_id, title, description, category_id, start_date, all_day)
SELECT 
    1,
    ELT(FLOOR(1 + RAND() * 5), 'Project Deadline', 'Assignment Due', 'Exam Day', 'Hackathon', 'Conference'),
    'Important all-day event',
    FLOOR(1 + RAND() * 8),
    DATE_ADD(CURDATE(), INTERVAL FLOOR(RAND() * 120) DAY),
    1
FROM (
    SELECT 1 n UNION ALL SELECT 2 UNION ALL SELECT 3 UNION ALL SELECT 4 UNION ALL SELECT 5 UNION ALL
    SELECT 6 UNION ALL SELECT 7 UNION ALL SELECT 8 UNION ALL SELECT 9 UNION ALL SELECT 10
) t1,
(
    SELECT 1 n UNION ALL SELECT 2 UNION ALL SELECT 3 UNION ALL SELECT 4 UNION ALL SELECT 5 UNION ALL
    SELECT 6 UNION ALL SELECT 7 UNION ALL SELECT 8 UNION ALL SELECT 9 UNION ALL SELECT 10
) t2;

-- Clean up
DROP TEMPORARY TABLE IF EXISTS date_series;
