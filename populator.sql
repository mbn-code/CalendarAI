-- Temporarily disable foreign key checks
SET FOREIGN_KEY_CHECKS = 0;

-- Clear existing data
TRUNCATE TABLE assistant_chat;
TRUNCATE TABLE calendar_events;
TRUNCATE TABLE user_preferences;
TRUNCATE TABLE users;
DELETE FROM event_categories;
ALTER TABLE event_categories AUTO_INCREMENT = 1;
ALTER TABLE users AUTO_INCREMENT = 1;

-- Re-enable foreign key checks
SET FOREIGN_KEY_CHECKS = 1;

-- Create sample users (passwords are all 'password123')
INSERT INTO users (username, email, password, full_name, role, is_active, last_login) VALUES 
('admin', 'admin@calendar.ai', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'System Admin', 'admin', TRUE, NOW()),
('john.doe', 'john@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'John Doe', 'user', TRUE, NOW()),
('jane.smith', 'jane@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Jane Smith', 'user', TRUE, NOW()),
('bob.wilson', 'bob@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Bob Wilson', 'user', TRUE, NOW()),
('alice.miller', 'alice@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Alice Miller', 'user', TRUE, NOW()),
('inactive.user', 'inactive@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Inactive User', 'user', FALSE, NULL),
('mbn', 'mbn@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'MBN User', 'user', TRUE, NOW());

-- Create user preferences for active users
INSERT INTO user_preferences (user_id, focus_start_time, focus_end_time, chill_start_time, chill_end_time, break_duration, session_length, priority_mode, has_completed_setup) VALUES
(1, '08:00', '16:00', '16:00', '22:00', 15, 120, 'balanced', TRUE),      -- admin
(2, '09:00', '17:00', '17:00', '23:00', 20, 90, 'productivity', TRUE),   -- john
(3, '07:00', '15:00', '15:00', '21:00', 10, 60, 'wellbeing', TRUE),     -- jane
(4, '10:00', '18:00', '18:00', '00:00', 15, 120, 'balanced', FALSE),    -- bob
(5, '08:30', '16:30', '16:30', '22:30', 30, 180, 'productivity', TRUE), -- alice
(7, '09:30', '17:30', '17:30', '23:30', 25, 150, 'wellbeing', TRUE);   -- mbn

-- Populate categories
INSERT INTO event_categories (name, color) VALUES 
('Classes', '#FF4444'),         -- Red for important classes - ID: 1
('Assignments', '#F6BF26'),     -- Yellow for deadlines - ID: 2 
('Study Group', '#33B679'),     -- Green for collaborative work - ID: 3
('Project Work', '#039BE5'),    -- Blue for development work - ID: 4
('Lab Sessions', '#7986CB'),    -- Purple for practical work - ID: 5
('Events', '#8E24AA'),          -- Deep Purple for special events - ID: 6
('Personal', '#616161'),        -- Gray for personal items - ID: 7
('Exams', '#D50000'),           -- Deep Red for critical items - ID: 8
('Meetings', '#0B8043'),        -- Dark Green for meetings - ID: 9
('Workshops', '#3F51B5'),       -- Indigo for workshops - ID: 10
('Research', '#795548'),        -- Brown for research activities - ID: 11
('Tutorials', '#FF5722'),       -- Deep Orange for tutorials - ID: 12
('School', '#009688'),          -- Teal for school events (immovable) - ID: 13
('Study', '#4CAF50');           -- Light Green for study sessions (can be optimized) - ID: 14

-- Clear any existing school schedule
DELETE FROM calendar_events WHERE category_id IN (1, 5, 8, 10, 11, 12, 13, 14);

-- Insert actual school schedule for April-June 2025
-- These are immovable school events

-- April 2025
INSERT INTO calendar_events (user_id, title, description, category_id, start_date, end_date, all_day, is_immovable) VALUES
-- April 1
(7, 'Fysik', 'Regular physics class', 13, '2025-04-01 09:00:00', '2025-04-01 11:00:00', 0, TRUE),
(7, 'Fysik forsøg 1', 'Physics experiment 1', 13, '2025-04-01 12:00:00', '2025-04-01 12:40:00', 0, TRUE),

-- April 4
(7, 'Temafest', 'Theme party', 6, '2025-04-04 00:00:00', NULL, 1, FALSE),

-- April 7
(7, '3D-vejledning', '3D guidance in fifth lesson', 13, '2025-04-07 14:00:00', '2025-04-07 15:30:00', 0, TRUE),

-- April 8
(7, 'Fysik', 'Regular physics class', 13, '2025-04-08 09:00:00', '2025-04-08 11:00:00', 0, TRUE),
(7, 'Fysik forsøg 1', 'Physics experiment 1', 13, '2025-04-08 12:00:00', '2025-04-08 12:40:00', 0, TRUE),

-- April 11
(7, 'Arnolds + Friendsdagscafé', 'Social event', 6, '2025-04-11 00:00:00', NULL, 1, FALSE),

-- April 22
(7, '3.årg Teknikfag værkstedsuge', 'Technical subject workshop week', 13, '2025-04-22 09:00:00', '2025-04-22 16:00:00', 0, TRUE),
(7, '3D-vejledning', '3D guidance in fifth lesson', 13, '2025-04-22 14:00:00', '2025-04-22 15:30:00', 0, TRUE),
(7, 'Fysik', 'Regular physics class', 13, '2025-04-22 09:00:00', '2025-04-22 11:00:00', 0, TRUE),
(7, 'Fysik forsøg 1', 'Physics experiment 1', 13, '2025-04-22 12:00:00', '2025-04-22 12:40:00', 0, TRUE),

-- April 23
(7, '3.årg Teknikfag værkstedsuge', 'Technical subject workshop week', 13, '2025-04-23 09:00:00', '2025-04-23 16:00:00', 0, TRUE),

-- April 24
(7, '3.årg Teknikfag værkstedsuge', 'Technical subject workshop week', 13, '2025-04-24 09:00:00', '2025-04-24 16:00:00', 0, TRUE),

-- April 25
(7, '2. årg. Aflevering af Matematik B projektrapport', 'Math B project report deadline', 2, '2025-04-25 00:00:00', NULL, 1, TRUE),
(7, '2.årg Teknologi B skrivedag', 'Technology B writing day', 13, '2025-04-25 09:00:00', '2025-04-25 16:00:00', 0, TRUE),
(7, '3.årg Teknikfag værkstedsuge', 'Technical subject workshop week', 13, '2025-04-25 09:00:00', '2025-04-25 16:00:00', 0, TRUE),
(7, 'Lounge', 'Social event', 6, '2025-04-25 17:00:00', '2025-04-25 21:00:00', 0, FALSE),

-- April 28
(7, '2.årg Teknologi B skrivedag', 'Technology B writing day', 13, '2025-04-28 09:00:00', '2025-04-28 16:00:00', 0, TRUE),
(7, '3.årg Teknikfag', 'Technical subject', 13, '2025-04-28 09:00:00', '2025-04-28 11:00:00', 0, TRUE),

-- April 29
(7, '1.årg SO Naturvidenskabelig uge', 'Science week', 13, '2025-04-29 09:00:00', '2025-04-29 16:00:00', 0, TRUE),
(7, '2.årg Teknologi B skrivedag', 'Technology B writing day', 13, '2025-04-29 09:00:00', '2025-04-29 16:00:00', 0, TRUE),
(7, '3.årg Skrivedag teknikfag', 'Technical subject writing day', 13, '2025-04-29 09:00:00', '2025-04-29 16:00:00', 0, TRUE),
(7, 'Fysik', 'Regular physics class', 13, '2025-04-29 09:00:00', '2025-04-29 11:00:00', 0, TRUE),
(7, 'Fysik forsøg 1', 'Physics experiment 1', 13, '2025-04-29 12:00:00', '2025-04-29 12:40:00', 0, TRUE),

-- April 30
(7, '1.årg SO Naturvidenskabelig uge', 'Science week', 13, '2025-04-30 09:00:00', '2025-04-30 16:00:00', 0, TRUE),
(7, '2.årg Teknologi B skrivedag', 'Technology B writing day', 13, '2025-04-30 09:00:00', '2025-04-30 16:00:00', 0, TRUE),
(7, '3.årg Skrivedag teknikfag', 'Technical subject writing day', 13, '2025-04-30 09:00:00', '2025-04-30 16:00:00', 0, TRUE),

-- May 1
(7, '1.årg SO Naturvidenskabelig uge', 'Science week', 13, '2025-05-01 09:00:00', '2025-05-01 16:00:00', 0, TRUE),
(7, '2. årg. Aflevering af Teknologi B projektrapport', 'Technology B project report deadline', 2, '2025-05-01 00:00:00', NULL, 1, TRUE),
(7, '2.årg Teknologi B skrivedag', 'Technology B writing day', 13, '2025-05-01 09:00:00', '2025-05-01 16:00:00', 0, TRUE),
(7, '3.årg Skrivedag teknikfag', 'Technical subject writing day', 13, '2025-05-01 09:00:00', '2025-05-01 16:00:00', 0, TRUE),

-- May 2
(7, '1.årg SO Naturvidenskabelig uge', 'Science week', 13, '2025-05-02 09:00:00', '2025-05-02 16:00:00', 0, TRUE),
(7, '3.årg Aflevering af teknikfags eksamensprojekt', 'Technical subject exam project deadline', 2, '2025-05-02 00:00:00', NULL, 1, TRUE),
(7, '3.årg Skrivedag teknikfag', 'Technical subject writing day', 13, '2025-05-02 09:00:00', '2025-05-02 16:00:00', 0, TRUE),
(7, 'LAN', 'LAN party Friday to Sunday', 6, '2025-05-02 17:00:00', '2025-05-04 17:00:00', 0, FALSE),

-- May 5
(7, '1.årg SO Naturvidenskabelig uge', 'Science week', 13, '2025-05-05 09:00:00', '2025-05-05 16:00:00', 0, TRUE),

-- May 6
(7, 'Fysik', 'Regular physics class', 13, '2025-05-06 09:00:00', '2025-05-06 11:00:00', 0, TRUE),
(7, 'Fysik forsøg 1', 'Physics experiment 1', 13, '2025-05-06 12:00:00', '2025-05-06 12:40:00', 0, TRUE),

-- May 7
(7, 'Sundhedstjek', 'Health check', 7, '2025-05-07 10:00:00', '2025-05-07 11:00:00', 0, TRUE),

-- May 9
(7, 'GALLA', 'Formal gala', 6, '2025-05-09 18:00:00', '2025-05-09 23:59:00', 0, FALSE),

-- May 12
(7, 'Alle årg. Delvis Offentliggørelse af prøveplan', 'Partial release of exam schedule', 8, '2025-05-12 12:00:00', '2025-05-12 12:30:00', 0, TRUE),
(7, 'Alle: Oprydningsdag', 'Cleanup day', 7, '2025-05-12 09:00:00', '2025-05-12 16:00:00', 0, TRUE),
(7, 'Sundhedstjek', 'Health check', 7, '2025-05-12 10:00:00', '2025-05-12 11:00:00', 0, TRUE),

-- May 13
(7, 'Fysik', 'Regular physics class', 13, '2025-05-13 09:00:00', '2025-05-13 11:00:00', 0, TRUE),
(7, 'Fysik forsøg 1', 'Physics experiment 1', 13, '2025-05-13 12:00:00', '2025-05-13 12:40:00', 0, TRUE),

-- May 16
(7, 'Alle årg. Offentliggørelse af prøveplanen kl.', 'Publication of exam schedule', 8, '2025-05-16 12:00:00', '2025-05-16 12:30:00', 0, TRUE),
(7, 'Sidste fredagscafé', 'Last Friday cafe', 6, '2025-05-16 15:00:00', '2025-05-16 19:00:00', 0, FALSE),

-- May 19
(7, '3. årg. Skr eksamen Dansk A', 'Written exam Danish A', 13, '2025-05-19 09:00:00', '2025-05-19 14:00:00', 0, TRUE),
(7, 'Alle årg. Første mulige prøvedag', 'First possible exam day', 8, '2025-05-19 08:00:00', '2025-05-19 08:30:00', 0, TRUE),

-- May 20
(7, '2.årg Skr. årsprøve Dansk A', 'Written annual test Danish A', 13, '2025-05-20 09:00:00', '2025-05-20 14:00:00', 0, TRUE),
(7, '3. årg. Matematik A Udlevering af forberedelsesmateriale', 'Math A preparation material release', 13, '2025-05-20 09:00:00', '2025-05-20 11:00:00', 0, TRUE),

-- May 21
(7, '2.årg. Skr. årsprøve Fysik A, kemi A, Geo A', 'Written annual test Physics A, Chemistry A, Geo A', 13, '2025-05-21 09:00:00', '2025-05-21 13:00:00', 0, TRUE),

-- May 22
(7, '3.årg. Skr eksamen Matematik A', 'Written exam Math A', 13, '2025-05-22 09:00:00', '2025-05-22 14:00:00', 0, TRUE),

-- May 23
(7, '2. årg. Skr. eksamen Engelsk B', 'Written exam English B', 13, '2025-05-23 09:00:00', '2025-05-23 13:00:00', 0, TRUE),
(7, '3. årg. Skr. eksamen Engelsk A', 'Written exam English A', 13, '2025-05-23 09:00:00', '2025-05-23 14:00:00', 0, TRUE),

-- May 27
(7, '3. årg. Skr eksamen Geovidenskab A, Biologi A', 'Written exam Geoscience A, Biology A', 13, '2025-05-27 09:00:00', '2025-05-27 14:00:00', 0, TRUE),

-- May 28
(7, '3. årg. Skr eksamen Fysik A', 'Written exam Physics A', 13, '2025-05-28 09:00:00', '2025-05-28 14:00:00', 0, TRUE),

-- June 2
(7, '3. årg. Skr eksamen Kemi A', 'Written exam Chemistry A', 13, '2025-06-02 09:00:00', '2025-06-02 13:00:00', 0, TRUE),

-- June 13
(7, 'Censormøde', 'Examiner meeting', 13, '2025-06-13 09:00:00', '2025-06-13 15:00:00', 0, TRUE),

-- June 16-17
(7, 'Censormøde', 'Examiner meeting', 13, '2025-06-16 09:00:00', '2025-06-16 15:00:00', 0, TRUE),
(7, 'Censormøde', 'Examiner meeting', 13, '2025-06-17 09:00:00', '2025-06-17 15:00:00', 0, TRUE),

-- June 25
(7, 'Alle årg. Sidste mulige prøvedag', 'Last possible exam day', 8, '2025-06-25 09:00:00', '2025-06-25 15:00:00', 0, TRUE),

-- June 27
(7, 'Translokation', 'Graduation ceremony', 13, '2025-06-27 10:00:00', '2025-06-27 14:00:00', 0, TRUE);

-- Insert study sessions that CAN be optimized
INSERT INTO calendar_events (user_id, title, description, category_id, start_date, end_date, all_day, is_immovable) VALUES
-- Study sessions for April
(7, 'Study: Physics', 'Review physics concepts', 14, '2025-04-02 16:00:00', '2025-04-02 18:00:00', 0, FALSE),
(7, 'Study: Mathematics', 'Work on math problems', 14, '2025-04-03 17:00:00', '2025-04-03 19:00:00', 0, FALSE),
(7, 'Study: Technical subjects', 'Prepare for technical classes', 14, '2025-04-07 19:00:00', '2025-04-07 21:00:00', 0, FALSE),
(7, 'Study: Physics exam prep', 'Prepare for upcoming physics tests', 14, '2025-04-10 16:00:00', '2025-04-10 18:00:00', 0, FALSE),
(7, 'Study: Project work', 'Work on ongoing projects', 14, '2025-04-14 17:00:00', '2025-04-14 19:30:00', 0, FALSE),
(7, 'Study: Report writing', 'Work on report drafts', 14, '2025-04-17 16:00:00', '2025-04-17 18:00:00', 0, FALSE),
(7, 'Study: Math B project', 'Preparation for Math B project', 14, '2025-04-20 14:00:00', '2025-04-20 17:00:00', 0, FALSE),

-- Study sessions for May
(7, 'Study: Exam revision', 'General exam preparation', 14, '2025-05-04 15:00:00', '2025-05-04 18:00:00', 0, FALSE),
(7, 'Study: Physics formulas', 'Memorize key physics formulas', 14, '2025-05-08 16:00:00', '2025-05-08 18:00:00', 0, FALSE),
(7, 'Study: Danish essay practice', 'Practice for Danish A exam', 14, '2025-05-10 14:00:00', '2025-05-10 16:00:00', 0, FALSE),
(7, 'Study: Math problems', 'Advanced math problem solving', 14, '2025-05-14 17:00:00', '2025-05-14 19:00:00', 0, FALSE),
(7, 'Study: English writing', 'Practice English writing skills', 14, '2025-05-17 13:00:00', '2025-05-17 15:00:00', 0, FALSE),
(7, 'Study: Chemistry review', 'Review chemistry concepts', 14, '2025-05-24 14:00:00', '2025-05-24 16:30:00', 0, FALSE),
(7, 'Study: Biology review', 'Review biology topics for exam', 14, '2025-05-25 15:00:00', '2025-05-25 17:30:00', 0, FALSE);

-- Generate date series for 4 months - keep this for non-school days to have some regular activities
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

-- Insert daily personal activities (but not on exam days)
INSERT INTO calendar_events (user_id, title, description, category_id, start_date, end_date, all_day, is_immovable)
SELECT 
    u.id,
    ELT(FLOOR(1 + RAND() * 5), 'Gym Session', 'Lunch Break', 'Coffee Break', 'Reading', 'Meditation'),
    'Personal time',
    7,
    DATE_ADD(d.date_value, INTERVAL h.hour HOUR),
    DATE_ADD(d.date_value, INTERVAL h.hour + 1 HOUR),
    0,
    FALSE
FROM date_series d
CROSS JOIN (SELECT 7 AS hour UNION SELECT 12 UNION SELECT 20) h
CROSS JOIN users u
WHERE u.is_active = TRUE
AND RAND() < 0.5 -- 50% chance for each user to have personal activities
AND DATE_FORMAT(d.date_value, '%Y-%m-%d') NOT IN (
    SELECT DATE_FORMAT(start_date, '%Y-%m-%d') 
    FROM calendar_events 
    WHERE category_id = 13
);

-- Clean up
DROP TEMPORARY TABLE IF EXISTS date_series;
