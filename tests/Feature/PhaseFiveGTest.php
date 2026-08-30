<?php

namespace Tests\Feature;

use App\Domain\Communication\Actions\MarkNoticeDeliveryRead;
use App\Domain\Communication\Actions\PublishNotice;
use App\Domain\Communication\Actions\SaveNoticeDraft;
use App\Domain\Communication\Actions\WithdrawNotice;
use App\Models\AcademicClass;
use App\Models\AcademicYear;
use App\Models\Enrollment;
use App\Models\Notice;
use App\Models\School;
use App\Models\SchoolUser;
use App\Models\Section;
use App\Models\Staff;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class PhaseFiveGTest extends TestCase
{
    use RefreshDatabase;

    private function fixture(): array
    {
        $school = School::factory()->create();
        $admin = User::factory()->create();
        SchoolUser::create(['school_id' => $school->id, 'user_id' => $admin->id, 'role' => 'school-admin', 'status' => 'active']);
        $year = AcademicYear::factory()->create(['school_id' => $school->id]);
        $class = AcademicClass::factory()->create(['school_id' => $school->id]);
        $section = Section::factory()->create(['school_id' => $school->id, 'class_id' => $class->id]);

        return compact('school', 'admin', 'year', 'class', 'section');
    }

    private function recipient(array $f, string $role, bool $active = true): array
    {
        $user = User::factory()->create();
        SchoolUser::create(['school_id' => $f['school']->id, 'user_id' => $user->id, 'role' => $role, 'status' => $active ? 'active' : 'inactive']);
        if ($role === 'student') {
            $profile = Student::factory()->create(['school_id' => $f['school']->id, 'user_id' => $user->id, 'status' => $active ? 'active' : 'inactive']);
            $enrollment = Enrollment::create(['school_id' => $f['school']->id, 'student_id' => $profile->id, 'academic_year_id' => $f['year']->id, 'class_id' => $f['class']->id, 'section_id' => $f['section']->id, 'roll' => Student::count() + 1, 'status' => $active ? 'active' : 'inactive', 'enrolled_at' => '2026-01-01']);
        } elseif ($role === 'teacher') {
            $profile = Teacher::factory()->create(['school_id' => $f['school']->id, 'user_id' => $user->id, 'status' => $active ? 'active' : 'inactive']);
            $enrollment = null;
        } elseif ($role === 'staff') {
            $profile = Staff::factory()->create(['school_id' => $f['school']->id, 'user_id' => $user->id, 'status' => $active ? 'active' : 'inactive']);
            $enrollment = null;
        } else {
            $profile = null;
            $enrollment = null;
        }

        return compact('user', 'profile', 'enrollment');
    }

    private function draft(array $f, array $audiences = [['type' => 'school']]): Notice
    {
        return app(SaveNoticeDraft::class)->handle($f['admin'], $f['school']->id, ['title' => 'Important notice', 'body' => 'Read this safely.', 'audiences' => $audiences]);
    }

    public function test_draft_update_publish_withdraw_and_published_immutability(): void
    {
        $f = $this->fixture();
        $student = $this->recipient($f, 'student');
        $notice = $this->draft($f, [['type' => 'role', 'role' => 'student']]);
        $notice = app(SaveNoticeDraft::class)->handle($f['admin'], $f['school']->id, ['title' => 'Updated notice', 'body' => 'Updated body.', 'audiences' => [['type' => 'class_section', 'academic_year_id' => $f['year']->id, 'class_id' => $f['class']->id, 'section_id' => $f['section']->id]]], $notice->id);
        $published = app(PublishNotice::class)->handle($f['admin'], $f['school']->id, $notice->id);
        $this->assertSame('published', $published->status);
        $this->assertCount(1, $published->deliveries);
        $this->assertSame($student['user']->id, $published->deliveries->first()->user_id);
        try {
            $published->update(['title' => 'Forged rewrite']);
            $this->fail('Published title mutation must fail.');
        } catch (\RuntimeException) {
        }
        try {
            $published->audiences->first()->update(['type' => 'school']);
            $this->fail('Published audience mutation must fail.');
        } catch (\RuntimeException) {
        }
        $withdrawn = app(WithdrawNotice::class)->handle($f['admin'], $f['school']->id, $published->id);
        $this->assertSame('withdrawn', $withdrawn->status);
        $this->assertDatabaseCount('notice_deliveries', 1);
        $this->assertDatabaseHas('audit_logs', ['action' => 'communication.notice_published']);
        $this->assertDatabaseHas('audit_logs', ['action' => 'communication.notice_withdrawn']);
    }

    public function test_server_resolution_excludes_inactive_and_deduplicates_school_and_role_audiences(): void
    {
        $f = $this->fixture();
        $student = $this->recipient($f, 'student');
        $teacher = $this->recipient($f, 'teacher');
        $this->recipient($f, 'student', false);
        $notice = $this->draft($f, [['type' => 'school'], ['type' => 'role', 'role' => 'student']]);
        $published = app(PublishNotice::class)->handle($f['admin'], $f['school']->id, $notice->id);
        $this->assertCount(3, $published->deliveries); // admin, student, teacher
        $this->assertSame(1, $published->deliveries->where('user_id', $student['user']->id)->count());
        $this->assertSame(1, $published->deliveries->where('user_id', $teacher['user']->id)->count());
    }

    public function test_foreign_or_empty_audience_rejects_without_deliveries_or_audit(): void
    {
        $f = $this->fixture();
        $foreign = $this->fixture();
        foreach ([
            [['type' => 'class_section', 'academic_year_id' => $foreign['year']->id, 'class_id' => $foreign['class']->id, 'section_id' => $foreign['section']->id]],
            [['type' => 'role', 'role' => 'parent']],
        ] as $audiences) {
            try {
                $this->draft($f, $audiences);
                $this->fail('Foreign or unsupported audience must fail.');
            } catch (\Throwable $e) {
                $this->assertTrue($e instanceof ValidationException || $e instanceof ModelNotFoundException);
            }
        }
        $empty = $this->draft($f, [['type' => 'class_section', 'academic_year_id' => $f['year']->id, 'class_id' => $f['class']->id, 'section_id' => $f['section']->id]]);
        try {
            app(PublishNotice::class)->handle($f['admin'], $f['school']->id, $empty->id);
            $this->fail('Empty audience must fail.');
        } catch (ValidationException) {
        }
        $this->assertSame('draft', $empty->fresh()->status);
        $this->assertDatabaseCount('notice_deliveries', 0);
        $this->assertDatabaseMissing('audit_logs', ['action' => 'communication.notice_published']);
    }

    public function test_publication_rolls_back_after_first_delivery_and_keeps_existing_history(): void
    {
        $f = $this->fixture();
        $this->recipient($f, 'student');
        $this->recipient($f, 'teacher');
        $notice = $this->draft($f);
        try {
            app(PublishNotice::class)->handle($f['admin'], $f['school']->id, $notice->id, fn () => throw new \RuntimeException('publication rollback'));
            $this->fail();
        } catch (\RuntimeException $e) {
            $this->assertSame('publication rollback', $e->getMessage());
        }
        $this->assertSame('draft', $notice->fresh()->status);
        $this->assertDatabaseCount('notice_deliveries', 0);
        $this->assertDatabaseMissing('audit_logs', ['action' => 'communication.notice_published']);
        $this->assertNull($notice->fresh()->audiences->first()->published_at);
    }

    public function test_recipient_read_ownership_idempotency_and_historical_snapshot(): void
    {
        $f = $this->fixture();
        $student = $this->recipient($f, 'student');
        $peer = $this->recipient($f, 'student');
        $notice = app(PublishNotice::class)->handle($f['admin'], $f['school']->id, $this->draft($f, [['type' => 'role', 'role' => 'student']])->id);
        $delivery = $notice->deliveries->firstWhere('user_id', $student['user']->id);
        $first = app(MarkNoticeDeliveryRead::class)->handle($student['user'], $f['school']->id, 'student', $delivery->id);
        $second = app(MarkNoticeDeliveryRead::class)->handle($student['user'], $f['school']->id, 'student', $delivery->id);
        $this->assertTrue($first->read_at->equalTo($second->read_at));
        try {
            app(MarkNoticeDeliveryRead::class)->handle($peer['user'], $f['school']->id, 'student', $delivery->id);
            $this->fail('Peer delivery read must fail.');
        } catch (ModelNotFoundException) {
        }
        $snapshot = $delivery->recipient_snapshot;
        $student['profile']->update(['first_name' => 'Changed']);
        SchoolUser::where(['school_id' => $f['school']->id, 'user_id' => $student['user']->id])->update(['status' => 'inactive']);
        $this->assertSame($snapshot, $delivery->fresh()->recipient_snapshot);
        $this->assertDatabaseHas('notice_deliveries', ['id' => $delivery->id]);
    }

    public function test_foreign_admin_cannot_create_or_publish(): void
    {
        $f = $this->fixture();
        $foreign = $this->fixture();
        $notice = $this->draft($f);
        $this->expectException(AuthorizationException::class);
        app(PublishNotice::class)->handle($foreign['admin'], $f['school']->id, $notice->id);
    }
}
