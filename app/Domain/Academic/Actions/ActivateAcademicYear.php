<?php

namespace App\Domain\Academic\Actions;

use App\Models\AcademicYear;
use App\Models\School;
use Illuminate\Support\Facades\DB;

class ActivateAcademicYear
{
    public function handle(AcademicYear $year): AcademicYear
    {
        return DB::transaction(function () use ($year) {
            School::query()->lockForUpdate()->findOrFail($year->school_id);
            AcademicYear::forSchool($year->school_id)->where('id', '!=', $year->id)->where('status', 'active')->update(['status' => 'closed']);
            $year->update(['status' => 'active']);

            return $year->refresh();
        });
    }
}
