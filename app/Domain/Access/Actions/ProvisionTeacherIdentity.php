<?php

namespace App\Domain\Access\Actions;

use App\Models\School;
use App\Models\SchoolUser;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class ProvisionTeacherIdentity
{
    public function handle(School $school, int $teacherId, array $data, bool $allowExisting = false, ?\Closure $afterMembership = null): array
    {
        $validated = Validator::make($data, [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'password' => ['required', 'string', 'min:8'],
        ])->validate();

        return DB::transaction(function () use ($school, $teacherId, $validated, $allowExisting, $afterMembership) {
            $teacher = Teacher::where(['id' => $teacherId, 'school_id' => $school->id, 'status' => SchoolUser::STATUS_ACTIVE])->lockForUpdate()->firstOrFail();
            $existing = User::where('email', $validated['email'])->lockForUpdate()->first();
            if ($existing && ! $allowExisting) {
                throw ValidationException::withMessages(['email' => 'An account with this email already exists. Use --existing-user only for this explicitly compatible teacher.']);
            }
            if ($teacher->user_id && (! $existing || $teacher->user_id !== $existing->id)) {
                throw ValidationException::withMessages(['teacher_id' => 'This teacher profile is already linked to another account.']);
            }
            if ($existing) {
                if ($existing->schoolMemberships()->exists() && ! $existing->schoolMemberships()->where('school_id', $school->id)->exists()) {
                    throw ValidationException::withMessages(['email' => 'The existing account is already associated with another school.']);
                }
                $membership = $existing->schoolMemberships()->where('school_id', $school->id)->first();
                if ($membership && ($membership->role !== 'teacher' || $membership->status !== SchoolUser::STATUS_ACTIVE)) {
                    throw ValidationException::withMessages(['membership' => 'Existing school membership is not an active teacher membership.']);
                }
                if ($membership && (! $teacher->user_id || $teacher->user_id === $existing->id)) {
                    if (! $teacher->user_id) {
                        $teacher->update(['user_id' => $existing->id]);
                    }
                    return ['user' => $existing, 'teacher' => $teacher, 'membership' => $membership, 'created' => false, 'resolved' => true];
                }
                $user = $existing;
                $created = false;
            } else {
                $user = User::create(['name' => $validated['name'], 'email' => $validated['email'], 'password' => $validated['password']]);
                $created = true;
            }

            $membership = SchoolUser::create(['school_id' => $school->id, 'user_id' => $user->id, 'role' => 'teacher', 'status' => SchoolUser::STATUS_ACTIVE]);
            $teacher->update(['user_id' => $user->id]);
            if ($afterMembership) {
                $afterMembership($membership);
            }

            return ['user' => $user, 'teacher' => $teacher->fresh(), 'membership' => $membership, 'created' => $created, 'resolved' => ! $created];
        });
    }
}
