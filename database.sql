-- Drop tables in reverse order of dependencies
DROP TABLE IF EXISTS assistant_chat;
DROP TABLE IF EXISTS system_prompts;
DROP TABLE IF EXISTS user_preferences;
DROP TABLE IF EXISTS calendar_events;
DROP TABLE IF EXISTS event_categories;
DROP TABLE IF EXISTS password_resets;
DROP TABLE IF EXISTS users;

-- Create tables in order of dependencies
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(100),
    role ENUM('admin', 'user') DEFAULT 'user',
    is_active BOOLEAN DEFAULT TRUE,
    last_login DATETIME,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE password_resets (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    token VARCHAR(255) NOT NULL,
    expires_at DATETIME NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE event_categories (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(50) NOT NULL,
    color VARCHAR(7) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE calendar_events (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    title VARCHAR(100) NOT NULL,
    description TEXT,
    category_id INT,
    start_date DATETIME NOT NULL,
    end_date DATETIME,
    all_day BOOLEAN DEFAULT FALSE,
    is_ai_optimized BOOLEAN DEFAULT FALSE,
    ai_description TEXT NULL,
    is_human_ai_altered BOOLEAN DEFAULT FALSE,
    preset_source VARCHAR(20) DEFAULT 'default',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES event_categories(id),
    FOREIGN KEY (user_id) REFERENCES users(id)
);

CREATE TABLE user_preferences (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    focus_start_time TIME DEFAULT '09:00',
    focus_end_time TIME DEFAULT '17:00',
    chill_start_time TIME DEFAULT '17:00',
    chill_end_time TIME DEFAULT '22:00',
    break_duration INT DEFAULT 15,
    session_length INT DEFAULT 120,
    priority_mode VARCHAR(20) NOT NULL DEFAULT 'balanced',
    has_completed_setup BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_user (user_id)
);

CREATE TABLE system_prompts (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    prompt_text TEXT NOT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE assistant_chat (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    message TEXT NOT NULL,
    is_user BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Insert default admin user if not exists
INSERT IGNORE INTO users (id, username, email, password, full_name, role) VALUES 
(1, 'admin', 'admin@calendar.ai', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'System Admin', 'admin');

-- Insert default system prompt if not exists
INSERT IGNORE INTO system_prompts (user_id, prompt_text, is_active) VALUES 
(1, 'You are a calendar management assistant. Help the user manage their schedule effectively by suggesting optimal times for tasks, managing breaks, and ensuring a healthy work-life balance.', 1);
