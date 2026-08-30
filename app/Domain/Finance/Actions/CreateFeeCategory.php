<?php

namespace App\Domain\Finance\Actions;

use App\Domain\Audit\RecordAudit;
use App\Domain\Finance\FinanceAuthorizer;
use App\Models\FeeCategory;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class CreateFeeCategory
{
    public function handle(User $actor, int $schoolId, array $data): FeeCategory
    {
        FinanceAuthorizer::admin($actor, $schoolId);

        return DB::transaction(function () use ($actor, $schoolId, $data) {
            $category = FeeCategory::create(['school_id' => $schoolId, 'code' => trim($data['code']), 'name' => trim($data['name']), 'description' => $data['description'] ?? null, 'status' => 'active']);
            app(RecordAudit::class)->handle($actor, $schoolId, 'finance.fee_category_created', $category, null, $category->only(['id', 'code', 'name', 'status']));

            return $category;
        });
    }
}
