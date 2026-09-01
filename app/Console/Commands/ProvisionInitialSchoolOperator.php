<?php

namespace App\Console\Commands;

use App\Domain\Access\Actions\ProvisionInitialSchoolOperator as ProvisionAction;
use App\Models\School;
use Illuminate\Console\Command;
use Illuminate\Validation\ValidationException;

class ProvisionInitialSchoolOperator extends Command
{
    protected $signature = 'edubangla:provision-initial-operator
                            {--school-id= : Existing school ID}
                            {--name= : Operator name}
                            {--email= : Operator email}
                            {--password= : Operator password (avoid this in shell history)}
                            {--existing-user : Explicitly resolve an already compatible account}
                            {--force : Skip confirmation}';

    protected $description = 'Provision one initial school-admin membership for one existing school.';

    public function handle(ProvisionAction $provision): int
    {
        $id = $this->option('school-id');
        if (! is_numeric($id) || (int) $id < 1 || ! ($school = School::find((int) $id))) {
            $this->error('An existing positive --school-id is required. No records were changed.');
            return self::INVALID;
        }
        $name = $this->option('name') ?: $this->ask('Operator name');
        $email = $this->option('email') ?: $this->ask('Operator email');
        $password = $this->option('password');
        if (! $password) {
            $password = $this->secret('Operator password (hidden)');
        }
        if (! $this->option('force') && ! $this->confirm("Provision one school-admin for [{$school->id}] {$school->name}?")) {
            $this->warn('Provisioning cancelled. No records were changed.');
            return self::SUCCESS;
        }
        try {
            $result = $provision->handle($school, compact('name', 'email', 'password'), (bool) $this->option('existing-user'));
        } catch (ValidationException $e) {
            $this->error('Provisioning failed closed. No unintended records were retained.');
            foreach ($e->errors() as $messages) foreach ($messages as $message) $this->line($message);
            return self::FAILURE;
        } catch (\Throwable $e) {
            $this->error('Provisioning failed and was rolled back.');
            $this->line($e->getMessage());
            return self::FAILURE;
        }
        $this->info($result['created'] ? 'Initial school operator created.' : 'Compatible operator resolved.');
        $this->line('Active school-admin membership: '.$result['membership']->id);
        return self::SUCCESS;
    }
}
