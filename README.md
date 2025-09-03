# EWU Course Registration & Faculty Management System (CRFMS) v2

## Project Overview
CRFMS (Course Registration and Faculty Management System) is a comprehensive web-based application designed for East West University to manage course registration, faculty assignments, and academic scheduling. The system provides role-based access for administrators, faculty members, and students with dedicated dashboards and functionalities.

## Features

### 🔐 Authentication & Authorization
- Role-based authentication system (Admin, Faculty, Student)
- Secure session management
- Password hashing with PHP's built-in functions
- CSRF token protection

### 👨‍💼 Admin Features
- **System Configuration**: Set current semester, year, and registration status
- **Department Management**: Create and manage academic departments
- **Program Management**: Manage degree programs linked to departments
- **Faculty Management**: Add/edit faculty members with department assignments
- **Student Management**: Student registration and profile management
- **Course Management**: Create courses with theory/lab components and prerequisites
- **Room Management**: Manage classrooms and labs with capacity tracking
- **Section Management**: Create course sections with advanced scheduling
- **Dashboard**: Real-time statistics and system overview

### 👨‍🏫 Faculty Features
- **Personal Dashboard**: View assigned sections and student counts
- **Section Management**: View and manage assigned course sections
- **Student Lists**: Access enrolled student information per section

### 👨‍🎓 Student Features
- **Personal Dashboard**: Academic overview and registration status
- **Course Registration**: Browse and register for available sections
- **Schedule View**: Visual timetable of registered courses
- **Credit Tracking**: Monitor total credits and course load

## Technology Stack

### Backend
- **PHP 7.4+**: Server-side scripting
- **MySQL/MariaDB**: Database management
- **Session Management**: Secure user authentication

### Frontend
- **HTML5 & CSS3**: Structure and styling
- **Bootstrap 5.3.0**: Responsive UI framework
- **Font Awesome 6.0.0**: Icon library
- **JavaScript**: Client-side interactions

### Development Environment
- **XAMPP**: Local development server
- **Apache**: Web server
- **phpMyAdmin**: Database administration

## Project Structure

```
crfms@v2/
├── admin/                      # Administrator interface
│   ├── admins.php             # Admin user management
│   ├── config.php             # System configuration
│   ├── courses.php            # Course management
│   ├── dashboard.php          # Admin dashboard
│   ├── departments.php        # Department management
│   ├── faculty.php            # Faculty management
│   ├── get_course_details.php # AJAX course details
│   ├── get_student_details.php # AJAX student details
│   ├── get_theory_sections.php # AJAX theory sections
│   ├── programs.php           # Program management
│   ├── rooms.php              # Room management
│   ├── sections.php           # Section management
│   └── students.php           # Student management
├── faculty/                   # Faculty interface
│   ├── dashboard.php          # Faculty dashboard
│   └── sections.php           # Faculty section management
├── student/                   # Student interface
│   ├── courses.php            # Course registration
│   ├── dashboard.php          # Student dashboard
│   └── schedule.php           # Class schedule view
├── includes/                  # Shared components
│   ├── admin_dependencies.php # Admin dependency checks
│   ├── config.php             # Global configuration
│   ├── db_connection.php      # Database connection
│   ├── footer.php             # HTML footer
│   ├── functions.php          # Utility functions
│   ├── header.php             # HTML header
│   ├── schedule_generator.php # Schedule generation utilities
│   └── validation_helper.php  # Validation functions
├── database.erd              # Entity Relationship Diagram
├── database.sql              # Database schema and setup
├── index.php                 # Application entry point
├── login.php                 # Authentication page
└── logout.php                # Session termination
```

## Database Schema

### Core Entities

#### config
- System-wide configuration settings
- Current semester and year tracking
- Registration status management

#### departments
- Academic department information
- Department codes and names

#### programs
- Degree programs linked to departments
- Program codes and descriptions

#### faculty
- Faculty member profiles
- Department assignments and designations

#### students
- Student profiles and academic information
- Program enrollment and status tracking

#### courses
- Course catalog with theory/lab components
- Credit hour management
- Prerequisites tracking

#### rooms
- Classroom and laboratory management
- Capacity and type specifications

#### sections
- Course section scheduling
- Faculty assignments
- Time/room allocations
- Theory-lab section pairing

#### registration
- Student course registrations
- Registration status tracking

#### enrollments
- Final enrollment records
- Grade management

#### admins
- Administrator account management

## Installation

### Prerequisites
- XAMPP (Apache + MySQL + PHP)
- PHP 7.4 or higher
- MySQL 5.7 or higher

### Setup Instructions

1. **Clone/Download the project**
   ```bash
   # Place the project in XAMPP htdocs folder
   C:\xampp\htdocs\crfms@v2\
   ```

2. **Database Setup**
   ```sql
   # Import the database schema
   mysql -u root -p < database.sql
   ```
   Or use phpMyAdmin to import `database.sql`

3. **Configuration**
   - Update database credentials in `includes/config.php`
   ```php
   define('DB_HOST', 'localhost');
   define('DB_USERNAME', 'root');
   define('DB_PASSWORD', '');
   define('DB_NAME', 'ewu_registration');
   ```

4. **Start XAMPP Services**
   - Start Apache and MySQL services
   - Access the application at `http://localhost/crfms@v2/`

5. **Initial Setup**
   - Create initial admin account through database
   - Configure system settings via Admin Dashboard

## Key Features Explained

### Smart Scheduling System
- **Time Conflict Detection**: Prevents faculty and room double-booking
- **Theory-Lab Pairing**: Automatic lab section linking to theory sections
- **Schedule Generator**: Creates all possible time slot combinations
- **Buffer Time Management**: Ensures adequate break time between classes

### Advanced Registration Logic
- **Prerequisite Checking**: Validates course prerequisites before registration
- **Capacity Management**: Real-time seat availability tracking
- **Credit Limits**: Prevents over-registration beyond credit limits
- **Schedule Conflicts**: Detects student schedule conflicts

### Security Features
- **CSRF Protection**: All forms protected against CSRF attacks
- **Input Sanitization**: Comprehensive input validation and sanitization
- **Password Security**: Bcrypt password hashing
- **Session Security**: Secure session configuration

### Data Validation
- **Business Logic Validation**: Comprehensive validation helper functions
- **Database Constraints**: Foreign key relationships and data integrity
- **Real-time Validation**: AJAX-powered form validation

## Usage Guide

### For Administrators
1. **Initial Setup**: Configure system via Admin → System Config
2. **Data Entry**: Add departments → programs → faculty → rooms → courses
3. **Section Creation**: Create course sections with proper scheduling
4. **Registration Control**: Open/close registration periods
5. **Monitoring**: Use dashboard for system oversight

### For Faculty
1. **Login**: Use faculty credentials to access faculty dashboard
2. **View Sections**: See assigned course sections and schedules
3. **Student Management**: Access enrolled student lists

### For Students
1. **Login**: Use student credentials to access student dashboard
2. **Browse Courses**: View available sections during registration period
3. **Register**: Add courses to schedule with real-time validation
4. **Schedule View**: Visual timetable of registered courses

## Advanced Features

### Schedule Generation
- Automatic time slot generation for theory and lab sessions
- Day combination management (MW, TR, etc.)
- Conflict-free scheduling algorithms

### Dependency Management
- Progressive system setup with dependency checking
- Required data validation before enabling features
- Smart form hiding/showing based on available data

### Responsive Design
- Mobile-friendly Bootstrap interface
- Adaptive layouts for different screen sizes
- Touch-friendly navigation

## API Endpoints
- `get_course_details.php`: AJAX course information retrieval
- `get_student_details.php`: AJAX student data fetching
- `get_theory_sections.php`: AJAX theory section listing

## Security Considerations
- Input validation on all user inputs
- SQL injection prevention through prepared statements
- XSS protection through output escaping
- Session hijacking prevention
- CSRF token validation

## Performance Features
- Efficient database queries with proper indexing
- Minimal AJAX calls for dynamic content
- Optimized file structure for fast loading
- Cached semester information

## Browser Compatibility
- Chrome 90+
- Firefox 88+
- Safari 14+
- Edge 90+

## Troubleshooting

### Common Issues
1. **Database Connection**: Check XAMPP MySQL service and credentials
2. **Permission Errors**: Ensure proper file permissions
3. **Session Issues**: Check PHP session configuration
4. **Bootstrap/FA Loading**: Verify CDN connectivity

### Debug Mode
Enable error reporting in `includes/config.php` for development:
```php
error_reporting(E_ALL);
ini_set('display_errors', 1);
```

## License
This project is developed for East West University academic purposes.

## Support
For technical support or feature requests, please contact the development team.

---

**Version**: 2.0  
**Last Updated**: September 2025  
**Developer**: EWU Development Team
