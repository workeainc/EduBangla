<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

class FrontendPrimitiveTest extends TestCase
{
    public function test_form_primitives_render_labels_and_accessible_errors(): void
    {
        $html = Blade::render('<x-ui.input name="email" label="Email" error="Email is required" required /><x-ui.select name="role" label="Role"><option>Teacher</option></x-ui.select>');

        $this->assertStringContainsString('for="email"', $html);
        $this->assertStringContainsString('aria-invalid="true"', $html);
        $this->assertStringContainsString('id="email-error"', $html);
        $this->assertStringContainsString('Email is required', $html);
        $this->assertStringContainsString('<option>Teacher</option>', $html);
    }

    public function test_state_and_lifecycle_primitives_render_semantic_statuses(): void
    {
        $html = Blade::render('<x-ui.status-badge status="published" /><x-ui.empty-state title="No results" /><x-ui.loading-state label="Saving" /><x-ui.alert type="error">Failed</x-ui.alert><x-ui.data-table caption="Results"><tbody><tr><td data-label="Name">A</td></tr></tbody></x-ui.data-table>');

        $this->assertStringContainsString('Published', $html);
        $this->assertStringContainsString('No results', $html);
        $this->assertStringContainsString('Saving', $html);
        $this->assertStringContainsString('role="alert"', $html);
        $this->assertStringContainsString('data-label="Name"', $html);
        $this->assertStringContainsString('Results', $html);
    }
}
