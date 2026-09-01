<?php

namespace App\Console\Commands;

use App\Domain\Academic\Actions\ProvisionPilotAcademicCatalog as ProvisionAction;
use App\Models\School;
use Illuminate\Console\Command;
use Illuminate\Validation\ValidationException;

class ProvisionPilotAcademicCatalog extends Command
{
    protected $signature = 'edubangla:provision-pilot-catalog
                            {--school-id= : Existing school ID to provision}
                            {--force : Skip the interactive confirmation}';

    protected $description = 'Provision the reviewed pilot academic catalog for one explicit school.';

    public function handle(ProvisionAction $provision): int
    {
        $schoolId = $this->option('school-id');
        if (! is_numeric($schoolId) || (int) $schoolId < 1) {
            $this->error('The --school-id option is required and must be an existing positive school ID.');

            return self::INVALID;
        }

        $school = School::query()->find((int) $schoolId);
        if (! $school) {
            $this->error("School [{$schoolId}] was not found. No records were changed.");

            return self::FAILURE;
        }

        if (! $this->option('force') && ! $this->confirm("Provision the curated pilot catalog for [{$school->id}] {$school->name}?")) {
            $this->warn('Provisioning cancelled. No records were changed.');

            return self::SUCCESS;
        }

        try {
            $summary = $provision->handle($school, config('edubangla-pilot-catalog.catalog'));
        } catch (ValidationException $exception) {
            $this->error('Provisioning failed closed. No new catalog records were retained.');
            foreach ($exception->errors() as $field => $messages) {
                foreach ($messages as $message) {
                    $this->line("{$field}: {$message}");
                }
            }

            return self::FAILURE;
        } catch (\Throwable $exception) {
            $this->error('Provisioning failed. The transaction was rolled back.');
            $this->line($exception->getMessage());

            return self::FAILURE;
        }

        $this->info("Provisioned target school [{$summary['school']['id']}] {$summary['school']['name']}.");
        $this->table(['Entity', 'Created', 'Resolved'], [
            ['Academic years', $summary['academic_year']['created'], $summary['academic_year']['resolved']],
            ['Classes', $summary['classes']['created'], $summary['classes']['resolved']],
            ['Sections', $summary['sections']['created'], $summary['sections']['resolved']],
            ['Subjects', $summary['subjects']['created'], $summary['subjects']['resolved']],
            ['Groups', $summary['groups']['created'], $summary['groups']['resolved']],
        ]);
        $this->line('Academic year activation: '.($summary['academic_year']['activated'] ? 'requested through ActivateAcademicYear.' : 'not requested.'));

        return self::SUCCESS;
    }
}
