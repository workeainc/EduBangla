<?php

namespace App\Domain\Attendance;

use App\Models\AttendanceSession;

class AttendanceReport
{
    public static function summarize(AttendanceSession $session): array
    {
        $counts = array_fill_keys(AttendanceStatus::values(), 0);
        foreach ($session->attendances as $row) {
            if (array_key_exists($row->status, $counts)) {
                $counts[$row->status]++;
            }
        }
        $denominator = $counts['present'] + $counts['absent'] + $counts['late'] + $counts['excused'];

        return $counts + ['total' => $denominator, 'percentage' => $denominator ? round((($counts['present'] + $counts['late']) / $denominator) * 100, 2) : 0.0];
    }
}
