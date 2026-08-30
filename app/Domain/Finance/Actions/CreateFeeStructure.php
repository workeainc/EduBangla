<?php

namespace App\Domain\Finance\Actions;

use App\Domain\Audit\RecordAudit;
use App\Domain\Finance\FinanceAuthorizer;
use App\Models\AcademicClass;
use App\Models\AcademicYear;
use App\Models\FeeCategory;
use App\Models\FeeStructure;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CreateFeeStructure
{
    public function handle(User $actor, int $schoolId, array $data): FeeStructure
    {
        FinanceAuthorizer::admin($actor, $schoolId);

        return DB::transaction(function () use ($actor, $schoolId, $data) {
            $year = AcademicYear::where(['id' => $data['academic_year_id'], 'school_id' => $schoolId])->firstOrFail();
            $class = AcademicClass::where(['id' => $data['class_id'], 'school_id' => $schoolId])->firstOrFail();
            $items = $data['items'] ?? [];
            if (count($items) < 1) {
                throw ValidationException::withMessages(['items' => 'A fee structure needs at least one item.']);
            }
            $structure = FeeStructure::create(['school_id' => $schoolId, 'academic_year_id' => $year->id, 'class_id' => $class->id, 'name' => trim($data['name']), 'status' => 'draft']);
            foreach ($items as $order => $item) {
                $category = FeeCategory::where(['id' => $item['fee_category_id'], 'school_id' => $schoolId, 'status' => 'active'])->firstOrFail();
                if ((float) $item['amount'] < 0) {
                    throw ValidationException::withMessages(['amount' => 'Fee amounts cannot be negative.']);
                }
                $structure->items()->create(['school_id' => $schoolId, 'fee_category_id' => $category->id, 'amount' => $item['amount'], 'due_date' => $item['due_date'] ?? null, 'sort_order' => $order]);
            }
            app(RecordAudit::class)->handle($actor, $schoolId, 'finance.fee_structure_created', $structure, null, ['id' => $structure->id, 'academic_year_id' => $year->id, 'class_id' => $class->id, 'item_count' => count($items)]);

            return $structure->load('items.category');
        });
    }
}
