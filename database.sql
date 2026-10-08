CREATE DATABASE IF NOT EXISTS sipway_online CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE sipway_online;

CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    phone VARCHAR(20),
    role ENUM('admin','student','teacher') DEFAULT 'student',
    status ENUM('active','inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    name_si VARCHAR(100),
    slug VARCHAR(100) UNIQUE NOT NULL,
    icon VARCHAR(50) DEFAULT 'fas fa-book',
    color VARCHAR(20) DEFAULT '#E30613',
    sort_order INT DEFAULT 0,
    image VARCHAR(255) DEFAULT NULL
);

CREATE TABLE teachers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(100),
    phone VARCHAR(20),
    subject VARCHAR(100),
    bio TEXT,
    photo VARCHAR(255) DEFAULT 'default-teacher.jpg',
    status ENUM('active','inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE courses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    category_id INT,
    teacher_id INT,
    title VARCHAR(200) NOT NULL,
    title_si VARCHAR(200),
    slug VARCHAR(200) UNIQUE NOT NULL,
    short_desc VARCHAR(500),
    description TEXT,
    thumbnail VARCHAR(255) DEFAULT 'default-course.jpg',
    duration VARCHAR(50),
    level VARCHAR(50) DEFAULT 'A/L',
    price DECIMAL(10,2) DEFAULT 0,
    is_featured TINYINT(1) DEFAULT 0,
    is_published TINYINT(1) DEFAULT 1,
    views INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL,
    FOREIGN KEY (teacher_id) REFERENCES teachers(id) ON DELETE SET NULL
);

CREATE TABLE lessons (
    id INT AUTO_INCREMENT PRIMARY KEY,
    course_id INT NOT NULL,
    title VARCHAR(200) NOT NULL,
    slug VARCHAR(200) NOT NULL,
    content LONGTEXT,
    video_url VARCHAR(500),
    duration VARCHAR(20),
    sort_order INT DEFAULT 0,
    is_free TINYINT(1) DEFAULT 0,
    is_published TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE
);

CREATE TABLE enrollments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    course_id INT NOT NULL,
    progress INT DEFAULT 0,
    status ENUM('active','completed') DEFAULT 'active',
    enrolled_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE,
    UNIQUE KEY (user_id, course_id)
);

CREATE TABLE contact_messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL,
    phone VARCHAR(20),
    subject VARCHAR(200),
    message TEXT NOT NULL,
    is_read TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE settings (
    setting_key VARCHAR(100) PRIMARY KEY,
    setting_value TEXT
);

-- Admin (password: password)
INSERT INTO users (full_name, email, password, role) VALUES
('Sipway Admin', 'admin@sipwaycampus.lk', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin');

-- Categories
INSERT INTO categories (name, name_si, slug, icon, color, sort_order) VALUES
('A/L Arts', 'උසස් පෙළ කලා', 'al-arts', 'fas fa-palette', '#E30613', 1),
('A/L Science', 'උසස් පෙළ විද්‍යා', 'al-science', 'fas fa-flask', '#0ea5e9', 2),
('A/L Technology', 'උසස් පෙළ තාක්ෂණ', 'al-technology', 'fas fa-cogs', '#8b5cf6', 3),
('A/L Commerce', 'උසස් පෙළ වාණිජ', 'al-commerce', 'fas fa-chart-line', '#10b981', 4),
('Languages', 'භාෂා', 'languages', 'fas fa-language', '#F5A623', 5),
('Grade 6-7', '6-7 ශ්‍රේණි', 'grade-6-7', 'fas fa-child', '#06b6d4', 6),
('Astrology', 'ජ්‍යොතිෂය', 'astrology', 'fas fa-star', '#a855f7', 7);

-- Teachers
INSERT INTO teachers (full_name, subject, bio) VALUES
('Mr. Kamal Perera', 'Sinhala', 'Experienced A/L Sinhala teacher with 15+ years'),
('Ms. Nimali Silva', 'Biology', 'Specialist in A/L Biology'),
('Mr. Ravi Fernando', 'ICT', 'ICT & Technology expert'),
('Ms. Sanduni Jayasuriya', 'Accounting', 'Commerce stream specialist'),
('Mr. Dinesh Pathirana', 'Korean Language', 'Korean language instructor'),
('Mr. Sunil Wickramasinghe', 'Astrology', 'Basic Astrology course instructor');

-- Courses
INSERT INTO courses (category_id, teacher_id, title, title_si, slug, short_desc, description, duration, level, price, is_featured) VALUES
(1, 1, 'A/L Sinhala', 'උසස් පෙළ සිංහල', 'al-sinhala', 'Complete A/L Sinhala language & literature course', '<p>Full coverage of A/L Sinhala syllabus including literature, grammar and essay writing.</p>', '1 Year', 'A/L', 15000, 1),
(2, 2, 'A/L Biology', 'උසස් පෙළ ජීව විද්‍යාව', 'al-biology', 'Complete A/L Biology for Science stream students', '<p>Comprehensive Biology course covering Unit 1-10 of the A/L syllabus.</p>', '1 Year', 'A/L', 18000, 1),
(1, 1, 'A/L Media Studies', 'උසස් පෙළ මාධ්‍ය අධ්‍යයනය', 'al-media', 'A/L Media Studies complete course', '<p>Learn media theory, practical skills and exam techniques.</p>', '1 Year', 'A/L', 14000, 0),
(3, 3, 'A/L SFT - Science for Technology', 'SFT - තාක්ෂණය සඳහා විද්‍යාව', 'al-sft', 'Science for Technology A/L course', '<p>Complete SFT syllabus for Technology stream.</p>', '1 Year', 'A/L', 16000, 1),
(3, 3, 'A/L Engineering Technology (ET)', 'ඉංජිනේරු තාක්ෂණය', 'al-et', 'Engineering Technology for A/L', '<p>Full ET course with practical components.</p>', '1 Year', 'A/L', 17000, 1),
(3, 3, 'A/L Bio-Systems Technology (BST)', 'ජෛව පද්ධති තාක්ෂණය', 'al-bst', 'Bio-Systems Technology A/L course', '<p>Complete BST syllabus coverage.</p>', '1 Year', 'A/L', 16000, 0),
(3, 3, 'A/L Agriculture', 'කෘෂිකර්මය', 'al-agriculture', 'A/L Agriculture complete course', '<p>Agriculture science for A/L students.</p>', '1 Year', 'A/L', 15000, 0),
(3, 3, 'A/L ICT', 'තොරතුරු හා සන්නිවේදන තාක්ෂණය', 'al-ict', 'Information & Communication Technology A/L', '<p>Complete ICT syllabus with programming and theory.</p>', '1 Year', 'A/L', 18000, 1),
(4, 4, 'A/L Accounting', 'ගිණුම්කරණය', 'al-accounting', 'A/L Accounting complete course', '<p>Financial accounting, cost accounting and exam preparation.</p>', '1 Year', 'A/L', 16000, 1),
(4, 4, 'A/L Economics', 'ආර්ථික විද්‍යාව', 'al-economics', 'A/L Economics full syllabus', '<p>Micro, macro economics and Sri Lankan economy.</p>', '1 Year', 'A/L', 15000, 1),
(4, 4, 'A/L Business Studies', 'ව්‍යාපාර අධ්‍යයනය', 'al-business', 'A/L Business Studies complete course', '<p>Business management, marketing and entrepreneurship.</p>', '1 Year', 'A/L', 15000, 0),
(5, 5, 'Korean Language Course', 'කොරියානු භාෂා පාඨමාලාව', 'korean-language', 'Learn Korean from beginner to intermediate', '<p>Speaking, reading, writing Korean. Perfect for employment in Korea.</p>', '6 Months', 'Beginner', 20000, 1),
(6, 1, 'Grade 6 All Subjects', '6 ශ්‍රේණිය සියලු විෂයන්', 'grade-6', 'Complete Grade 6 classes for all subjects', '<p>Mathematics, Science, English, Sinhala and more for Grade 6.</p>', '1 Year', 'Grade 6', 10000, 0),
(6, 1, 'Grade 7 All Subjects', '7 ශ්‍රේණිය සියලු විෂයන්', 'grade-7', 'Complete Grade 7 classes for all subjects', '<p>Full Grade 7 curriculum support online.</p>', '1 Year', 'Grade 7', 10000, 0),
(7, 6, 'Basic Astrology', 'මූලික ජ්‍යොතිෂය', 'basic-astrology', 'Learn the fundamentals of Astrology', '<p>මූලික ජ්‍යොතිෂ විද්‍යාව - ග්‍රහ, රාශි, නැකත් සහ මූලික ජන්ම පත්‍ර කියවීම.</p>', '3 Months', 'Beginner', 12000, 1);

-- Sample lessons
INSERT INTO lessons (course_id, title, slug, content, duration, sort_order, is_free) VALUES
(1, 'Introduction to A/L Sinhala', 'intro-sinhala',
'<div class="lesson-section animate-fade-in"><h2>උසස් පෙළ සිංහල වෙත සාදරයෙන් පිළිගනිමු!</h2><p class="lead">මෙම පාඨමාලාව තුළ ඔබට සිංහල භාෂාව හා සාහිත්‍යය පිළිබඳ සම්පූර්ණ දැනුමක් ලැබෙනු ඇත.</p></div>
<div class="lesson-card animate-slide-up"><h3><i class="fas fa-book"></i> පාඨමාලා අන්තර්ගතය</h3><ul><li>සාහිත්‍ය කෘති විශ්ලේෂණය</li><li>ව්‍යාකරණ</li><li>රචනා ලිවීම</li><li>ප්‍රශ්න පත්‍ර භාවිතා</li></ul></div>
<div class="highlight-box animate-pulse"><strong>ඉගෙනුම් ඉඟිය:</strong> දිනපතා කියවීම් පුහුණුවීම් කරන්න!</div>', '20 min', 1, 1),
(12, 'Introduction to Korean', 'intro-korean',
'<div class="lesson-section animate-fade-in"><h2>안녕하세요! Welcome to Korean Language</h2><p>Start your journey to learn Korean - Hangul, basic greetings and daily conversation.</p></div>
<div class="lesson-card animate-slide-up"><h3><i class="fas fa-language"></i> What you will learn</h3><ul><li>Hangul alphabet</li><li>Basic greetings</li><li>Numbers & time</li><li>Daily conversation</li></ul></div>', '25 min', 1, 1),
(15, 'Introduction to Astrology', 'intro-astrology',
'<div class="lesson-section animate-fade-in"><h2>මූලික ජ්‍යොතිෂය වෙත සාදරයෙන් පිළිගනිමු</h2><p>ග්‍රහ මණ්ඩලය, රාශි චක්‍රය සහ ජන්ම පත්‍රයේ මූලික කරුණු ඉගෙන ගනිමු.</p></div>
<div class="lesson-card animate-slide-up"><h3><i class="fas fa-star"></i> මෙම පාඩමෙන්</h3><ul><li>නව ග්‍රහයන් 9</li><li>රාශි 12</li><li>නැකත් 27</li><li>ජන්ම පත්‍රය කියවීම</li></ul></div>
<div class="highlight-box"><strong>විශේෂ:</strong> ප්‍රායෝගික ජන්ම පත්‍ර කියවීම් ඇතුළත්!</div>', '30 min', 1, 1);

INSERT INTO settings (setting_key, setting_value) VALUES
('site_title', 'Sipway Online School'),
('site_tagline', 'Learn Differently. Succeed Differently.'),
('contact_email', 'sipwaycampus@gmail.com'),
('contact_phone', '+94 71 813 0000'),
('contact_address', 'Rajapihilla Road, Sarvodaya Mawatha, Kurunegala'),
('facebook', 'https://www.facebook.com/sipwaycampus');

-- If you already imported an older version, run this:
-- ALTER TABLE categories ADD COLUMN image VARCHAR(255) DEFAULT NULL;

