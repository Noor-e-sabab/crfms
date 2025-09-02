# EWU Course Registration and Faculty Management System (CRFMS)

A complete web-based course registration and faculty management system built with PHP, MySQL, and Bootstrap 5.

## Features

### Student Portal
- View available courses for registration
- Register/Drop courses with prerequisite checking
- Time conflict detection
- View current schedule and academic history
- Grade viewing

### Faculty Portal
- View assigned course sections
- Manage class rosters
- Submit and update student grades
- View teaching load and statistics

### Admin Portal
- Complete CRUD operations for:
  - Departments
  - Courses (with prerequisites)
  - Students
  - Faculty
  - Course Sections
  - System Administrators
- Semester management
- Registration control (open/close)
- System configuration

## Technology Stack

- **Backend**: PHP 7.4+
- **Database**: MySQL 5.7+
- **Frontend**: HTML5, CSS3, JavaScript
- **Framework**: Bootstrap 5.3
- **Icons**: Font Awesome 6
- **Server**: Apache (XAMPP)

## Installation & Setup

### Prerequisites
- XAMPP (Apache + MySQL + PHP)
- Web browser
- Text editor (optional)

### Step 1: Setup Database
1. Start XAMPP Control Panel
2. Start Apache and MySQL services
3. Open phpMyAdmin (http://localhost/phpmyadmin)
4. Create a new database or import the provided `database.sql` file
5. The database will be created with all necessary tables

### Step 2: Configure Application
1. Extract/Copy all files to `C:\xampp\htdocs\crfms@v2\`
2. Update database configuration in `includes/config.php` if needed:
   ```php
   define('DB_HOST', 'localhost');
   define('DB_USERNAME', 'root');
   define('DB_PASSWORD', '');
   define('DB_NAME', 'ewu_registration');
   ```

### Step 3: Access the System
1. Open web browser
2. Navigate to: `http://localhost/crfms@v2/`
3. You'll be redirected to the login page

## First Time Setup

### Create First Admin
Since there's no registration system, you need to manually create the first admin:

1. Open phpMyAdmin
2. Go to `ewu_registration` database
3. Open `admins` table
4. Insert a new record:
   ```sql
   INSERT INTO admins (username, email, password_hash) 
   VALUES ('admin', 'admin@ewu.edu', '$2y$10$example_hash_here');
   ```
5. To generate password hash, use PHP:
   ```php
   echo password_hash('your_password', PASSWORD_DEFAULT);
   ```

### Initial System Configuration
1. Login as admin
2. Go to System Config
3. Set current semester and year
4. Enable/disable registration as needed
5. Add departments, courses, faculty, and students

## Usage Guide

### For Administrators

#### Setting Up New Semester
1. **System Config**: Set semester and year
2. **Departments**: Add/manage academic departments
3. **Courses**: Add courses with prerequisites
4. **Faculty**: Add faculty members
5. **Students**: Add student accounts
6. **Sections**: Create course sections for the semester
7. **Enable Registration**: Open registration for students

#### Managing Registration
- Use System Config to open/close registration
- Monitor enrollment through Sections page
- View system statistics on Dashboard

### For Faculty
1. Login with faculty credentials
2. View assigned sections in current semester
3. Access student rosters for each section
4. Submit grades for enrolled students

### For Students
1. Login with student credentials
2. Browse available courses
3. Register for courses (when registration is open)
4. View current schedule and academic history
5. Check grades as they become available

## Security Features

- Password hashing using PHP's `password_hash()`
- CSRF token protection for forms
- SQL injection prevention with prepared statements
- Session-based authentication
- Role-based access control
- Input sanitization and validation

## File Structure

```
crfms@v2/
├── includes/
│   ├── config.php          # Global configuration
│   ├── db_connection.php   # Database connection
│   ├── functions.php       # Utility functions
│   ├── header.php         # Common header
│   └── footer.php         # Common footer
├── admin/                 # Admin panel
│   ├── dashboard.php
│   ├── departments.php
│   ├── courses.php
│   ├── faculty.php
│   ├── students.php
│   ├── sections.php
│   ├── admins.php
│   ├── config.php
│   └── get_course_details.php
├── faculty/               # Faculty panel
│   ├── dashboard.php
│   └── sections.php
├── student/               # Student panel
│   ├── dashboard.php
│   ├── courses.php
│   └── schedule.php
├── database.sql           # Database schema
├── index.php             # Main entry point
├── login.php             # Login page
├── logout.php            # Logout handler
└── README.md             # This file
```

## Database Schema

The system uses 11 main tables:
- `config` - System configuration
- `departments` - Academic departments
- `courses` - Course catalog
- `prerequisites` - Course prerequisites
- `faculty` - Faculty members
- `students` - Student accounts
- `sections` - Course sections
- `registration` - Student registrations
- `enrollments` - Final grades
- `admins` - System administrators

## Troubleshooting

### Common Issues

1. **Database Connection Error**
   - Check XAMPP MySQL service is running
   - Verify database credentials in `config.php`

2. **Page Not Loading**
   - Ensure Apache is running in XAMPP
   - Check file permissions
   - Verify the URL path

3. **Login Issues**
   - Ensure admin account exists in database
   - Check password hash is correct
   - Clear browser cookies/cache

4. **Permission Denied**
   - Check user roles in database
   - Verify session is active

### Support

For technical support or questions about the system, please contact the development team.

## License

This system is developed for educational purposes. Please ensure appropriate licensing for production use.
