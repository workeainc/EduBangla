<?php

namespace Tests\Feature;

use App\Domain\Communication\Actions\PublishNotice;
use App\Domain\Communication\Actions\SaveNoticeDraft;
use App\Livewire\Admin\Notices as AdminNotices;
use App\Livewire\Communication\Inbox;
use App\Models\School;
use App\Models\SchoolUser;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CommunicationFrontendTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_notice_workspace_renders_and_saves_draft(): void
    {
        [$school, $admin] = $this->admin();
        Livewire::test(AdminNotices::class, ['school' => $school, 'notice' => null])
            ->assertSee('Audience type')
            ->set('title', 'Exam notice')
            ->set('body', 'Exam starts Monday.')
            ->call('saveDraft')
            ->assertHasNoErrors()
            ->assertSee('Notice draft saved.');
        $this->assertDatabaseHas('notices', ['school_id' => $school->id, 'title' => 'Exam notice', 'status' => 'draft']);
    }

    public function test_recipient_inbox_shows_published_notice_and_marks_delivery_read(): void
    {
        [$school, $admin] = $this->admin();
        $student = User::factory()->create();
        SchoolUser::create(['school_id' => $school->id, 'user_id' => $student->id, 'role' => 'student', 'status' => 'active']);
        $notice = app(SaveNoticeDraft::class)->handle($admin, $school->id, ['title' => 'Published notice', 'body' => 'Please read.', 'audiences' => [['type' => 'role', 'role' => 'student']]]);
        $published = app(PublishNotice::class)->handle($admin, $school->id, $notice->id);
        $delivery = $published->deliveries->first();
        $this->actingAs($student)->withSession(['active_school_id' => $school->id]);
        Livewire::test(Inbox::class, ['school' => $school, 'role' => 'student', 'delivery' => null])
            ->assertSee('Published notice')
            ->assertSee('Unread')
            ->call('markRead', $delivery->id)
            ->assertSee('Notice marked as read.');
    }

    private function admin(): array
    {
        $school = School::factory()->create();
        $admin = User::factory()->create();
        SchoolUser::create(['school_id' => $school->id, 'user_id' => $admin->id, 'role' => 'school-admin', 'status' => 'active']);
        $this->actingAs($admin)->withSession(['active_school_id' => $school->id]);
        return [$school, $admin];
    }
}
