<?php

namespace Tests\Feature;

use App\Domain\Communication\Actions\PublishNotice;
use App\Domain\Communication\Actions\SaveNoticeDraft;
use App\Livewire\Admin\Notices as AdminNotices;
use App\Livewire\Communication\Inbox;
use App\Models\School;
use App\Models\SchoolUser;
use App\Models\Staff;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PhaseFiveGScopeTest extends TestCase
{
    use RefreshDatabase;

    private function membership(School $school, string $role): array
    {
        $user = User::factory()->create();
        SchoolUser::create(['school_id' => $school->id, 'user_id' => $user->id, 'role' => $role, 'status' => 'active']);
        match ($role) {
            'student' => Student::factory()->create(['school_id' => $school->id, 'user_id' => $user->id]),
            'teacher' => Teacher::factory()->create(['school_id' => $school->id, 'user_id' => $user->id]),
            'staff' => Staff::factory()->create(['school_id' => $school->id, 'user_id' => $user->id]),
            default => null,
        };

        return compact('user');
    }

    public function test_admin_and_each_recipient_portal_have_expected_ownership_boundary(): void
    {
        $school = School::factory()->create();
        $admin = $this->membership($school, 'school-admin');
        $teacher = $this->membership($school, 'teacher');
        $student = $this->membership($school, 'student');
        $staff = $this->membership($school, 'staff');
        $notice = app(SaveNoticeDraft::class)->handle($admin['user'], $school->id, ['title' => 'All', 'body' => 'Body', 'audiences' => [['type' => 'school']]]);
        $notice = app(PublishNotice::class)->handle($admin['user'], $school->id, $notice->id);
        $teacherDelivery = $notice->deliveries->firstWhere('user_id', $teacher['user']->id);
        $studentDelivery = $notice->deliveries->firstWhere('user_id', $student['user']->id);
        $staffDelivery = $notice->deliveries->firstWhere('user_id', $staff['user']->id);
        $this->actingAs($admin['user'])->withSession(['active_school_id' => $school->id])->get(route('admin.notices', $school))->assertOk();
        $this->actingAs($teacher['user'])->withSession(['active_school_id' => $school->id])->get(route('teacher.notices.show', [$school, $teacherDelivery]))->assertOk();
        $this->actingAs($student['user'])->withSession(['active_school_id' => $school->id])->get(route('student.notices.show', [$school, $studentDelivery]))->assertOk();
        $this->actingAs($staff['user'])->withSession(['active_school_id' => $school->id])->get(route('staff.notices.show', [$school, $staffDelivery]))->assertOk();
        $this->actingAs($student['user'])->withSession(['active_school_id' => $school->id])->get(route('student.notices.show', [$school, $teacherDelivery]))->assertNotFound();
        $this->actingAs($teacher['user'])->withSession(['active_school_id' => $school->id])->get(route('admin.notices', $school))->assertForbidden();
    }

    public function test_parent_guest_inactive_and_foreign_school_are_denied(): void
    {
        $school = School::factory()->create();
        $foreign = School::factory()->create();
        $parent = $this->membership($school, 'parent');
        $inactive = $this->membership($school, 'staff');
        SchoolUser::where(['school_id' => $school->id, 'user_id' => $inactive['user']->id])->update(['status' => 'inactive']);
        $this->get(route('student.notices', $school))->assertRedirect(route('login'));
        $this->actingAs($parent['user'])->withSession(['active_school_id' => $school->id])->get(route('staff.notices', $school))->assertForbidden();
        $this->actingAs($inactive['user'])->withSession(['active_school_id' => $school->id])->get(route('staff.notices', $school))->assertForbidden();
        $this->actingAs($parent['user'])->withSession(['active_school_id' => $school->id])->get(route('student.notices', $foreign))->assertForbidden();
    }

    public function test_livewire_mutations_reject_forged_notice_and_delivery_ids(): void
    {
        $school = School::factory()->create();
        $foreign = School::factory()->create();
        $admin = $this->membership($school, 'school-admin');
        $recipient = $this->membership($school, 'student');
        $foreignAdmin = $this->membership($foreign, 'school-admin');
        $foreignNotice = app(SaveNoticeDraft::class)->handle($foreignAdmin['user'], $foreign->id, ['title' => 'Foreign', 'body' => 'Foreign body', 'audiences' => [['type' => 'school']]]);
        $notice = app(PublishNotice::class)->handle($admin['user'], $school->id, app(SaveNoticeDraft::class)->handle($admin['user'], $school->id, ['title' => 'Local', 'body' => 'Local body', 'audiences' => [['type' => 'school']]])->id);
        $delivery = $notice->deliveries->firstWhere('user_id', $recipient['user']->id);

        $this->actingAs($admin['user']);
        $adminComponent = app(AdminNotices::class);
        $adminComponent->school = $school;

        try {
            $adminComponent->publish($foreignNotice->id);
            $this->fail('Foreign publication must reject.');
        } catch (ModelNotFoundException) {
        }
        $this->assertSame('draft', $foreignNotice->fresh()->status);

        try {
            $adminComponent->withdraw($foreignNotice->id);
            $this->fail('Foreign withdrawal must reject.');
        } catch (ModelNotFoundException) {
        }
        try {
            $this->actingAs($recipient['user']);
            $inbox = app(Inbox::class);
            $inbox->school = $school;
            $inbox->role = 'student';
            $inbox->markRead(999999);
            $this->fail('Forged delivery must reject.');
        } catch (ModelNotFoundException) {
        }
        $inbox->markRead($delivery->id);
        $this->assertNotNull($delivery->fresh()->read_at);
    }
}
