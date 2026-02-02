-- Sample Data for Testing
-- Run this after schema.sql

USE streaming_platform;

-- Insert sample users
-- Password for all: 'password123'
-- SHA1(username . password) format
INSERT INTO users (username, email, password, role) VALUES
('admin', 'admin@streaming.com', SHA1('adminpassword123'), 'admin'),
('instructor1', 'instructor1@streaming.com', SHA1('instructor1password123'), 'instructor'),
('instructor2', 'instructor2@streaming.com', SHA1('instructor2password123'), 'instructor'),
('student1', 'student1@streaming.com', SHA1('student1password123'), 'student'),
('student2', 'student2@streaming.com', SHA1('student2password123'), 'student');

-- Insert sample videos
INSERT INTO videos (title, description, category_id, video_url, uploaded_by) VALUES
('Introduction to PHP', 'Learn the basics of PHP programming language', 1, 'https://example.com/videos/php-intro.mp4', 2),
('Advanced MySQL Queries', 'Master complex MySQL queries and optimization', 1, 'https://example.com/videos/mysql-advanced.mp4', 2),
('Linear Algebra Basics', 'Introduction to linear algebra concepts', 2, 'https://example.com/videos/linear-algebra.mp4', 3),
('Physics Fundamentals', 'Basic principles of physics', 3, 'https://example.com/videos/physics-fundamentals.mp4', 3),
('Business Strategy 101', 'Learn fundamental business strategy concepts', 4, 'https://example.com/videos/business-strategy.mp4', 2);

-- Insert sample logs
INSERT INTO logs (action, user_id, details, ip_address) VALUES
('user_login', 1, 'Admin user logged in', '127.0.0.1'),
('video_created', 2, 'Created video: Introduction to PHP', '127.0.0.1'),
('video_viewed', 4, 'Viewed video: Introduction to PHP', '127.0.0.1');

