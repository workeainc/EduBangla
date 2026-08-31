<?php

namespace App\Domain\Student\Actions;

use App\Models\Student;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class CreateStudent
{
    public function handle(int $schoolId, array $data): Student
    {
        $validated = Validator::make($data, [
            'student_code' => ['required', 'string', 'max:64'],
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['nullable', 'string', 'max:255'],
            'date_of_birth' => ['nullable', 'date'],
            'gender' => ['nullable', 'string', 'max:20'],
            'phone' => ['nullable', 'string', 'max:32'],
            'email' => ['nullable', 'email', 'max:255'],
            'address' => ['nullable', 'string'],
            'admission_date' => ['nullable', 'date'],
        ])->validate();

        if (Student::forSchool($schoolId)->where('student_code', $validated['student_code'])->exists()) {
            throw ValidationException::withMessages(['student_code' => 'A student with this code already exists in this school.']);
        }

        return Student::create(array_merge($validated, ['school_id' => $schoolId, 'status' => 'active']));
    }
}
