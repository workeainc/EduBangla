<?php

namespace App\Domain\Student\Actions;

use App\Models\Guardian;
use Illuminate\Support\Facades\Validator;

class CreateGuardian
{
    public function handle(int $schoolId, array $data): Guardian
    {
        $validated = Validator::make($data, [
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:32'],
            'email' => ['nullable', 'email', 'max:255'],
            'address' => ['nullable', 'string'],
        ])->validate();

        return Guardian::create(array_merge($validated, ['school_id' => $schoolId, 'status' => 'active']));
    }
}
