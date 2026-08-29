<?php

namespace App\Domain\Examination\Actions;

use App\Models\QuestionOption;
use Illuminate\Validation\ValidationException;

class DeleteQuestionOption
{
    public function handle(int $id, int $schoolId): void
    {
        $o = QuestionOption::where('school_id', $schoolId)->findOrFail($id);
        $type = $o->questionVersion->question->type;
        if (in_array($type, ['mcq', 'true_false'], true) && $o->questionVersion->options()->count() <= 2) {
            throw ValidationException::withMessages(['option' => 'Minimum option structure বজায় রাখতে option মুছতে পারবেন না।']);
        }$o->delete();
    }
}
