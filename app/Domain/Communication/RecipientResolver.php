<?php

namespace App\Domain\Communication;

use App\Models\AcademicClass;
use App\Models\AcademicYear;
use App\Models\Enrollment;
use App\Models\NoticeAudience;
use App\Models\SchoolUser;
use App\Models\Section;
use App\Models\Staff;
use App\Models\Student;
use App\Models\Teacher;
use Illuminate\Validation\ValidationException;

class RecipientResolver
{
    public function normalize(int $schoolId, array $audiences): array
    {
        if ($audiences === []) {
            throw ValidationException::withMessages(['audiences' => 'At least one audience is required.']);
        }

        return array_map(fn (array $audience) => $this->normalizeOne($schoolId, $audience), $audiences);
    }

    public function recipients(int $schoolId, iterable $audiences): array
    {
        $recipients = [];
        foreach ($audiences as $audience) {
            $data = $audience instanceof NoticeAudience ? $audience->only(['type', 'role', 'academic_year_id', 'class_id', 'section_id']) : $audience;
            foreach ($this->resolveOne($schoolId, $data) as $recipient) {
                $recipients[$recipient['user_id']] = $recipient;
            }
        }

        return array_values($recipients);
    }

    private function normalizeOne(int $schoolId, array $audience): array
    {
        $type = $audience['type'] ?? null;
        if (! in_array($type, ['school', 'role', 'class_section'], true)) {
            throw ValidationException::withMessages(['audiences' => 'Unsupported audience type.']);
        }
        if ($type === 'school') {
            return ['type' => 'school', 'role' => null, 'academic_year_id' => null, 'class_id' => null, 'section_id' => null];
        }
        if ($type === 'role') {
            $role = $audience['role'] ?? null;
            if (! in_array($role, CommunicationAuthorizer::RECIPIENT_ROLES, true)) {
                throw ValidationException::withMessages(['audiences' => 'Unsupported recipient role.']);
            }

            return ['type' => 'role', 'role' => $role, 'academic_year_id' => null, 'class_id' => null, 'section_id' => null];
        }

        $year = AcademicYear::forSchool($schoolId)->whereKey($audience['academic_year_id'] ?? null)->firstOrFail();
        $class = AcademicClass::forSchool($schoolId)->whereKey($audience['class_id'] ?? null)->firstOrFail();
        $section = Section::forSchool($schoolId)->whereKey($audience['section_id'] ?? null)->where('class_id', $class->id)->firstOrFail();

        return ['type' => 'class_section', 'role' => null, 'academic_year_id' => $year->id, 'class_id' => $class->id, 'section_id' => $section->id];
    }

    private function resolveOne(int $schoolId, array $audience): array
    {
        if ($audience['type'] === 'class_section') {
            return Enrollment::query()->where(['enrollments.school_id' => $schoolId, 'enrollments.academic_year_id' => $audience['academic_year_id'], 'enrollments.class_id' => $audience['class_id'], 'enrollments.section_id' => $audience['section_id'], 'enrollments.status' => 'active'])
                ->join('students', 'students.id', '=', 'enrollments.student_id')
                ->join('school_users', function ($join) use ($schoolId) {
                    $join->on('school_users.user_id', '=', 'students.user_id')->where('school_users.school_id', '=', $schoolId)->where('school_users.role', '=', 'student')->where('school_users.status', '=', 'active');
                })
                ->where('students.status', 'active')->whereNotNull('students.user_id')
                ->get(['school_users.user_id', 'students.id as profile_id'])
                ->map(fn ($row) => $this->recipient((int) $row->user_id, 'student', 'student', (int) $row->profile_id))->all();
        }

        $query = SchoolUser::query()->where(['school_id' => $schoolId, 'status' => 'active'])->whereIn('role', CommunicationAuthorizer::RECIPIENT_ROLES);
        if ($audience['type'] === 'role') {
            $query->where('role', $audience['role']);
        }

        return $query->get()->map(function (SchoolUser $membership) use ($schoolId) {
            [$profileType, $profileId] = $this->profile($schoolId, $membership->user_id, $membership->role);

            return $this->recipient($membership->user_id, $membership->role, $profileType, $profileId);
        })->filter()->values()->all();
    }

    private function profile(int $schoolId, int $userId, string $role): array
    {
        return match ($role) {
            'student' => ($row = Student::where(['school_id' => $schoolId, 'user_id' => $userId, 'status' => 'active'])->first()) ? ['student', $row->id] : [null, null],
            'teacher' => ($row = Teacher::where(['school_id' => $schoolId, 'user_id' => $userId, 'status' => 'active'])->first()) ? ['teacher', $row->id] : [null, null],
            'staff' => ($row = Staff::where(['school_id' => $schoolId, 'user_id' => $userId, 'status' => 'active'])->first()) ? ['staff', $row->id] : [null, null],
            default => [null, null],
        };
    }

    private function recipient(int $userId, string $role, ?string $profileType, ?int $profileId): array
    {
        return ['user_id' => $userId, 'recipient_role' => $role, 'profile_type' => $profileType, 'profile_id' => $profileId, 'recipient_snapshot' => ['user_id' => $userId, 'role' => $role, 'profile_type' => $profileType, 'profile_id' => $profileId]];
    }
}
