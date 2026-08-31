<?php

namespace Tests\Feature;

use App\Livewire\Admin\FinanceManagement;
use App\Models\School;
use App\Models\SchoolUser;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class FinanceFrontendTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_finance_workspace_renders_scoped_finance_states(): void
    {
        $admin = User::factory()->create();
        $school = School::factory()->create();
        SchoolUser::create(['school_id' => $school->id, 'user_id' => $admin->id, 'role' => 'school-admin', 'status' => 'active']);
        $this->actingAs($admin)->withSession(['active_school_id' => $school->id]);

        Livewire::test(FinanceManagement::class, ['school' => $school, 'screen' => 'invoices', 'invoice' => null])
            ->assertSee('School invoices')
            ->assertSee('No invoices');
    }
}
