<?php

namespace App\Livewire\Admin;

use App\Domain\Communication\Actions\PublishNotice;
use App\Domain\Communication\Actions\SaveNoticeDraft;
use App\Domain\Communication\Actions\WithdrawNotice;
use App\Models\AcademicClass;
use App\Models\AcademicYear;
use App\Models\Notice;
use App\Models\School;
use App\Models\Section;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;

class Notices extends Component
{
    public School $school;

    public ?Notice $notice = null;

    public string $title = '';

    public string $body = '';

    public array $audiences = [['type' => 'school']];

    public function mount(School $school, ?Notice $notice = null): void
    {
        $this->school = $school;
        Gate::authorize('update', $school);
        if ($notice && $notice->school_id !== $school->id) {
            abort(404);
        }
        $this->notice = $notice;
        if ($notice) {
            $this->title = $notice->title;
            $this->body = $notice->body;
            $this->audiences = $notice->audiences->map(fn ($audience) => $audience->only(['type', 'role', 'academic_year_id', 'class_id', 'section_id']))->all();
        }
    }

    public function saveDraft(): void
    {
        $this->validate(['title' => 'required|string|max:255', 'body' => 'required|string', 'audiences' => 'required|array|min:1']);
        $this->notice = app(SaveNoticeDraft::class)->handle(auth()->user(), $this->school->id, ['title' => $this->title, 'body' => $this->body, 'audiences' => $this->audiences], $this->notice?->id);
    }

    public function publish(int $noticeId): void
    {
        $this->notice = app(PublishNotice::class)->handle(auth()->user(), $this->school->id, $noticeId);
    }

    public function withdraw(int $noticeId): void
    {
        $this->notice = app(WithdrawNotice::class)->handle(auth()->user(), $this->school->id, $noticeId);
    }

    public function render()
    {
        return view('livewire.admin.notices', ['notices' => Notice::forSchool($this->school)->withCount('deliveries')->latest()->get(), 'years' => AcademicYear::forSchool($this->school)->get(), 'classes' => AcademicClass::forSchool($this->school)->get(), 'sections' => Section::forSchool($this->school)->get()]);
    }
}
