CREATE TABLE IF NOT EXISTS user_preferences (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    focus_start_time TIME NOT NULL DEFAULT '09:00',
    focus_end_time TIME NOT NULL DEFAULT '17:00',
    chill_start_time TIME NOT NULL DEFAULT '17:00',
    chill_end_time TIME NOT NULL DEFAULT '22:00',
    break_duration INT NOT NULL DEFAULT 15,
    session_length INT NOT NULL DEFAULT 120,
    priority_mode ENUM('deadlines', 'balanced', 'flexible') NOT NULL DEFAULT 'balanced',
    has_completed_setup BOOLEAN NOT NULL DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_user_id (user_id)
);

CREATE TABLE IF NOT EXISTS system_prompts (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    prompt_text TEXT NOT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS assistant_chat (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    message TEXT NOT NULL,
    is_user BOOLEAN NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

ALTER TABLE calendar_events 
ADD COLUMN IF NOT EXISTS is_ai_optimized BOOLEAN DEFAULT FALSE,
ADD COLUMN IF NOT EXISTS is_human_ai_altered BOOLEAN DEFAULT FALSE;