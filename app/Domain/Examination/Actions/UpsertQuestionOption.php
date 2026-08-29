<?php

namespace App\Domain\Examination\Actions;

use App\Models\QuestionOption;
use App\Models\QuestionVersion;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class UpsertQuestionOption
{
    public function validateVersion(QuestionVersion $version): void
    {
        $type = $version->question()->value('type');
        $count = $version->options()->count();
        $correct = $version->options()->where('is_correct', true)->count();
        if ($type === 'mcq' && ($count < 2 || $correct !== 1)) {
            throw ValidationException::withMessages(['option' => 'MCQ-তে অন্তত ২টি option এবং ঠিক ১টি সঠিক উত্তর আবশ্যক।']);
        }
        if ($type === 'true_false' && ($count !== 2 || $correct !== 1)) {
            throw ValidationException::withMessages(['option' => 'True/False-তে true ও false এবং ঠিক ১টি সঠিক উত্তর আবশ্যক।']);
        }
        if (in_array($type, ['short_answer', 'descriptive'], true) && $count > 0) {
            throw ValidationException::withMessages(['option' => 'এই প্রশ্নের option থাকতে পারবে না।']);
        }
    }

    public function handle(QuestionVersion $version, array $data): QuestionOption
    {
        if ($version->school_id !== (int) ($data['school_id'] ?? 0)) {
            throw ValidationException::withMessages(['school_id' => 'Invalid school scope.']);
        }
        $type = $version->question()->value('type');
        if (! in_array($type, ['mcq', 'true_false'], true)) {
            throw ValidationException::withMessages(['option' => 'এই প্রশ্নের জন্য option প্রযোজ্য নয়।']);
        }
        if ($type === 'true_false' && ! in_array($data['option_key'], ['true', 'false'], true)) {
            throw ValidationException::withMessages(['option_key' => 'True/False key invalid.']);
        }

        return DB::transaction(fn () => QuestionOption::updateOrCreate(['question_version_id' => $version->id, 'option_key' => $data['option_key']], ['school_id' => $version->school_id, 'option_text' => $data['option_text'], 'is_correct' => (bool) ($data['is_correct'] ?? false)]));
    }
}
