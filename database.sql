-- Create the database
DROP DATABASE IF EXISTS ewu_registration;
CREATE DATABASE ewu_registration;
USE ewu_registration;

-- Create config table for system settings
CREATE TABLE config (
    id INT PRIMARY KEY AUTO_INCREMENT,
    registration_open BOOLEAN DEFAULT FALSE,
    current_semester VARCHAR(20),
    current_year INT,
    last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Create departments table
CREATE TABLE departments (
    department_id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL UNIQUE,
    short_name VARCHAR(10) NOT NULL UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Create programs table
CREATE TABLE programs (
    program_id INT PRIMARY KEY AUTO_INCREMENT,
    program_name VARCHAR(100) NOT NULL,
    program_code VARCHAR(10) NOT NULL UNIQUE,
    short_code VARCHAR(10),
    department_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (department_id) REFERENCES departments(department_id)
);

-- Create faculty table
CREATE TABLE faculty (
    faculty_id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    department_id INT,
    designation VARCHAR(50) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (department_id) REFERENCES departments(department_id)
);

-- Create students table
CREATE TABLE students (
    student_id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    department_id INT,
    program_id INT,
    admission_year INT NOT NULL,
    admission_semester VARCHAR(20) NOT NULL,
    status ENUM('active', 'inactive', 'graduated') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (department_id) REFERENCES departments(department_id),
    FOREIGN KEY (program_id) REFERENCES programs(program_id)
);

-- Create courses table
CREATE TABLE courses (
    course_id VARCHAR(10) PRIMARY KEY,
    title VARCHAR(200) NOT NULL,
    theory_credits DECIMAL(3,1) NOT NULL,
    lab_credits DECIMAL(3,1) DEFAULT 0,
    total_credits DECIMAL(3,1) GENERATED ALWAYS AS (theory_credits + lab_credits) STORED,
    has_lab BOOLEAN DEFAULT FALSE,
    department_id INT,
    program_id INT,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (department_id) REFERENCES departments(department_id),
    FOREIGN KEY (program_id) REFERENCES programs(program_id)
);

-- Create prerequisites table
CREATE TABLE prerequisites (
    id INT PRIMARY KEY AUTO_INCREMENT,
    course_id VARCHAR(10) NOT NULL,
    prerequisite_course_id VARCHAR(10) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (course_id) REFERENCES courses(course_id),
    FOREIGN KEY (prerequisite_course_id) REFERENCES courses(course_id)
);

-- Create rooms table
CREATE TABLE rooms (
    room_id INT PRIMARY KEY AUTO_INCREMENT,
    room_number VARCHAR(20) NOT NULL UNIQUE,
    building VARCHAR(50),
    capacity INT NOT NULL,
    room_type ENUM('classroom', 'lab', 'both') DEFAULT 'classroom',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Create sections table
CREATE TABLE sections (
    section_id INT PRIMARY KEY AUTO_INCREMENT,
    course_id VARCHAR(10) NOT NULL,
    section_number VARCHAR(10) NOT NULL, -- e.g., 'A1', 'B2', 'L1' for Lab1
    faculty_id INT NOT NULL,
    semester VARCHAR(20) NOT NULL,
    year INT NOT NULL,
    section_type ENUM('theory', 'lab') DEFAULT 'theory',
    parent_section_id INT NULL, -- For lab sections, references the theory section
    schedule_days VARCHAR(20) NOT NULL, -- e.g., 'MW' for Monday-Wednesday
    schedule_time VARCHAR(50) NOT NULL, -- e.g., '10:00-11:20'
    room_id INT NOT NULL,
    capacity INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (course_id) REFERENCES courses(course_id),
    FOREIGN KEY (faculty_id) REFERENCES faculty(faculty_id),
    FOREIGN KEY (room_id) REFERENCES rooms(room_id),
    FOREIGN KEY (parent_section_id) REFERENCES sections(section_id) ON DELETE CASCADE,
    UNIQUE KEY unique_section (course_id, section_number, semester, year)
);

-- Create registration table
CREATE TABLE registration (
    registration_id INT PRIMARY KEY AUTO_INCREMENT,
    student_id INT NOT NULL,
    section_id INT NOT NULL,
    registration_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status ENUM('registered', 'dropped') DEFAULT 'registered',
    FOREIGN KEY (student_id) REFERENCES students(student_id),
    FOREIGN KEY (section_id) REFERENCES sections(section_id)
);

-- Create enrollments table
CREATE TABLE enrollments (
    enrollment_id INT PRIMARY KEY AUTO_INCREMENT,
    student_id INT NOT NULL,
    section_id INT NOT NULL,
    grade VARCHAR(2),
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(student_id),
    FOREIGN KEY (section_id) REFERENCES sections(section_id)
);

-- Create admins table
CREATE TABLE admins (
    admin_id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Create necessary indexes
CREATE INDEX idx_registration_student ON registration(student_id);
CREATE INDEX idx_registration_section ON registration(section_id);
CREATE INDEX idx_enrollments_student ON enrollments(student_id);
CREATE INDEX idx_enrollments_section ON enrollments(section_id);
CREATE INDEX idx_sections_course ON sections(course_id);
CREATE INDEX idx_sections_faculty ON sections(faculty_id);
