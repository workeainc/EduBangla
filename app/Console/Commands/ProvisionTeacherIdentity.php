<?php

namespace App\Console\Commands;

use App\Domain\Access\Actions\ProvisionTeacherIdentity as ProvisionAction;
use App\Models\School;
use Illuminate\Console\Command;
use Illuminate\Validation\ValidationException;

class ProvisionTeacherIdentity extends Command
{
    protected $signature = 'edubangla:provision-teacher-identity
                            {--school-id= : Existing school ID}
                            {--teacher-id= : Existing active teacher profile ID}
                            {--name= : Teacher account name}
                            {--email= : Teacher account email}
                            {--password= : Teacher password (avoid this in shell history)}
                            {--existing-user : Explicitly resolve an already compatible account}
                            {--force : Skip confirmation}';

    protected $description = 'Attach one account and active teacher membership to one existing teacher profile.';

    public function handle(ProvisionAction $provision): int
    {
        $schoolId = $this->option('school-id');
        $teacherId = $this->option('teacher-id');
        if (! is_numeric($schoolId) || (int) $schoolId < 1 || ! ($school = School::find((int) $schoolId)) || ! is_numeric($teacherId) || (int) $teacherId < 1) {
            $this->error('Existing positive --school-id and --teacher-id values are required. No records were changed.');
            return self::INVALID;
        }
        $name = $this->option('name') ?: $this->ask('Teacher account name');
        $email = $this->option('email') ?: $this->ask('Teacher account email');
        $password = $this->option('password') ?: $this->secret('Teacher password (hidden)');
        if (! $this->option('force') && ! $this->confirm("Provision one teacher identity for school [{$school->id}]?")) {
            $this->warn('Provisioning cancelled. No records were changed.');
            return self::SUCCESS;
        }
        try {
            $result = $provision->handle($school, (int) $teacherId, compact('name', 'email', 'password'), (bool) $this->option('existing-user'));
        } catch (ValidationException $e) {
            $this->error('Provisioning failed closed. No unintended records were retained.');
            foreach ($e->errors() as $messages) foreach ($messages as $message) $this->line($message);
            return self::FAILURE;
        } catch (\Throwable $e) {
            $this->error('Provisioning failed and was rolled back.');
            $this->line($e->getMessage());
            return self::FAILURE;
        }
        $this->info($result['created'] ? 'Teacher identity created and linked.' : 'Compatible teacher identity resolved.');
        $this->line('Active teacher membership: '.$result['membership']->id);
        return self::SUCCESS;
    }
}
