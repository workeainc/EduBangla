<?php

namespace App\Domain\Examination\Actions;

use App\Models\QuestionOption;
use Illuminate\Support\Facades\DB;

class ReorderQuestionOption
{
    public function handle(int $id, int $order, int $schoolId): QuestionOption
    {
        return DB::transaction(function () use ($id, $order, $schoolId) {
            $o = QuestionOption::where('school_id', $schoolId)->findOrFail($id);
            $o->update(['sort_order' => max(0, $order)]);

            return $o->refresh();
        });
    }
}
