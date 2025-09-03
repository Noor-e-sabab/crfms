--
-- Database: `ewu_registration`
--

-- --------------------------------------------------------

--
-- Dumping data for table `admins`
--

INSERT INTO `admins` (`admin_id`, `username`, `password_hash`, `email`, `created_at`) VALUES
(2, 'admin1', '$2y$10$EILWWvi0rPq9/sOCIAG.p.w.iDfWqQinW76zrstk2q9zvtR2zUpp6', 'admin1@ewu.edu.bd', '2025-09-03 17:58:43'),
(3, 'admin2', '$2y$10$EILWWvi0rPq9/sOCIAG.p.w.iDfWqQinW76zrstk2q9zvtR2zUpp6', 'admin2@ewu.edu.bd', '2025-09-03 17:58:43'),
(4, 'admin3', '$2y$10$EILWWvi0rPq9/sOCIAG.p.w.iDfWqQinW76zrstk2q9zvtR2zUpp6', 'admin3@ewu.edu.bd', '2025-09-03 17:58:43');

-- --------------------------------------------------------

--
-- Dumping data for table `config`
--

INSERT INTO `config` (`id`, `registration_open`, `current_semester`, `current_year`, `last_updated`) VALUES
(1, 1, 'Spring', 2025, '2025-09-03 17:39:49'),
(2, 1, 'Spring', 2025, '2025-09-03 17:39:49'),
(3, 1, 'Spring', 2025, '2025-09-03 17:39:49'),
(4, 1, 'Spring', 2025, '2025-09-03 17:39:49'),
(5, 1, 'Spring', 2025, '2025-09-03 17:39:49');

-- --------------------------------------------------------

--
-- Dumping data for table `courses`
--

INSERT INTO `courses` (`course_id`, `title`, `theory_credits`, `lab_credits`, `has_lab`, `department_id`, `program_id`, `description`, `created_at`) VALUES
('BBA101', 'Principles of Management', 3.0, 0.0, 0, 4, 7, 'Mgmt basics', '2025-09-03 17:15:17'),
('BBA102', 'Principles of Marketing', 3.0, 0.0, 0, 4, 8, 'Marketing mix', '2025-09-03 17:15:17'),
('BBA201', 'Business Finance', 3.0, 0.0, 0, 4, 7, 'Financial concepts', '2025-09-03 17:15:17'),
('BBA202', 'Organizational Behavior', 3.0, 0.0, 0, 4, 9, 'Human behavior', '2025-09-03 17:15:17'),
('CE101', 'Engineering Mechanics', 3.0, 0.0, 0, 3, 5, 'Statics, dynamics', '2025-09-03 17:15:17'),
('CE102', 'Structural Analysis', 3.0, 1.0, 1, 3, 5, 'Beams, trusses', '2025-09-03 17:15:17'),
('CE201', 'Hydraulics', 3.0, 1.0, 1, 3, 6, 'Fluid mechanics', '2025-09-03 17:15:17'),
('CSE101', 'Intro to Computer Science', 3.0, 1.0, 1, 1, 1, 'Basics of CS', '2025-09-03 17:15:17'),
('CSE102', 'Programming Fundamentals', 3.0, 1.0, 1, 1, 1, 'C programming', '2025-09-03 17:15:17'),
('CSE201', 'Data Structures', 3.0, 1.0, 1, 1, 1, 'Linked lists, trees', '2025-09-03 17:15:17'),
('CSE202', 'Algorithms', 3.0, 0.0, 0, 1, 2, 'Algorithm design', '2025-09-03 17:15:17'),
('CSE301', 'Operating Systems', 3.0, 1.0, 1, 1, 2, 'Process mgmt', '2025-09-03 17:15:17'),
('CSE302', 'Database Systems', 3.0, 1.0, 1, 1, 2, 'SQL, design', '2025-09-03 17:15:17'),
('ECO101', 'Microeconomics', 3.0, 0.0, 0, 5, 10, 'Consumer theory', '2025-09-03 17:15:17'),
('ECO102', 'Macroeconomics', 3.0, 0.0, 0, 5, 11, 'GDP, inflation', '2025-09-03 17:15:17'),
('ECO201', 'Econometrics', 3.0, 1.0, 1, 5, 11, 'Regression', '2025-09-03 17:15:17'),
('EEE101', 'Circuit Theory', 3.0, 1.0, 1, 2, 3, 'Ohm, Kirchhoff laws', '2025-09-03 17:15:17'),
('EEE102', 'Electronics I', 3.0, 1.0, 1, 2, 3, 'Semiconductors', '2025-09-03 17:15:17'),
('EEE201', 'Signals and Systems', 3.0, 0.0, 0, 2, 4, 'Signal analysis', '2025-09-03 17:15:17'),
('EEE202', 'Control Systems', 3.0, 1.0, 1, 2, 4, 'Controllers', '2025-09-03 17:15:17'),
('ENG101', 'English Composition', 3.0, 0.0, 0, 6, 12, 'Writing skills', '2025-09-03 17:15:17'),
('ENG102', 'English Literature I', 3.0, 0.0, 0, 6, 12, 'Poetry', '2025-09-03 17:15:17'),
('ENG201', 'Shakespeare Studies', 3.0, 0.0, 0, 6, 13, 'Plays', '2025-09-03 17:15:17'),
('MAT101', 'Calculus I', 3.0, 0.0, 0, 7, 14, 'Limits, derivatives', '2025-09-03 17:15:17'),
('MAT102', 'Linear Algebra', 3.0, 0.0, 0, 7, 14, 'Matrices', '2025-09-03 17:15:17'),
('MAT201', 'Probability and Stats', 3.0, 0.0, 0, 7, 15, 'Probability', '2025-09-03 17:15:17'),
('PHR101', 'Pharmaceutics I', 3.0, 1.0, 1, 8, 16, 'Dosage forms', '2025-09-03 17:15:17'),
('PHR102', 'Pharmaceutical Chemistry', 3.0, 1.0, 1, 8, 16, 'Chemistry', '2025-09-03 17:15:17'),
('PHR201', 'Pharmacology I', 3.0, 0.0, 0, 8, 17, 'Drug effects', '2025-09-03 17:15:17'),
('PHR202', 'Clinical Pharmacy', 3.0, 1.0, 1, 8, 17, 'Hospital pharmacy', '2025-09-03 17:15:17');

-- --------------------------------------------------------

--
-- Dumping data for table `departments`
--

INSERT INTO `departments` (`department_id`, `name`, `short_name`, `created_at`) VALUES
(1, 'Computer Science and Engineering', 'CSE', '2025-09-03 17:08:57'),
(2, 'Electrical and Electronic Engineering', 'EEE', '2025-09-03 17:08:57'),
(3, 'Civil Engineering', 'CE', '2025-09-03 17:08:57'),
(4, 'Business Administration', 'BBA', '2025-09-03 17:08:57'),
(5, 'Economics', 'ECO', '2025-09-03 17:08:57'),
(6, 'English', 'ENG', '2025-09-03 17:08:57'),
(7, 'Mathematics', 'MAT', '2025-09-03 17:08:57'),
(8, 'Pharmacy', 'PHR', '2025-09-03 17:08:57'),
(9, 'Environmental Science', 'ENV', '2025-09-03 17:08:57'),
(10, 'Sociology', 'SOC', '2025-09-03 17:08:57'),
(11, 'Law', 'LAW', '2025-09-03 17:08:57'),
(12, 'Mechanical Engineering', 'ME', '2025-09-03 17:08:57'),
(13, 'Architecture', 'ARCH', '2025-09-03 17:08:57'),
(14, 'Statistics', 'STAT', '2025-09-03 17:08:57'),
(15, 'Anthropology', 'ANTH', '2025-09-03 17:08:57'),
(16, 'Political Science', 'POL', '2025-09-03 17:08:57'),
(17, 'Public Health', 'PH', '2025-09-03 17:08:57'),
(18, 'International Relations', 'IR', '2025-09-03 17:08:57'),
(19, 'Journalism and Media Studies', 'JMS', '2025-09-03 17:08:57'),
(20, 'Physics', 'PHY', '2025-09-03 17:08:57'),
(21, 'Chemistry', 'CHE', '2025-09-03 17:08:57'),
(22, 'Biology', 'BIO', '2025-09-03 17:08:57'),
(23, 'History', 'HIS', '2025-09-03 17:08:57'),
(24, 'Philosophy', 'PHIL', '2025-09-03 17:08:57'),
(25, 'Accounting', 'ACC', '2025-09-03 17:08:57'),
(26, 'Marketing', 'MKT', '2025-09-03 17:08:57'),
(27, 'Finance', 'FIN', '2025-09-03 17:08:57'),
(28, 'Management', 'MGT', '2025-09-03 17:08:57'),
(29, 'Education', 'EDU', '2025-09-03 17:08:57'),
(30, 'Psychology', 'PSY', '2025-09-03 17:08:57');

-- --------------------------------------------------------

--
-- Dumping data for table `faculty`
--

INSERT INTO `faculty` (`faculty_id`, `name`, `email`, `password_hash`, `department_id`, `designation`, `created_at`) VALUES
(1, 'Dr. AT', 'at@ewu.edu.bd', '$2y$10$kgArnQp74jkgrdy7G7RVIesSOeCZaDZH.eTqQCrfQb3n7ndHN5nke', 1, 'Professor', '2025-09-03 17:10:40'),
(2, 'Dr. MSHQ', 'mshq@ewu.edu.bd', '$2y$10$kgArnQp74jkgrdy7G7RVIesSOeCZaDZH.eTqQCrfQb3n7ndHN5nke', 1, 'Assoc. Professor', '2025-09-03 17:10:40'),
(3, 'Mr. AQUIB', 'aquib@ewu.edu.bd', '$2y$10$kgArnQp74jkgrdy7G7RVIesSOeCZaDZH.eTqQCrfQb3n7ndHN5nke', 1, 'Lecturer', '2025-09-03 17:10:40'),
(4, 'Dr. KHALID', 'khalid@ewu.edu.bd', '$2y$10$kgArnQp74jkgrdy7G7RVIesSOeCZaDZH.eTqQCrfQb3n7ndHN5nke', 2, 'Professor', '2025-09-03 17:10:40'),
(5, 'Ms. ANIKA', 'anika@ewu.edu.bd', '$2y$10$kgArnQp74jkgrdy7G7RVIesSOeCZaDZH.eTqQCrfQb3n7ndHN5nke', 2, 'Lecturer', '2025-09-03 17:10:40'),
(6, 'Mr. ARMAN', 'arman@ewu.edu.bd', '$2y$10$kgArnQp74jkgrdy7G7RVIesSOeCZaDZH.eTqQCrfQb3n7ndHN5nke', 3, 'Lecturer', '2025-09-03 17:10:40'),
(7, 'Dr. IMRAN', 'imran@ewu.edu.bd', '$2y$10$kgArnQp74jkgrdy7G7RVIesSOeCZaDZH.eTqQCrfQb3n7ndHN5nke', 4, 'Professor', '2025-09-03 17:10:40'),
(8, 'Dr. MAR', 'mar@ewu.edu.bd', '$2y$10$kgArnQp74jkgrdy7G7RVIesSOeCZaDZH.eTqQCrfQb3n7ndHN5nke', 4, 'Assoc. Professor', '2025-09-03 17:10:40'),
(9, 'Mr. RUTBA', 'rutba@ewu.edu.bd', '$2y$10$kgArnQp74jkgrdy7G7RVIesSOeCZaDZH.eTqQCrfQb3n7ndHN5nke', 5, 'Lecturer', '2025-09-03 17:10:40'),
(10, 'Dr. FHUQ', 'fhuq@ewu.edu.bd', '$2y$10$kgArnQp74jkgrdy7G7RVIesSOeCZaDZH.eTqQCrfQb3n7ndHN5nke', 6, 'Assoc. Professor', '2025-09-03 17:10:40'),
(11, 'Ms. NISHAT', 'nishat@ewu.edu.bd', '$2y$10$kgArnQp74jkgrdy7G7RVIesSOeCZaDZH.eTqQCrfQb3n7ndHN5nke', 6, 'Lecturer', '2025-09-03 17:10:40'),
(12, 'Dr. MOON', 'moon@ewu.edu.bd', '$2y$10$kgArnQp74jkgrdy7G7RVIesSOeCZaDZH.eTqQCrfQb3n7ndHN5nke', 7, 'Professor', '2025-09-03 17:10:40'),
(13, 'Mr. MIR', 'mir@ewu.edu.bd', '$2y$10$kgArnQp74jkgrdy7G7RVIesSOeCZaDZH.eTqQCrfQb3n7ndHN5nke', 7, 'Lecturer', '2025-09-03 17:10:40'),
(14, 'Dr. RABEA', 'rabea@ewu.edu.bd', '$2y$10$kgArnQp74jkgrdy7G7RVIesSOeCZaDZH.eTqQCrfQb3n7ndHN5nke', 8, 'Professor', '2025-09-03 17:10:40'),
(15, 'Mr. MAHCY', 'mahcy@ewu.edu.bd', '$2y$10$kgArnQp74jkgrdy7G7RVIesSOeCZaDZH.eTqQCrfQb3n7ndHN5nke', 8, 'Lecturer', '2025-09-03 17:10:40'),
(16, 'Dr. DSU', 'dsu@ewu.edu.bd', '$2y$10$kgArnQp74jkgrdy7G7RVIesSOeCZaDZH.eTqQCrfQb3n7ndHN5nke', 9, 'Assoc. Professor', '2025-09-03 17:10:40'),
(17, 'Mr. ALI', 'ali@ewu.edu.bd', '$2y$10$kgArnQp74jkgrdy7G7RVIesSOeCZaDZH.eTqQCrfQb3n7ndHN5nke', 10, 'Lecturer', '2025-09-03 17:10:40'),
(18, 'Dr. YS', 'ys@ewu.edu.bd', '$2y$10$kgArnQp74jkgrdy7G7RVIesSOeCZaDZH.eTqQCrfQb3n7ndHN5nke', 11, 'Professor', '2025-09-03 17:10:40'),
(19, 'Mr. SHK', 'shk@ewu.edu.bd', '$2y$10$kgArnQp74jkgrdy7G7RVIesSOeCZaDZH.eTqQCrfQb3n7ndHN5nke', 11, 'Lecturer', '2025-09-03 17:10:40'),
(20, 'Mr. ARIJIT', 'arijit@ewu.edu.bd', '$2y$10$kgArnQp74jkgrdy7G7RVIesSOeCZaDZH.eTqQCrfQb3n7ndHN5nke', 12, 'Lecturer', '2025-09-03 17:10:40'),
(21, 'Ms. SUDDIN', 'suddin@ewu.edu.bd', '$2y$10$kgArnQp74jkgrdy7G7RVIesSOeCZaDZH.eTqQCrfQb3n7ndHN5nke', 12, 'Lecturer', '2025-09-03 17:10:40'),
(22, 'Mr. KMMU', 'kmmu@ewu.edu.bd', '$2y$10$kgArnQp74jkgrdy7G7RVIesSOeCZaDZH.eTqQCrfQb3n7ndHN5nke', 13, 'Lecturer', '2025-09-03 17:10:40'),
(23, 'Dr. TM', 'tm@ewu.edu.bd', '$2y$10$kgArnQp74jkgrdy7G7RVIesSOeCZaDZH.eTqQCrfQb3n7ndHN5nke', 14, 'Professor', '2025-09-03 17:10:40'),
(24, 'Mr. MKN', 'mkn@ewu.edu.bd', '$2y$10$kgArnQp74jkgrdy7G7RVIesSOeCZaDZH.eTqQCrfQb3n7ndHN5nke', 14, 'Lecturer', '2025-09-03 17:10:40'),
(25, 'Ms. DIPAYAN', 'dipayan@ewu.edu.bd', '$2y$10$kgArnQp74jkgrdy7G7RVIesSOeCZaDZH.eTqQCrfQb3n7ndHN5nke', 15, 'Lecturer', '2025-09-03 17:10:40'),
(26, 'Dr. PC', 'pc@ewu.edu.bd', '$2y$10$kgArnQp74jkgrdy7G7RVIesSOeCZaDZH.eTqQCrfQb3n7ndHN5nke', 16, 'Professor', '2025-09-03 17:10:40'),
(27, 'Mr. SKL', 'skl@ewu.edu.bd', '$2y$10$kgArnQp74jkgrdy7G7RVIesSOeCZaDZH.eTqQCrfQb3n7ndHN5nke', 17, 'Lecturer', '2025-09-03 17:10:40'),
(28, 'Dr. REZVI', 'rezvi@ewu.edu.bd', '$2y$10$kgArnQp74jkgrdy7G7RVIesSOeCZaDZH.eTqQCrfQb3n7ndHN5nke', 18, 'Professor', '2025-09-03 17:10:40'),
(29, 'Mr. NRST', 'nrst@ewu.edu.bd', '$2y$10$kgArnQp74jkgrdy7G7RVIesSOeCZaDZH.eTqQCrfQb3n7ndHN5nke', 19, 'Lecturer', '2025-09-03 17:10:40'),
(30, 'Ms. RRD', 'rrd@ewu.edu.bd', '$2y$10$kgArnQp74jkgrdy7G7RVIesSOeCZaDZH.eTqQCrfQb3n7ndHN5nke', 20, 'Lecturer', '2025-09-03 17:10:40');

-- --------------------------------------------------------

--
-- Dumping data for table `programs`
--

INSERT INTO `programs` (`program_id`, `program_name`, `program_code`, `short_code`, `department_id`, `created_at`) VALUES
(1, 'BSc in Computer Science', 'BSCCS', NULL, 1, '2025-09-03 17:09:39'),
(2, 'MSc in Computer Science', 'MSCCS', NULL, 1, '2025-09-03 17:09:39'),
(3, 'BSc in Electrical Engineering', 'BSCEEE', NULL, 2, '2025-09-03 17:09:39'),
(4, 'MSc in Electrical Engineering', 'MSCEE', NULL, 2, '2025-09-03 17:09:39'),
(5, 'BSc in Civil Engineering', 'BSCCE', NULL, 3, '2025-09-03 17:09:39'),
(6, 'MSc in Civil Engineering', 'MSCCE', NULL, 3, '2025-09-03 17:09:39'),
(7, 'BBA in Finance', 'BBAFIN', NULL, 4, '2025-09-03 17:09:39'),
(8, 'BBA in Marketing', 'BBAMKT', NULL, 4, '2025-09-03 17:09:39'),
(9, 'MBA', 'MBA', NULL, 4, '2025-09-03 17:09:39'),
(10, 'BSc in Economics', 'BSCECO', NULL, 5, '2025-09-03 17:09:39'),
(11, 'MSc in Economics', 'MSCECO', NULL, 5, '2025-09-03 17:09:39'),
(12, 'BA in English', 'BAENG', NULL, 6, '2025-09-03 17:09:39'),
(13, 'MA in English', 'MAENG', NULL, 6, '2025-09-03 17:09:39'),
(14, 'BSc in Mathematics', 'BSCMAT', NULL, 7, '2025-09-03 17:09:39'),
(15, 'MSc in Mathematics', 'MSCMAT', NULL, 7, '2025-09-03 17:09:39'),
(16, 'BPharm', 'BPHARM', NULL, 8, '2025-09-03 17:09:39'),
(17, 'MPharm', 'MPHARM', NULL, 8, '2025-09-03 17:09:39'),
(18, 'BSc in Environmental Science', 'BSCENV', NULL, 9, '2025-09-03 17:09:39'),
(19, 'MSc in Environmental Science', 'MSCENV', NULL, 9, '2025-09-03 17:09:39'),
(20, 'BSS in Sociology', 'BSSSOC', NULL, 10, '2025-09-03 17:09:39'),
(21, 'MSS in Sociology', 'MSSSOC', NULL, 10, '2025-09-03 17:09:39'),
(22, 'LLB', 'LLB', NULL, 11, '2025-09-03 17:09:39'),
(23, 'LLM', 'LLM', NULL, 11, '2025-09-03 17:09:39'),
(24, 'BSc in Mechanical Engineering', 'BSCME', NULL, 12, '2025-09-03 17:09:39'),
(25, 'MSc in Mechanical Engineering', 'MSCME', NULL, 12, '2025-09-03 17:09:39'),
(26, 'BArch in Architecture', 'BARCH', NULL, 13, '2025-09-03 17:09:39'),
(27, 'MArch in Architecture', 'MARCH', NULL, 13, '2025-09-03 17:09:39'),
(28, 'BSc in Statistics', 'BSCSTAT', NULL, 14, '2025-09-03 17:09:39'),
(29, 'MSc in Statistics', 'MSCSTAT', NULL, 14, '2025-09-03 17:09:39'),
(30, 'BA in Anthropology', 'BAANTH', NULL, 15, '2025-09-03 17:09:39');

-- --------------------------------------------------------

--
-- Dumping data for table `rooms`
--

INSERT INTO `rooms` (`room_id`, `room_number`, `building`, `capacity`, `room_type`, `created_at`) VALUES
(1, 'FUB-104', 'FUB', 40, 'classroom', '2025-09-03 17:11:28'),
(2, '372', 'SEIP Lab', 30, 'lab', '2025-09-03 17:11:28'),
(3, '530', 'C. Lab-2', 30, 'lab', '2025-09-03 17:11:28'),
(4, '529', 'C. Lab-1', 30, 'lab', '2025-09-03 17:11:28'),
(5, '533', 'C. Lab-3', 30, 'lab', '2025-09-03 17:11:28'),
(6, '301', 'Main', 40, 'classroom', '2025-09-03 17:11:28'),
(7, '302', 'Main', 40, 'classroom', '2025-09-03 17:11:28'),
(8, '303', 'Main', 40, 'classroom', '2025-09-03 17:11:28'),
(9, '401', 'Main', 40, 'classroom', '2025-09-03 17:11:28'),
(10, '402', 'Main', 40, 'classroom', '2025-09-03 17:11:28'),
(11, '403', 'Main', 40, 'classroom', '2025-09-03 17:11:28'),
(12, '404', 'Main', 40, 'classroom', '2025-09-03 17:11:28'),
(13, '501', 'Main', 40, 'classroom', '2025-09-03 17:11:28'),
(14, '502', 'Main', 40, 'classroom', '2025-09-03 17:11:28'),
(15, '503', 'Main', 40, 'classroom', '2025-09-03 17:11:28'),
(16, '601', 'Main', 40, 'classroom', '2025-09-03 17:11:28'),
(17, '602', 'Main', 40, 'classroom', '2025-09-03 17:11:28'),
(18, '701', 'Main', 40, 'classroom', '2025-09-03 17:11:28'),
(19, '702', 'Main', 40, 'classroom', '2025-09-03 17:11:28'),
(20, '801', 'Main', 40, 'classroom', '2025-09-03 17:11:28'),
(21, '802', 'Main', 40, 'classroom', '2025-09-03 17:11:28'),
(22, '901', 'Main', 40, 'classroom', '2025-09-03 17:11:28'),
(23, '902', 'Main', 40, 'classroom', '2025-09-03 17:11:28'),
(24, '1001', 'Main', 40, 'classroom', '2025-09-03 17:11:28'),
(25, '1002', 'Main', 40, 'classroom', '2025-09-03 17:11:28'),
(26, '1101', 'Main', 40, 'classroom', '2025-09-03 17:11:28'),
(27, '1102', 'Main', 40, 'classroom', '2025-09-03 17:11:28'),
(28, '1201', 'Main', 40, 'classroom', '2025-09-03 17:11:28'),
(29, '1202', 'Main', 40, 'classroom', '2025-09-03 17:11:28'),
(30, 'VR-1', 'Virtual Reality Lab', 25, 'lab', '2025-09-03 17:11:28');

-- --------------------------------------------------------

--
-- Dumping data for table `sections`
--

INSERT INTO `sections` (`section_id`, `course_id`, `section_number`, `faculty_id`, `semester`, `year`, `section_type`, `parent_section_id`, `schedule_days`, `schedule_time`, `room_id`, `capacity`, `created_at`) VALUES
(1, 'CSE101', 'A', 1, 'Spring', 2025, 'theory', NULL, 'Mon,Wed', '10:00-11:30', 1, 40, '2025-09-03 17:40:16'),
(2, 'CSE101', 'A1', 3, 'Spring', 2025, 'lab', 1, 'Thu', '14:00-16:00', 4, 20, '2025-09-03 17:40:16'),
(3, 'CSE102', 'A', 2, 'Spring', 2025, 'theory', NULL, 'Tue,Thu', '09:00-10:30', 6, 40, '2025-09-03 17:40:16'),
(4, 'CSE102', 'A1', 3, 'Spring', 2025, 'lab', 3, 'Fri', '11:00-13:00', 5, 20, '2025-09-03 17:40:16'),
(5, 'CSE201', 'B', 1, 'Spring', 2025, 'theory', NULL, 'Mon,Wed', '12:00-13:30', 7, 40, '2025-09-03 17:40:16'),
(6, 'CSE201', 'B1', 3, 'Spring', 2025, 'lab', 5, 'Thu', '09:00-11:00', 2, 20, '2025-09-03 17:40:16'),
(7, 'CSE202', 'A', 2, 'Spring', 2025, 'theory', NULL, 'Tue,Fri', '15:00-16:30', 8, 40, '2025-09-03 17:40:16'),
(8, 'CSE301', 'A', 1, 'Spring', 2025, 'theory', NULL, 'Mon,Wed', '14:00-15:30', 9, 40, '2025-09-03 17:40:16'),
(9, 'CSE301', 'A1', 3, 'Spring', 2025, 'lab', 8, 'Thu', '10:00-12:00', 3, 20, '2025-09-03 17:40:16'),
(10, 'CSE302', 'B', 2, 'Spring', 2025, 'theory', NULL, 'Tue,Thu', '11:00-12:30', 10, 45, '2025-09-03 17:40:16'),
(11, 'EEE101', 'A', 4, 'Spring', 2025, 'theory', NULL, 'Mon,Wed', '09:00-10:30', 6, 40, '2025-09-03 17:40:16'),
(12, 'EEE101', 'A1', 5, 'Spring', 2025, 'lab', 11, 'Thu', '13:00-15:00', 2, 20, '2025-09-03 17:40:16'),
(13, 'EEE102', 'B', 4, 'Spring', 2025, 'theory', NULL, 'Tue,Fri', '12:00-13:30', 7, 40, '2025-09-03 17:40:16'),
(14, 'EEE102', 'B1', 5, 'Spring', 2025, 'lab', 13, 'Thu', '11:00-13:00', 3, 20, '2025-09-03 17:40:16'),
(15, 'EEE201', 'A', 4, 'Spring', 2025, 'theory', NULL, 'Mon,Wed', '16:00-17:30', 8, 40, '2025-09-03 17:40:16'),
(16, 'EEE202', 'A', 5, 'Spring', 2025, 'theory', NULL, 'Tue,Thu', '14:00-15:30', 9, 40, '2025-09-03 17:40:16'),
(17, 'EEE202', 'A1', 5, 'Spring', 2025, 'lab', 16, 'Fri', '09:00-11:00', 5, 20, '2025-09-03 17:40:16'),
(18, 'EEE201', 'B', 4, 'Spring', 2025, 'theory', NULL, 'Tue,Fri', '10:00-11:30', 10, 35, '2025-09-03 17:40:16'),
(19, 'EEE102', 'C', 5, 'Spring', 2025, 'theory', NULL, 'Mon,Wed', '13:00-14:30', 11, 40, '2025-09-03 17:40:16'),
(20, 'EEE102', 'C1', 5, 'Spring', 2025, 'lab', 19, 'Thu', '11:00-13:00', 5, 20, '2025-09-03 17:40:16'),
(21, 'BBA101', 'A', 7, 'Spring', 2025, 'theory', NULL, 'Mon,Wed', '08:00-09:30', 12, 50, '2025-09-03 17:40:16'),
(22, 'BBA102', 'A', 8, 'Spring', 2025, 'theory', NULL, 'Tue,Thu', '09:00-10:30', 13, 45, '2025-09-03 17:40:16'),
(23, 'BBA201', 'A', 7, 'Spring', 2025, 'theory', NULL, 'Mon,Wed', '11:00-12:30', 14, 50, '2025-09-03 17:40:16'),
(24, 'BBA202', 'A', 8, 'Spring', 2025, 'theory', NULL, 'Tue,Thu', '12:00-13:30', 15, 45, '2025-09-03 17:40:16'),
(25, 'ECO101', 'A', 9, 'Spring', 2025, 'theory', NULL, 'Mon,Wed', '13:00-14:30', 16, 40, '2025-09-03 17:40:16'),
(26, 'ECO102', 'A', 9, 'Spring', 2025, 'theory', NULL, 'Tue,Thu', '14:00-15:30', 17, 40, '2025-09-03 17:40:16'),
(27, 'ECO201', 'A', 9, 'Spring', 2025, 'theory', NULL, 'Mon,Wed', '15:00-16:30', 18, 40, '2025-09-03 17:40:16'),
(28, 'ENG101', 'A', 10, 'Spring', 2025, 'theory', NULL, 'Tue,Fri', '09:00-10:30', 19, 40, '2025-09-03 17:40:16'),
(29, 'ENG102', 'A', 11, 'Spring', 2025, 'theory', NULL, 'Mon,Wed', '10:00-11:30', 20, 40, '2025-09-03 17:40:16'),
(30, 'MAT101', 'A', 12, 'Spring', 2025, 'theory', NULL, 'Tue,Thu', '08:00-09:30', 21, 40, '2025-09-03 17:40:16');

-- --------------------------------------------------------

--
-- Dumping data for table `students`
--

INSERT INTO `students` (`student_id`, `name`, `email`, `password_hash`, `department_id`, `program_id`, `admission_year`, `admission_semester`, `status`, `created_at`) VALUES
(1, 'Noor E Sabab', 'noor@ewu.edu.bd', '$2y$10$kgArnQp74jkgrdy7G7RVIesSOeCZaDZH.eTqQCrfQb3n7ndHN5nke', 1, 1, 2022, 'Fall', 'active', '2025-09-03 17:14:08'),
(2, 'Alice Rahman', 'alice@ewu.edu.bd', '$2y$10$kgArnQp74jkgrdy7G7RVIesSOeCZaDZH.eTqQCrfQb3n7ndHN5nke', 1, 1, 2022, 'Fall', 'active', '2025-09-03 17:14:08'),
(3, 'Bob Karim', 'bob@ewu.edu.bd', '$2y$10$kgArnQp74jkgrdy7G7RVIesSOeCZaDZH.eTqQCrfQb3n7ndHN5nke', 1, 1, 2021, 'Spring', 'active', '2025-09-03 17:14:08'),
(4, 'Charlie Hossain', 'charlie@ewu.edu.bd', '$2y$10$kgArnQp74jkgrdy7G7RVIesSOeCZaDZH.eTqQCrfQb3n7ndHN5nke', 1, 1, 2023, 'Fall', 'active', '2025-09-03 17:14:08'),
(5, 'Diana Akter', 'diana@ewu.edu.bd', '$2y$10$kgArnQp74jkgrdy7G7RVIesSOeCZaDZH.eTqQCrfQb3n7ndHN5nke', 2, 3, 2020, 'Spring', 'active', '2025-09-03 17:14:08'),
(6, 'Evan Rahman', 'evan@ewu.edu.bd', '$2y$10$kgArnQp74jkgrdy7G7RVIesSOeCZaDZH.eTqQCrfQb3n7ndHN5nke', 2, 4, 2022, 'Fall', 'active', '2025-09-03 17:14:08'),
(7, 'Farhan Alam', 'farhan@ewu.edu.bd', '$2y$10$kgArnQp74jkgrdy7G7RVIesSOeCZaDZH.eTqQCrfQb3n7ndHN5nke', 3, 5, 2021, 'Fall', 'active', '2025-09-03 17:14:08'),
(8, 'Gulshan Jahan', 'gulshan@ewu.edu.bd', '$2y$10$kgArnQp74jkgrdy7G7RVIesSOeCZaDZH.eTqQCrfQb3n7ndHN5nke', 3, 6, 2020, 'Fall', 'active', '2025-09-03 17:14:08'),
(9, 'Hasibul Hasan', 'hasib@ewu.edu.bd', '$2y$10$kgArnQp74jkgrdy7G7RVIesSOeCZaDZH.eTqQCrfQb3n7ndHN5nke', 4, 7, 2023, 'Spring', 'active', '2025-09-03 17:14:08'),
(10, 'Ishrat Jahan', 'ishrat@ewu.edu.bd', '$2y$10$kgArnQp74jkgrdy7G7RVIesSOeCZaDZH.eTqQCrfQb3n7ndHN5nke', 4, 8, 2021, 'Fall', 'active', '2025-09-03 17:14:08'),
(11, 'Jamil Khan', 'jamil@ewu.edu.bd', '$2y$10$kgArnQp74jkgrdy7G7RVIesSOeCZaDZH.eTqQCrfQb3n7ndHN5nke', 5, 10, 2020, 'Spring', 'active', '2025-09-03 17:14:08'),
(12, 'Kamrul Islam', 'kamrul@ewu.edu.bd', '$2y$10$kgArnQp74jkgrdy7G7RVIesSOeCZaDZH.eTqQCrfQb3n7ndHN5nke', 5, 11, 2023, 'Fall', 'active', '2025-09-03 17:14:08'),
(13, 'Lamia Sultana', 'lamia@ewu.edu.bd', '$2y$10$kgArnQp74jkgrdy7G7RVIesSOeCZaDZH.eTqQCrfQb3n7ndHN5nke', 6, 12, 2022, 'Spring', 'active', '2025-09-03 17:14:08'),
(14, 'Mahin Chowdhury', 'mahin@ewu.edu.bd', '$2y$10$kgArnQp74jkgrdy7G7RVIesSOeCZaDZH.eTqQCrfQb3n7ndHN5nke', 6, 13, 2021, 'Fall', 'active', '2025-09-03 17:14:08'),
(15, 'Nadia Hasan', 'nadia@ewu.edu.bd', '$2y$10$kgArnQp74jkgrdy7G7RVIesSOeCZaDZH.eTqQCrfQb3n7ndHN5nke', 7, 14, 2020, 'Fall', 'active', '2025-09-03 17:14:08'),
(16, 'Omar Faruk', 'omar@ewu.edu.bd', '$2y$10$kgArnQp74jkgrdy7G7RVIesSOeCZaDZH.eTqQCrfQb3n7ndHN5nke', 7, 15, 2022, 'Spring', 'active', '2025-09-03 17:14:08'),
(17, 'Priya Das', 'priya@ewu.edu.bd', '$2y$10$kgArnQp74jkgrdy7G7RVIesSOeCZaDZH.eTqQCrfQb3n7ndHN5nke', 8, 16, 2021, 'Fall', 'active', '2025-09-03 17:14:08'),
(18, 'Qadir Rahman', 'qadir@ewu.edu.bd', '$2y$10$kgArnQp74jkgrdy7G7RVIesSOeCZaDZH.eTqQCrfQb3n7ndHN5nke', 8, 17, 2020, 'Spring', 'active', '2025-09-03 17:14:08'),
(19, 'Riya Islam', 'riya@ewu.edu.bd', '$2y$10$kgArnQp74jkgrdy7G7RVIesSOeCZaDZH.eTqQCrfQb3n7ndHN5nke', 9, 18, 2023, 'Fall', 'active', '2025-09-03 17:14:08'),
(20, 'Sajid Ahmed', 'sajid@ewu.edu.bd', '$2y$10$kgArnQp74jkgrdy7G7RVIesSOeCZaDZH.eTqQCrfQb3n7ndHN5nke', 9, 19, 2022, 'Fall', 'active', '2025-09-03 17:14:08'),
(21, 'Tanvir Alam', 'tanvir@ewu.edu.bd', '$2y$10$kgArnQp74jkgrdy7G7RVIesSOeCZaDZH.eTqQCrfQb3n7ndHN5nke', 10, 20, 2021, 'Spring', 'active', '2025-09-03 17:14:08'),
(22, 'Umme Habiba', 'umme@ewu.edu.bd', '$2y$10$kgArnQp74jkgrdy7G7RVIesSOeCZaDZH.eTqQCrfQb3n7ndHN5nke', 11, 22, 2023, 'Fall', 'active', '2025-09-03 17:14:08'),
(23, 'Vaskar Paul', 'vaskar@ewu.edu.bd', '$2y$10$kgArnQp74jkgrdy7G7RVIesSOeCZaDZH.eTqQCrfQb3n7ndHN5nke', 11, 23, 2021, 'Fall', 'active', '2025-09-03 17:14:08'),
(24, 'Wasiq Rahman', 'wasiq@ewu.edu.bd', '$2y$10$kgArnQp74jkgrdy7G7RVIesSOeCZaDZH.eTqQCrfQb3n7ndHN5nke', 12, 24, 2020, 'Spring', 'active', '2025-09-03 17:14:08'),
(25, 'Xenia Afroz', 'xenia@ewu.edu.bd', '$2y$10$kgArnQp74jkgrdy7G7RVIesSOeCZaDZH.eTqQCrfQb3n7ndHN5nke', 12, 25, 2022, 'Fall', 'active', '2025-09-03 17:14:08'),
(26, 'Yasir Arafat', 'yasir@ewu.edu.bd', '$2y$10$kgArnQp74jkgrdy7G7RVIesSOeCZaDZH.eTqQCrfQb3n7ndHN5nke', 13, 26, 2021, 'Fall', 'active', '2025-09-03 17:14:08'),
(27, 'Zara Haque', 'zara@ewu.edu.bd', '$2y$10$kgArnQp74jkgrdy7G7RVIesSOeCZaDZH.eTqQCrfQb3n7ndHN5nke', 13, 27, 2023, 'Spring', 'active', '2025-09-03 17:14:08'),
(28, 'Arif Khan', 'arif@ewu.edu.bd', '$2y$10$kgArnQp74jkgrdy7G7RVIesSOeCZaDZH.eTqQCrfQb3n7ndHN5nke', 14, 28, 2020, 'Spring', 'active', '2025-09-03 17:14:08'),
(29, 'Bilal Chowdhury', 'bilal@ewu.edu.bd', '$2y$10$kgArnQp74jkgrdy7G7RVIesSOeCZaDZH.eTqQCrfQb3n7ndHN5nke', 15, 29, 2022, 'Fall', 'active', '2025-09-03 17:14:08'),
(30, 'Chitra Saha', 'chitra@ewu.edu.bd', '$2y$10$kgArnQp74jkgrdy7G7RVIesSOeCZaDZH.eTqQCrfQb3n7ndHN5nke', 15, 30, 2021, 'Fall', 'active', '2025-09-03 17:14:08'),
(31, 'Dipon Das', 'dipon@ewu.edu.bd', '$2y$10$kgArnQp74jkgrdy7G7RVIesSOeCZaDZH.eTqQCrfQb3n7ndHN5nke', 1, 2, 2023, 'Spring', 'active', '2025-09-03 17:14:08'),
(32, 'Adnan Rahman', 'adnan@ewu.edu.bd', '$2y$10$kgArnQp74jkgrdy7G7RVIesSOeCZaDZH.eTqQCrfQb3n7ndHN5nke', 1, 1, 2023, 'Fall', 'active', '2025-09-03 17:52:47'),
(33, 'Bushra Akter', 'bushra@ewu.edu.bd', '$2y$10$kgArnQp74jkgrdy7G7RVIesSOeCZaDZH.eTqQCrfQb3n7ndHN5nke', 1, 2, 2022, 'Spring', 'active', '2025-09-03 17:52:47'),
(34, 'Chowdhury Rafi', 'rafi@ewu.edu.bd', '$2y$10$kgArnQp74jkgrdy7G7RVIesSOeCZaDZH.eTqQCrfQb3n7ndHN5nke', 2, 3, 2021, 'Fall', 'active', '2025-09-03 17:52:47'),
(35, 'Deborah Sultana', 'deborah@ewu.edu.bd', '$2y$10$kgArnQp74jkgrdy7G7RVIesSOeCZaDZH.eTqQCrfQb3n7ndHN5nke', 2, 4, 2020, 'Spring', 'active', '2025-09-03 17:52:47'),
(36, 'Emon Hossain', 'emon@ewu.edu.bd', '$2y$10$kgArnQp74jkgrdy7G7RVIesSOeCZaDZH.eTqQCrfQb3n7ndHN5nke', 3, 5, 2023, 'Fall', 'active', '2025-09-03 17:52:47'),
(37, 'Fatema Khatun', 'fatema@ewu.edu.bd', '$2y$10$kgArnQp74jkgrdy7G7RVIesSOeCZaDZH.eTqQCrfQb3n7ndHN5nke', 3, 6, 2022, 'Spring', 'active', '2025-09-03 17:52:47'),
(38, 'Gias Uddin', 'gias@ewu.edu.bd', '$2y$10$kgArnQp74jkgrdy7G7RVIesSOeCZaDZH.eTqQCrfQb3n7ndHN5nke', 4, 7, 2021, 'Fall', 'active', '2025-09-03 17:52:47'),
(39, 'Humayun Kabir', 'humayun@ewu.edu.bd', '$2y$10$kgArnQp74jkgrdy7G7RVIesSOeCZaDZH.eTqQCrfQb3n7ndHN5nke', 4, 8, 2020, 'Spring', 'active', '2025-09-03 17:52:47'),
(40, 'Israt Sultana', 'israt@ewu.edu.bd', '$2y$10$kgArnQp74jkgrdy7G7RVIesSOeCZaDZH.eTqQCrfQb3n7ndHN5nke', 5, 10, 2022, 'Fall', 'active', '2025-09-03 17:52:47'),
(41, 'Jahidul Islam', 'jahid@ewu.edu.bd', '$2y$10$kgArnQp74jkgrdy7G7RVIesSOeCZaDZH.eTqQCrfQb3n7ndHN5nke', 5, 11, 2021, 'Spring', 'active', '2025-09-03 17:52:47'),
(42, 'Khaled Mahmud', 'khaled@ewu.edu.bd', '$2y$10$kgArnQp74jkgrdy7G7RVIesSOeCZaDZH.eTqQCrfQb3n7ndHN5nke', 6, 12, 2023, 'Fall', 'active', '2025-09-03 17:52:47'),
(43, 'Labiba Noor', 'labiba@ewu.edu.bd', '$2y$10$kgArnQp74jkgrdy7G7RVIesSOeCZaDZH.eTqQCrfQb3n7ndHN5nke', 6, 13, 2022, 'Spring', 'active', '2025-09-03 17:52:47'),
(44, 'Mamun Ahmed', 'mamun@ewu.edu.bd', '$2y$10$kgArnQp74jkgrdy7G7RVIesSOeCZaDZH.eTqQCrfQb3n7ndHN5nke', 7, 14, 2021, 'Fall', 'active', '2025-09-03 17:52:47'),
(45, 'Nusrat Jahan', 'nusrat@ewu.edu.bd', '$2y$10$kgArnQp74jkgrdy7G7RVIesSOeCZaDZH.eTqQCrfQb3n7ndHN5nke', 7, 15, 2020, 'Spring', 'active', '2025-09-03 17:52:47'),
(46, 'Oishee Chowdhury', 'oishee@ewu.edu.bd', '$2y$10$kgArnQp74jkgrdy7G7RVIesSOeCZaDZH.eTqQCrfQb3n7ndHN5nke', 8, 16, 2023, 'Fall', 'active', '2025-09-03 17:52:47'),
(47, 'Parvez Hasan', 'parvez@ewu.edu.bd', '$2y$10$kgArnQp74jkgrdy7G7RVIesSOeCZaDZH.eTqQCrfQb3n7ndHN5nke', 8, 17, 2022, 'Spring', 'active', '2025-09-03 17:52:47'),
(48, 'Quazi Tanvir', 'quazi@ewu.edu.bd', '$2y$10$kgArnQp74jkgrdy7G7RVIesSOeCZaDZH.eTqQCrfQb3n7ndHN5nke', 9, 18, 2021, 'Fall', 'active', '2025-09-03 17:52:47'),
(49, 'Rashed Khan', 'rashed@ewu.edu.bd', '$2y$10$kgArnQp74jkgrdy7G7RVIesSOeCZaDZH.eTqQCrfQb3n7ndHN5nke', 9, 19, 2020, 'Spring', 'active', '2025-09-03 17:52:47'),
(50, 'Samia Hossain', 'samia@ewu.edu.bd', '$2y$10$kgArnQp74jkgrdy7G7RVIesSOeCZaDZH.eTqQCrfQb3n7ndHN5nke', 10, 20, 2023, 'Fall', 'active', '2025-09-03 17:52:47'),
(51, 'Tariq Aziz', 'tariq@ewu.edu.bd', '$2y$10$kgArnQp74jkgrdy7G7RVIesSOeCZaDZH.eTqQCrfQb3n7ndHN5nke', 10, 20, 2022, 'Spring', 'active', '2025-09-03 17:52:47'),
(52, 'Umme Kulsum', 'kulsum@ewu.edu.bd', '$2y$10$kgArnQp74jkgrdy7G7RVIesSOeCZaDZH.eTqQCrfQb3n7ndHN5nke', 11, 22, 2021, 'Fall', 'active', '2025-09-03 17:52:47'),
(53, 'Vincent Karim', 'vincent@ewu.edu.bd', '$2y$10$kgArnQp74jkgrdy7G7RVIesSOeCZaDZH.eTqQCrfQb3n7ndHN5nke', 11, 23, 2020, 'Spring', 'active', '2025-09-03 17:52:47'),
(54, 'Wasima Rahman', 'wasima@ewu.edu.bd', '$2y$10$kgArnQp74jkgrdy7G7RVIesSOeCZaDZH.eTqQCrfQb3n7ndHN5nke', 12, 24, 2023, 'Fall', 'active', '2025-09-03 17:52:47'),
(55, 'Xahid Hasan', 'xahid@ewu.edu.bd', '$2y$10$kgArnQp74jkgrdy7G7RVIesSOeCZaDZH.eTqQCrfQb3n7ndHN5nke', 12, 25, 2022, 'Spring', 'active', '2025-09-03 17:52:47'),
(56, 'Yasmin Ara', 'yasmin@ewu.edu.bd', '$2y$10$kgArnQp74jkgrdy7G7RVIesSOeCZaDZH.eTqQCrfQb3n7ndHN5nke', 13, 26, 2021, 'Fall', 'active', '2025-09-03 17:52:47'),
(57, 'Zubair Alam', 'zubair@ewu.edu.bd', '$2y$10$kgArnQp74jkgrdy7G7RVIesSOeCZaDZH.eTqQCrfQb3n7ndHN5nke', 13, 27, 2020, 'Spring', 'active', '2025-09-03 17:52:47'),
(58, 'Abir Hossain', 'abir@ewu.edu.bd', '$2y$10$kgArnQp74jkgrdy7G7RVIesSOeCZaDZH.eTqQCrfQb3n7ndHN5nke', 14, 28, 2022, 'Spring', 'active', '2025-09-03 17:52:47'),
(59, 'Bithi Akter', 'bithi@ewu.edu.bd', '$2y$10$kgArnQp74jkgrdy7G7RVIesSOeCZaDZH.eTqQCrfQb3n7ndHN5nke', 15, 29, 2023, 'Fall', 'active', '2025-09-03 17:52:47'),
(60, 'Chowdhury Samin', 'samin@ewu.edu.bd', '$2y$10$kgArnQp74jkgrdy7G7RVIesSOeCZaDZH.eTqQCrfQb3n7ndHN5nke', 15, 30, 2021, 'Fall', 'active', '2025-09-03 17:52:47'),
(61, 'Dola Sultana', 'dola@ewu.edu.bd', '$2y$10$kgArnQp74jkgrdy7G7RVIesSOeCZaDZH.eTqQCrfQb3n7ndHN5nke', 1, 2, 2020, 'Spring', 'active', '2025-09-03 17:52:47');
