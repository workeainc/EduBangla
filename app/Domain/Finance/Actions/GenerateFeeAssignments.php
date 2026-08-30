<?php

namespace App\Domain\Finance\Actions;

use App\Domain\Audit\RecordAudit;
use App\Domain\Finance\FinanceAuthorizer;
use App\Models\Enrollment;
use App\Models\FeeStructure;
use App\Models\StudentFeeAssignment;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class GenerateFeeAssignments
{
    public function handle(User $actor, int $schoolId, int $structureId, array $enrollmentIds, ?\Closure $afterFirst = null): int
    {
        FinanceAuthorizer::admin($actor, $schoolId);

        return DB::transaction(function () use ($actor, $schoolId, $structureId, $enrollmentIds, $afterFirst) {
            $structure = FeeStructure::with('items.category')->where(['school_id' => $schoolId, 'id' => $structureId, 'status' => 'active'])->lockForUpdate()->firstOrFail();
            $count = 0;
            foreach ($enrollmentIds as $enrollmentId) {
                $enrollment = Enrollment::where(['school_id' => $schoolId, 'id' => $enrollmentId, 'status' => 'active'])->lockForUpdate()->firstOrFail();
                if ($enrollment->academic_year_id !== $structure->academic_year_id || $enrollment->class_id !== $structure->class_id) {
                    throw ValidationException::withMessages(['enrollment' => 'Enrollment does not match the fee structure scope.']);
                }
                foreach ($structure->items as $item) {
                    if (StudentFeeAssignment::where(['school_id' => $schoolId, 'enrollment_id' => $enrollment->id, 'fee_structure_item_id' => $item->id])->exists()) {
                        continue;
                    }
                    $snapshot = ['category_code' => $item->category->code, 'category_name' => $item->category->name, 'amount' => (string) $item->amount, 'due_date' => optional($item->due_date)->toDateString(), 'academic_year_id' => $structure->academic_year_id, 'class_id' => $structure->class_id, 'section_id' => $enrollment->section_id];
                    $assignment = StudentFeeAssignment::create(['school_id' => $schoolId, 'student_id' => $enrollment->student_id, 'enrollment_id' => $enrollment->id, 'academic_year_id' => $enrollment->academic_year_id, 'class_id' => $enrollment->class_id, 'section_id' => $enrollment->section_id, 'fee_structure_id' => $structure->id, 'fee_structure_item_id' => $item->id, 'fee_category_id' => $item->fee_category_id, 'category_code' => $item->category->code, 'category_name' => $item->category->name, 'amount' => $item->amount, 'due_date' => $item->due_date, 'snapshot' => $snapshot, 'status' => 'assigned']);
                    $count++;
                    if ($afterFirst) {
                        $afterFirst($assignment);
                    }
                }
            }
            if ($count) {
                app(RecordAudit::class)->handle($actor, $schoolId, 'finance.fee_assignments_generated', $structure, null, ['count' => $count]);
            }

            return $count;
        });
    }
}
