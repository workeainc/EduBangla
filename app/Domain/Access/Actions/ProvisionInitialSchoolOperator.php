<?php

namespace App\Domain\Access\Actions;

use App\Models\School;
use App\Models\SchoolUser;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class ProvisionInitialSchoolOperator
{
    public function handle(School $school, array $data, bool $allowExisting = false, ?\Closure $afterMembership = null): array
    {
        $validated = Validator::make($data, [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'password' => ['required', 'string', 'min:8'],
        ])->validate();

        return DB::transaction(function () use ($school, $validated, $allowExisting, $afterMembership) {
            $existing = User::where('email', $validated['email'])->lockForUpdate()->first();
            if ($existing && ! $allowExisting) {
                throw ValidationException::withMessages(['email' => 'An account with this email already exists. Use --existing-user only for an explicitly compatible account.']);
            }
            if ($existing) {
                if ($existing->schoolMemberships()->exists() && ! $existing->schoolMemberships()->where('school_id', $school->id)->exists()) {
                    throw ValidationException::withMessages(['email' => 'The existing account is already associated with another school.']);
                }
                $membership = $existing->schoolMemberships()->where('school_id', $school->id)->first();
                if ($membership && ($membership->role !== 'school-admin' || $membership->status !== SchoolUser::STATUS_ACTIVE)) {
                    throw ValidationException::withMessages(['membership' => 'Existing school membership is not an active school-admin membership.']);
                }
                if ($membership) {
                    return ['user' => $existing, 'membership' => $membership, 'created' => false, 'resolved' => true];
                }
                $user = $existing;
                $created = false;
            } else {
                $user = User::create(['name' => $validated['name'], 'email' => $validated['email'], 'password' => $validated['password']]);
                $created = true;
            }

            $membership = SchoolUser::create(['school_id' => $school->id, 'user_id' => $user->id, 'role' => 'school-admin', 'status' => SchoolUser::STATUS_ACTIVE]);
            if ($afterMembership) {
                $afterMembership($membership);
            }

            return ['user' => $user, 'membership' => $membership, 'created' => $created, 'resolved' => ! $created];
        });
    }
}
