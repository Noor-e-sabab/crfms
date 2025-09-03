<?php
/**
 * Comprehensive Validation Helper for CRFMS
 * Contains logical validation functions for course, section, and registration management
 */

/**
 * Validate faculty time conflict
 */
function validateFacultyTimeConflict($db, $faculty_id, $schedule_days, $schedule_time, $semester, $year, $exclude_section_id = null) {
    $exclude_clause = $exclude_section_id ? "AND section_id != ?" : "";
    $query = "SELECT section_id, course_id, section_number, schedule_days, schedule_time 
              FROM sections 
              WHERE faculty_id = ? AND semester = ? AND year = ? $exclude_clause";
    
    $stmt = $db->prepare($query);
    if ($exclude_section_id) {
        $stmt->bind_param('isii', $faculty_id, $semester, $year, $exclude_section_id);
    } else {
        $stmt->bind_param('isi', $faculty_id, $semester, $year);
    }
    $stmt->execute();
    $existing_sections = $stmt->get_result();
    
    while ($section = $existing_sections->fetch_assoc()) {
        if (daysOverlap($schedule_days, $section['schedule_days']) && 
            timeRangesOverlapWithBuffer($schedule_time, $section['schedule_time'])) {
            return [
                'valid' => false, 
                'error' => "Faculty has time conflict with {$section['course_id']} Section {$section['section_number']} at {$section['schedule_days']} {$section['schedule_time']}"
            ];
        }
    }
    return ['valid' => true];
}

/**
 * Validate theory-lab section pairing
 */
function validateTheoryLabPairing($db, $course_id, $parent_section_id, $lab_schedule_days, $lab_schedule_time, $semester, $year) {
    if (!$parent_section_id) {
        return ['valid' => false, 'error' => 'Lab section must be paired with a theory section.'];
    }
    
    // Get parent section details
    $query = "SELECT schedule_days, schedule_time, section_type FROM sections 
              WHERE section_id = ? AND course_id = ? AND semester = ? AND year = ?";
    $stmt = $db->prepare($query);
    $stmt->bind_param('issi', $parent_section_id, $course_id, $semester, $year);
    $stmt->execute();
    $parent = $stmt->get_result()->fetch_assoc();
    
    if (!$parent) {
        return ['valid' => false, 'error' => 'Parent theory section not found.'];
    }
    
    if ($parent['section_type'] !== 'theory') {
        return ['valid' => false, 'error' => 'Lab can only be paired with theory sections.'];
    }
    
    // Check if lab conflicts with theory time
    if (daysOverlap($lab_schedule_days, $parent['schedule_days']) && 
        timeRangesOverlapWithBuffer($lab_schedule_time, $parent['schedule_time'])) {
        return [
            'valid' => false, 
            'error' => "Lab time conflicts with theory section time ({$parent['schedule_days']} {$parent['schedule_time']})"
        ];
    }
    
    return ['valid' => true];
}

/**
 * Validate room type compatibility with section type
 */
function validateRoomTypeCompatibility($room_type, $section_type) {
    $valid_combinations = [
        'theory' => ['classroom', 'both'],
        'lab' => ['lab', 'both']
    ];
    
    return in_array($room_type, $valid_combinations[$section_type]);
}

/**
 * Validate course lab component against section type
 */
function validateCourseLabComponent($db, $course_id, $section_type) {
    $query = "SELECT has_lab, theory_credits, lab_credits FROM courses WHERE course_id = ?";
    $stmt = $db->prepare($query);
    $stmt->bind_param('s', $course_id);
    $stmt->execute();
    $course = $stmt->get_result()->fetch_assoc();
    
    if (!$course) {
        return ['valid' => false, 'error' => 'Course not found.'];
    }
    
    if ($section_type === 'lab' && !$course['has_lab']) {
        return ['valid' => false, 'error' => 'Cannot create lab section for a course without lab component.'];
    }
    
    if ($section_type === 'theory' && $course['theory_credits'] <= 0) {
        return ['valid' => false, 'error' => 'Cannot create theory section for a course with no theory credits.'];
    }
    
    return ['valid' => true];
}

/**
 * Validate section capacity against room capacity
 */
function validateSectionCapacity($db, $room_id, $section_capacity) {
    $query = "SELECT capacity FROM rooms WHERE room_id = ?";
    $stmt = $db->prepare($query);
    $stmt->bind_param('i', $room_id);
    $stmt->execute();
    $room = $stmt->get_result()->fetch_assoc();
    
    if (!$room) {
        return ['valid' => false, 'error' => 'Room not found.'];
    }
    
    if ($section_capacity > $room['capacity']) {
        return ['valid' => false, 'error' => "Section capacity ({$section_capacity}) cannot exceed room capacity ({$room['capacity']})."];
    }
    
    return ['valid' => true];
}

/**
 * Check for comprehensive scheduling conflicts with enhanced time overlap detection
 */
function checkComprehensiveSchedulingConflicts($db, $semester, $year, $room_id, $faculty_id, $schedule_days, $schedule_time, $exclude_section_id = null) {
    $conflicts = [];
    
    // Room conflict check with enhanced time overlap
    $room_query = "SELECT s.section_id, s.course_id, s.section_number, c.title, f.name as faculty_name,
                   s.schedule_days, s.schedule_time
                   FROM sections s 
                   JOIN courses c ON s.course_id = c.course_id 
                   JOIN faculty f ON s.faculty_id = f.faculty_id 
                   WHERE s.semester = ? AND s.year = ? AND s.room_id = ?";
    
    if ($exclude_section_id) {
        $room_query .= " AND s.section_id != ?";
    }
    
    $stmt = $db->prepare($room_query);
    if ($exclude_section_id) {
        $stmt->bind_param('siii', $semester, $year, $room_id, $exclude_section_id);
    } else {
        $stmt->bind_param('sii', $semester, $year, $room_id);
    }
    $stmt->execute();
    $room_sections = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    foreach ($room_sections as $existing) {
        if (daysOverlap($schedule_days, $existing['schedule_days']) && 
            timeRangesOverlapWithBuffer($schedule_time, $existing['schedule_time'], 10)) {
            $conflicts[] = "Room conflict with {$existing['course_id']}-{$existing['section_number']} ({$existing['title']}) taught by {$existing['faculty_name']} at " . 
                          formatDaysDisplay($existing['schedule_days']) . " " . formatTimeDisplay($existing['schedule_time']);
        }
    }
    
    // Faculty conflict check with enhanced time overlap
    $faculty_query = "SELECT s.section_id, s.course_id, s.section_number, c.title, r.room_number, r.building,
                      s.schedule_days, s.schedule_time
                      FROM sections s 
                      JOIN courses c ON s.course_id = c.course_id 
                      JOIN rooms r ON s.room_id = r.room_id 
                      WHERE s.semester = ? AND s.year = ? AND s.faculty_id = ?";
    
    if ($exclude_section_id) {
        $faculty_query .= " AND s.section_id != ?";
    }
    
    $stmt = $db->prepare($faculty_query);
    if ($exclude_section_id) {
        $stmt->bind_param('siii', $semester, $year, $faculty_id, $exclude_section_id);
    } else {
        $stmt->bind_param('sii', $semester, $year, $faculty_id);
    }
    $stmt->execute();
    $faculty_sections = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    foreach ($faculty_sections as $existing) {
        if (daysOverlap($schedule_days, $existing['schedule_days']) && 
            timeRangesOverlapWithBuffer($schedule_time, $existing['schedule_time'], 10)) {
            $location = $existing['room_number'];
            if ($existing['building']) {
                $location .= " ({$existing['building']})";
            }
            $conflicts[] = "Faculty conflict: already teaching {$existing['course_id']}-{$existing['section_number']} ({$existing['title']}) in {$location} at " . 
                          formatDaysDisplay($existing['schedule_days']) . " " . formatTimeDisplay($existing['schedule_time']);
        }
    }
    
    return $conflicts;
}

/**
 * Validate student credit load limits
 */
function validateStudentCreditLoad($db, $student_id, $semester, $year, $additional_credits, $max_credits = 21) {
    $query = "SELECT SUM(c.total_credits) as total_credits FROM registration r 
              JOIN sections sec ON r.section_id = sec.section_id 
              JOIN courses c ON sec.course_id = c.course_id
              WHERE r.student_id = ? AND r.status = 'registered' 
              AND sec.semester = ? AND sec.year = ?";
    $stmt = $db->prepare($query);
    $stmt->bind_param('isi', $student_id, $semester, $year);
    $stmt->execute();
    $current_credits = $stmt->get_result()->fetch_assoc()['total_credits'] ?? 0;
    
    if (($current_credits + $additional_credits) > $max_credits) {
        return [
            'valid' => false, 
            'error' => "Adding this section would exceed the maximum credit limit ({$max_credits} credits). You currently have {$current_credits} credits.",
            'current_credits' => $current_credits,
            'max_credits' => $max_credits
        ];
    }
    
    return ['valid' => true, 'current_credits' => $current_credits];
}

/**
 * Check for course registration conflicts (duplicate registrations, missing components)
 */
function validateCourseRegistrationLogic($db, $student_id, $course_id, $section_type, $semester, $year) {
    // Get existing registrations for this course
    $query = "SELECT sec.section_type, sec.section_id FROM registration r 
              JOIN sections sec ON r.section_id = sec.section_id 
              WHERE r.student_id = ? AND r.status = 'registered' 
              AND sec.course_id = ? AND sec.semester = ? AND sec.year = ?";
    $stmt = $db->prepare($query);
    $stmt->bind_param('issi', $student_id, $course_id, $semester, $year);
    $stmt->execute();
    $existing_registrations = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    $has_theory = false;
    $has_lab = false;
    
    foreach ($existing_registrations as $reg) {
        if ($reg['section_type'] === 'theory') $has_theory = true;
        if ($reg['section_type'] === 'lab') $has_lab = true;
    }
    
    // Check for duplicate section type registration
    if ($section_type === 'theory' && $has_theory) {
        return ['valid' => false, 'error' => 'You are already registered for the theory section of this course.'];
    }
    
    if ($section_type === 'lab' && $has_lab) {
        return ['valid' => false, 'error' => 'You are already registered for the lab section of this course.'];
    }
    
    return ['valid' => true];
}

/**
 * Comprehensive section validation for admin section creation
 */
function validateSectionCreation($db, $course_id, $faculty_id, $semester, $year, $section_type, $schedule_days, $schedule_time, $room_id, $capacity) {
    $errors = [];
    
    // Validate course lab component
    $course_validation = validateCourseLabComponent($db, $course_id, $section_type);
    if (!$course_validation['valid']) {
        $errors[] = $course_validation['error'];
    }
    
    // Validate room type compatibility
    $room_query = "SELECT room_type FROM rooms WHERE room_id = ?";
    $stmt = $db->prepare($room_query);
    $stmt->bind_param('i', $room_id);
    $stmt->execute();
    $room = $stmt->get_result()->fetch_assoc();
    
    if (!$room) {
        $errors[] = 'Selected room not found.';
    } else {
        if (!validateRoomTypeCompatibility($room['room_type'], $section_type)) {
            if ($section_type === 'theory') {
                $errors[] = 'Theory classes cannot be assigned to lab-only rooms. Please select a classroom or multi-purpose room.';
            } else {
                $errors[] = 'Lab classes cannot be assigned to classroom-only rooms. Please select a lab or multi-purpose room.';
            }
        }
    }
    
    // Validate section capacity
    $capacity_validation = validateSectionCapacity($db, $room_id, $capacity);
    if (!$capacity_validation['valid']) {
        $errors[] = $capacity_validation['error'];
    }
    
    // Check scheduling conflicts
    $conflicts = checkComprehensiveSchedulingConflicts($db, $semester, $year, $room_id, $faculty_id, $schedule_days, $schedule_time);
    $errors = array_merge($errors, $conflicts);
    
    return [
        'valid' => empty($errors),
        'errors' => $errors
    ];
}

/**
 * Generate helpful suggestions for fixing validation errors
 */
function generateValidationSuggestions($validation_errors) {
    $suggestions = [];
    
    foreach ($validation_errors as $error) {
        if (strpos($error, 'Room conflict') !== false) {
            $suggestions[] = "Try selecting a different time slot or room for this section.";
        } elseif (strpos($error, 'Faculty conflict') !== false) {
            $suggestions[] = "Assign a different faculty member or choose a different time slot.";
        } elseif (strpos($error, 'Theory classes cannot be assigned to lab-only rooms') !== false) {
            $suggestions[] = "Select a classroom or multi-purpose room for theory sections.";
        } elseif (strpos($error, 'Lab classes cannot be assigned to classroom-only rooms') !== false) {
            $suggestions[] = "Select a lab or multi-purpose room for lab sections.";
        } elseif (strpos($error, 'capacity cannot exceed room capacity') !== false) {
            $suggestions[] = "Reduce the section capacity or choose a larger room.";
        } elseif (strpos($error, 'does not have a lab component') !== false) {
            $suggestions[] = "Create a theory section instead, or add a lab component to the course.";
        }
    }
    
    return array_unique($suggestions);
}

/**
 * Check if a student has schedule conflicts with a given section
 * Returns array of conflict descriptions, empty if no conflicts
 */
function checkStudentScheduleConflicts($db, $student_id, $section_id) {
    $conflicts = [];
    
    // Get the schedule information for the section we want to register for
    $section_query = "SELECT s.schedule_days, s.schedule_time, s.course_id, s.section_number, s.semester, s.year
                      FROM sections s 
                      WHERE s.section_id = ?";
    $stmt = $db->prepare($section_query);
    $stmt->bind_param('i', $section_id);
    $stmt->execute();
    $section_info = $stmt->get_result()->fetch_assoc();
    
    if (!$section_info) {
        return ['Section not found'];
    }
    
    // Get all sections the student is currently registered for in the same semester/year
    $student_sections_query = "SELECT s.course_id, s.section_number, s.schedule_days, s.schedule_time, c.title
                               FROM registration r
                               JOIN sections s ON r.section_id = s.section_id
                               JOIN courses c ON s.course_id = c.course_id
                               WHERE r.student_id = ? 
                               AND r.status = 'registered'
                               AND s.semester = ? 
                               AND s.year = ?
                               AND s.section_id != ?";
    
    $stmt = $db->prepare($student_sections_query);
    $stmt->bind_param('isii', $student_id, $section_info['semester'], $section_info['year'], $section_id);
    $stmt->execute();
    $registered_sections = $stmt->get_result();
    
    // Check for conflicts with each registered section
    while ($existing = $registered_sections->fetch_assoc()) {
        if (daysOverlap($section_info['schedule_days'], $existing['schedule_days']) && 
            timeRangesOverlapWithBuffer($section_info['schedule_time'], $existing['schedule_time'], 10)) {
            
            $conflicts[] = "Time conflict with {$existing['course_id']}-{$existing['section_number']} ({$existing['title']}) at {$existing['schedule_days']} {$existing['schedule_time']}";
        }
    }
    
    return $conflicts;
}

/**
 * Auto-register student for paired lab section when registering for theory
 */
function autoRegisterPairedLab($db, $student_id, $theory_section_id) {
    // Check if theory section has a paired lab
    $query = "SELECT lab.section_id as lab_section_id, lab.capacity, lab.course_id, lab.section_number,
                     (SELECT COUNT(*) FROM registration WHERE section_id = lab.section_id AND status = 'registered') as enrolled
              FROM sections theory
              LEFT JOIN sections lab ON lab.parent_section_id = theory.section_id AND lab.section_type = 'lab'
              WHERE theory.section_id = ? AND theory.section_type = 'theory'";
    
    $stmt = $db->prepare($query);
    $stmt->bind_param('i', $theory_section_id);
    $stmt->execute();
    $lab_info = $stmt->get_result()->fetch_assoc();
    
    if (!$lab_info || !$lab_info['lab_section_id']) {
        return ['success' => true, 'message' => 'No paired lab section found.'];
    }
    
    // Check lab capacity
    if ($lab_info['enrolled'] >= $lab_info['capacity']) {
        return [
            'success' => false, 
            'error' => "Cannot auto-register for lab section {$lab_info['section_number']} - capacity full ({$lab_info['enrolled']}/{$lab_info['capacity']})"
        ];
    }
    
    // Check for student's schedule conflicts with lab
    $conflicts = checkStudentScheduleConflicts($db, $student_id, $lab_info['lab_section_id']);
    if (!empty($conflicts)) {
        return [
            'success' => false, 
            'error' => "Cannot auto-register for lab due to schedule conflict: " . implode('; ', $conflicts)
        ];
    }
    
    // Register student for lab
    $insert_query = "INSERT INTO registration (student_id, section_id, registration_date, status) VALUES (?, ?, NOW(), 'registered')";
    $stmt = $db->prepare($insert_query);
    $stmt->bind_param('ii', $student_id, $lab_info['lab_section_id']);
    
    if ($stmt->execute()) {
        return [
            'success' => true, 
            'message' => "Auto-registered for paired lab section {$lab_info['section_number']}"
        ];
    } else {
        return [
            'success' => false, 
            'error' => "Failed to auto-register for lab section: " . $stmt->error
        ];
    }
}

/**
 * Get proper credit calculation for theory-lab pairs
 */
function calculateSectionCredits($db, $section_id) {
    $query = "SELECT s.section_type, s.parent_section_id, c.theory_credits, c.lab_credits, c.total_credits
              FROM sections s
              JOIN courses c ON s.course_id = c.course_id
              WHERE s.section_id = ?";
    
    $stmt = $db->prepare($query);
    $stmt->bind_param('i', $section_id);
    $stmt->execute();
    $section = $stmt->get_result()->fetch_assoc();
    
    if (!$section) return 0;
    
    if ($section['section_type'] === 'theory') {
        return $section['theory_credits'];
    } elseif ($section['section_type'] === 'lab') {
        return $section['lab_credits'];
    }
    
    return $section['total_credits'];
}

/**
 * Auto-drop student from paired lab section when dropping theory
 */
function autoDropPairedLab($db, $student_id, $theory_section_id) {
    // Find paired lab section
    $query = "SELECT lab.section_id as lab_section_id, lab.section_number, lab.course_id
              FROM sections lab
              WHERE lab.parent_section_id = ? AND lab.section_type = 'lab'";
    $stmt = $db->prepare($query);
    $stmt->bind_param('i', $theory_section_id);
    $stmt->execute();
    $lab_info = $stmt->get_result()->fetch_assoc();
    
    if (!$lab_info) {
        return ['success' => true, 'message' => 'No paired lab section found.'];
    }
    
    // Check if student is registered for the lab
    $check_query = "SELECT registration_id FROM registration 
                    WHERE student_id = ? AND section_id = ? AND status = 'registered'";
    $stmt = $db->prepare($check_query);
    $stmt->bind_param('ii', $student_id, $lab_info['lab_section_id']);
    $stmt->execute();
    $lab_registration = $stmt->get_result()->fetch_assoc();
    
    if (!$lab_registration) {
        return ['success' => true, 'message' => 'Student was not registered for paired lab.'];
    }
    
    // Drop student from lab
    $drop_query = "UPDATE registration SET status = 'dropped' 
                   WHERE student_id = ? AND section_id = ? AND status = 'registered'";
    $stmt = $db->prepare($drop_query);
    $stmt->bind_param('ii', $student_id, $lab_info['lab_section_id']);
    
    if ($stmt->execute() && $stmt->affected_rows > 0) {
        return [
            'success' => true, 
            'message' => "Auto-dropped from paired lab section {$lab_info['section_number']}"
        ];
    } else {
        return [
            'success' => false, 
            'error' => "Failed to auto-drop from lab section: " . $stmt->error
        ];
    }
}
?>
