<?php
/**
 * Schedule Generator Utility
 * Generates all possible schedule combinations and time slots
 */

class ScheduleGenerator {
    
    // Days of the week
    private static $weekdays = [
        'M' => 'Monday',
        'T' => 'Tuesday', 
        'W' => 'Wednesday',
        'R' => 'Thursday',
        'F' => 'Friday',
        'S' => 'Saturday',
        'U' => 'Sunday'
    ];
    
    /**
     * Generate all possible day combinations
     * @return array Array of day combinations
     */
    public static function generateDayCombinations() {
        $combinations = [];
        $days = array_keys(self::$weekdays);
        
        // Single days
        foreach ($days as $day) {
            $combinations[$day] = self::$weekdays[$day];
        }
        
        // Two-day combinations
        for ($i = 0; $i < count($days); $i++) {
            for ($j = $i + 1; $j < count($days); $j++) {
                $code = $days[$i] . $days[$j];
                $name = self::$weekdays[$days[$i]] . ' & ' . self::$weekdays[$days[$j]];
                $combinations[$code] = $name;
            }
        }
        
        return $combinations;
    }
    
    /**
     * Generate theory class time slots
     * Theory: 8:30am - 7pm (each 1.5 hour with 10 minutes break)
     * @return array Array of time slots
     */
    public static function generateTheoryTimeSlots() {
        $slots = [];
        $startTime = new DateTime('08:30');
        $endOfDay = new DateTime('19:00');
        
        while ($startTime < $endOfDay) {
            $endTime = clone $startTime;
            $endTime->add(new DateInterval('PT1H30M')); // Add 1.5 hours
            
            if ($endTime <= $endOfDay) {
                $timeSlot = $startTime->format('g:i A') . ' - ' . $endTime->format('g:i A');
                $slots[] = $timeSlot;
            }
            
            // Add 10 minutes break + 1.5 hours for next slot = 1:40 total
            $startTime->add(new DateInterval('PT1H40M'));
        }
        
        return $slots;
    }
    
    /**
     * Generate lab class time slots
     * Lab: 8am - 7pm (each 3 hours with 10 minutes break)
     * @return array Array of time slots
     */
    public static function generateLabTimeSlots() {
        $slots = [];
        $startTime = new DateTime('08:00');
        $endOfDay = new DateTime('19:00');
        
        while ($startTime < $endOfDay) {
            $endTime = clone $startTime;
            $endTime->add(new DateInterval('PT3H')); // Add 3 hours
            
            if ($endTime <= $endOfDay) {
                $timeSlot = $startTime->format('g:i A') . ' - ' . $endTime->format('g:i A');
                $slots[] = $timeSlot;
            }
            
            // Add 10 minutes break + 3 hours for next slot = 3:10 total
            $startTime->add(new DateInterval('PT3H10M'));
        }
        
        return $slots;
    }
    
    /**
     * Get all time slots (both theory and lab)
     * @return array Array with 'theory' and 'lab' keys
     */
    public static function getAllTimeSlots() {
        return [
            'theory' => self::generateTheoryTimeSlots(),
            'lab' => self::generateLabTimeSlots()
        ];
    }
    
    /**
     * Get day combinations grouped by type
     * @return array Array with different combination types
     */
    public static function getDayOptionsGrouped() {
        $combinations = self::generateDayCombinations();
        $grouped = [
            'single' => [],
            'pairs' => []
        ];
        
        foreach ($combinations as $code => $name) {
            if (strlen($code) == 1) {
                $grouped['single'][$code] = $name;
            } else {
                $grouped['pairs'][$code] = $name;
            }
        }
        
        return $grouped;
    }
    
    /**
     * Validate if a time slot conflicts with existing schedules
     * @param string $days Day combination (e.g., 'MW', 'T')
     * @param string $timeSlot Time slot (e.g., '8:30 AM - 10:00 AM')
     * @param array $existingSchedules Array of existing schedules
     * @return bool True if conflict exists
     */
    public static function hasScheduleConflict($days, $timeSlot, $existingSchedules) {
        $dayChars = str_split($days);
        
        foreach ($existingSchedules as $schedule) {
            $existingDayChars = str_split($schedule['days']);
            
            // Check if any day overlaps
            if (array_intersect($dayChars, $existingDayChars)) {
                // Check if time overlaps
                if (self::timeSlotOverlaps($timeSlot, $schedule['time'])) {
                    return true;
                }
            }
        }
        
        return false;
    }
    
    /**
     * Check if two time slots overlap
     * @param string $slot1 First time slot
     * @param string $slot2 Second time slot
     * @return bool True if overlap exists
     */
    private static function timeSlotOverlaps($slot1, $slot2) {
        // Parse time slots
        $time1 = self::parseTimeSlot($slot1);
        $time2 = self::parseTimeSlot($slot2);
        
        if (!$time1 || !$time2) return false;
        
        // Check overlap: start1 < end2 && start2 < end1
        return ($time1['start'] < $time2['end'] && $time2['start'] < $time1['end']);
    }
    
    /**
     * Parse time slot string into start and end times
     * @param string $timeSlot Time slot string
     * @return array|false Array with start and end times or false
     */
    private static function parseTimeSlot($timeSlot) {
        if (preg_match('/(\d{1,2}:\d{2}\s*[AP]M)\s*-\s*(\d{1,2}:\d{2}\s*[AP]M)/i', $timeSlot, $matches)) {
            $start = DateTime::createFromFormat('g:i A', trim($matches[1]));
            $end = DateTime::createFromFormat('g:i A', trim($matches[2]));
            
            if ($start && $end) {
                return [
                    'start' => $start,
                    'end' => $end
                ];
            }
        }
        
        return false;
    }
}
?>
