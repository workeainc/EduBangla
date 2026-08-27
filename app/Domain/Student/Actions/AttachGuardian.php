<?php

namespace App\Domain\Student\Actions;

use App\Models\Guardian;
use App\Models\Student;
use Illuminate\Validation\ValidationException;

class AttachGuardian
{
    public function handle(Student $student, Guardian $guardian, string $relationshipType, bool $isPrimary = false): void
    {
        if ($student->school_id !== $guardian->school_id) {
            throw ValidationException::withMessages(['guardian_id' => 'Guardian must belong to the same school as the student.']);
        } $student->guardians()->syncWithoutDetaching([$guardian->id => ['relationship_type' => $relationshipType, 'is_primary' => $isPrimary]]);
    }
}
