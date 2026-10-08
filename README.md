# Sipway Online School

Complete PHP online learning platform for Sipway Campus.

## Courses Included
- **A/L Arts:** Sinhala, Media
- **A/L Science:** Biology
- **A/L Technology:** SFT, ET, BST, Agriculture, ICT
- **A/L Commerce:** Accounting, Economics, Business Studies
- **Languages:** Korean
- **Grade 6-7:** All subjects
- **Astrology:** මූලික ජ්‍යොතිෂය

## Installation
1. Import `database.sql` via phpMyAdmin
2. Edit `includes/db.php` if needed (DB credentials)
3. Place folder in `www` / `htdocs`
4. Open `http://localhost/sipway-online-school`

## Logins

### Admin (separate page)
- URL: `http://localhost/sipway-online-school/admin-login.php`
- Email: `admin@sipwaycampus.lk`
- Password: `password`

### Student
- URL: `http://localhost/sipway-online-school/login.php`
- Register first, then login

## Structure
```
sipway-online-school/
├── index.php, courses.php, course-details.php, teachers.php
├── about.php, contact.php, login.php, register.php
├── student-dashboard.php, logout.php, admin-login.php
├── database.sql
├── admin/
│   ├── admin-dashboard.php, admin-courses.php
│   ├── admin-add-course.php, admin-edit-course.php
│   ├── admin-students.php, admin-teachers.php
│   ├── admin-add-teacher.php, admin-edit-teacher.php
│   ├── admin-enrollments.php, admin-messages.php
│   ├── admin-settings.php, admin-logout.php
│   └── includes/
├── includes/ (db, functions, header, navbar, footer)
└── assets/ (css, js, images)
```

## Brand Colors
- Red: #E30613
- Black: #1A1A1A
- Orange: #F5A623
