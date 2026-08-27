<?php

namespace App\Domain\School;

use App\Models\School;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;

class TenantContext
{
    private ?School $school = null;

    public function activate(School $school, User $user): void
    {
        if (! $school->hasActiveMember($user)) {
            throw new AuthorizationException('The user is not an active member of this school.');
        }

        $this->school = $school;
    }

    public function school(): ?School
    {
        return $this->school;
    }

    public function id(): ?int
    {
        return $this->school?->getKey();
    }

    public function isActiveFor(School $school): bool
    {
        return $this->id() === $school->getKey();
    }

    public function clear(): void
    {
        $this->school = null;
    }
}
