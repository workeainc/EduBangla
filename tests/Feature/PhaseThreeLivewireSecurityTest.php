<?php

namespace Tests\Feature;

use App\Livewire\Admin\PhaseThreeManagement;
use App\Models\School;
use App\Models\SchoolUser;
use App\Models\Staff;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PhaseThreeLivewireSecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_livewire_cannot_edit_or_toggle_cross_school_profiles(): void
    {
        $schoolA = School::factory()->create();
        $schoolB = School::factory()->create();
        $admin = User::factory()->create();
        SchoolUser::create(['school_id' => $schoolA->id, 'user_id' => $admin->id, 'role' => 'school-admin', 'status' => 'active']);
        $foreignTeacher = Teacher::factory()->create(['school_id' => $schoolB->id]);
        $foreignStaff = Staff::factory()->create(['school_id' => $schoolB->id]);
        $this->actingAs($admin)->withSession(['active_school_id' => $schoolA->id]);
        $this->expectException(ModelNotFoundException::class);
        Livewire::actingAs($admin)->test(PhaseThreeManagement::class, ['screen' => 'teachers'])->call('edit', $foreignTeacher->id);
    }

    public function test_admin_livewire_cannot_toggle_cross_school_staff(): void
    {
        $schoolA = School::factory()->create();
        $schoolB = School::factory()->create();
        $admin = User::factory()->create();
        SchoolUser::create(['school_id' => $schoolA->id, 'user_id' => $admin->id, 'role' => 'school-admin', 'status' => 'active']);
        $foreign = Staff::factory()->create(['school_id' => $schoolB->id]);
        $this->actingAs($admin)->withSession(['active_school_id' => $schoolA->id]);
        $this->expectException(ModelNotFoundException::class);
        $component = app(PhaseThreeManagement::class);
        $component->screen = 'staff';
        $component->toggleStatus($foreign->id);
    }
}
