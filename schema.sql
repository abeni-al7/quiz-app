-- Initial database schema for Quiz App

CREATE DATABASE IF NOT EXISTS quiz_app;
USE quiz_app;

-- Users table: admins and students
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'student') DEFAULT 'student',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Subjects
CREATE TABLE IF NOT EXISTS subjects (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(100) NOT NULL UNIQUE,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Quizzes
CREATE TABLE IF NOT EXISTS quizzes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    subject_id INT NOT NULL,
    created_by INT NULL,
    title VARCHAR(150) NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
);

-- Questions (supports multiple choice, true/false, fill-in-blank)
CREATE TABLE IF NOT EXISTS questions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    quiz_id INT NOT NULL,
    type ENUM('multiple_choice', 'true_false', 'fill_blank') NOT NULL,
    prompt TEXT NOT NULL,
    correct_answer TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (quiz_id) REFERENCES quizzes(id) ON DELETE CASCADE
);

-- Choices for multiple choice and true/false
CREATE TABLE IF NOT EXISTS choices (
    id INT AUTO_INCREMENT PRIMARY KEY,
    question_id INT NOT NULL,
    content VARCHAR(255) NOT NULL,
    is_correct BOOLEAN DEFAULT FALSE,
    FOREIGN KEY (question_id) REFERENCES questions(id) ON DELETE CASCADE
);

-- Student quiz attempts
CREATE TABLE IF NOT EXISTS student_quizzes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    quiz_id INT NOT NULL,
    status ENUM('in_progress', 'completed', 'graded') DEFAULT 'in_progress',
    score INT DEFAULT 0,
    started_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    completed_at DATETIME,
    FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (quiz_id) REFERENCES quizzes(id) ON DELETE CASCADE
);

-- Student answers
CREATE TABLE IF NOT EXISTS student_answers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    attempt_id INT NOT NULL,
    question_id INT NOT NULL,
    answer_text TEXT,
    chosen_choice_id INT,
    is_correct BOOLEAN,
    FOREIGN KEY (attempt_id) REFERENCES student_quizzes(id) ON DELETE CASCADE,
    FOREIGN KEY (question_id) REFERENCES questions(id) ON DELETE CASCADE,
    FOREIGN KEY (chosen_choice_id) REFERENCES choices(id) ON DELETE SET NULL
);

-- Leaderboard view (top 15 students)
DROP VIEW IF EXISTS leaderboard;
CREATE VIEW leaderboard AS
SELECT u.id AS student_id, u.name, SUM(sq.score) AS total_score
FROM users u
JOIN student_quizzes sq ON u.id = sq.student_id
WHERE u.role = 'student' AND sq.status = 'graded'
GROUP BY u.id, u.name
ORDER BY total_score DESC
LIMIT 15;
