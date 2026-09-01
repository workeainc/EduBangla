<?php

namespace App\Domain\Academic\Actions;

use App\Models\AcademicClass;
use App\Models\AcademicGroup;
use App\Models\AcademicYear;
use App\Models\School;
use App\Models\Section;
use App\Models\Subject;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class ProvisionPilotAcademicCatalog
{
    /**
     * @return array{school: array{id: int, name: string}, academic_year: array{created: int, resolved: int, activated: bool}, classes: array{created: int, resolved: int}, sections: array{created: int, resolved: int}, subjects: array{created: int, resolved: int}, groups: array{created: int, resolved: int}}
     *
     * @throws ValidationException
     */
    public function handle(School $school, array $catalog): array
    {
        $this->validateCatalog($catalog);

        return DB::transaction(function () use ($school, $catalog) {
            $school = School::query()->lockForUpdate()->findOrFail($school->id);
            $summary = $this->emptySummary($school);

            [$year, $created] = $this->resolveYear($school, $catalog['academic_year']);
            $this->count($summary['academic_year'], $created);

            $classes = [];
            foreach ($catalog['classes'] as $definition) {
                [$class, $created] = $this->resolveClass($school, $definition);
                $classes[$class->code] = $class;
                $this->count($summary['classes'], $created);
            }

            foreach ($catalog['sections'] as $definition) {
                $class = $classes[$definition['class_code']] ?? null;
                if (! $class) {
                    throw ValidationException::withMessages(['sections' => "Unknown class code [{$definition['class_code']}]."]);
                }

                [, $created] = $this->resolveSection($school, $class, $definition);
                $this->count($summary['sections'], $created);
            }

            foreach ($catalog['subjects'] as $definition) {
                [, $created] = $this->resolveSubject($school, $definition);
                $this->count($summary['subjects'], $created);
            }

            foreach ($catalog['groups'] ?? [] as $definition) {
                [, $created] = $this->resolveGroup($school, $definition);
                $this->count($summary['groups'], $created);
            }

            if ($catalog['academic_year']['activate']) {
                app(ActivateAcademicYear::class)->handle($year);
                $summary['academic_year']['activated'] = true;
            }

            return $summary;
        });
    }

    private function validateCatalog(array $catalog): void
    {
        $validator = Validator::make($catalog, [
            'academic_year' => ['required', 'array:name,start_date,end_date,activate'],
            'academic_year.name' => ['required', 'string', 'max:255'],
            'academic_year.start_date' => ['required', 'date'],
            'academic_year.end_date' => ['required', 'date', 'after_or_equal:academic_year.start_date'],
            'academic_year.activate' => ['required', 'boolean'],
            'classes' => ['required', 'array', 'min:1'],
            'classes.*' => ['array:name,code,sort_order,status'],
            'classes.*.name' => ['required', 'string', 'max:255'],
            'classes.*.code' => ['required', 'string', 'max:32'],
            'classes.*.sort_order' => ['required', 'integer', 'min:0'],
            'classes.*.status' => ['required', 'string', 'max:20'],
            'sections' => ['required', 'array', 'min:1'],
            'sections.*' => ['array:class_code,name,code,capacity,status'],
            'sections.*.class_code' => ['required', 'string', 'max:32'],
            'sections.*.name' => ['required', 'string', 'max:255'],
            'sections.*.code' => ['required', 'string', 'max:32'],
            'sections.*.capacity' => ['nullable', 'integer', 'min:1', 'max:65535'],
            'sections.*.status' => ['required', 'string', 'max:20'],
            'subjects' => ['required', 'array', 'min:1'],
            'subjects.*' => ['array:name,code,short_name,status'],
            'subjects.*.name' => ['required', 'string', 'max:255'],
            'subjects.*.code' => ['required', 'string', 'max:32'],
            'subjects.*.short_name' => ['nullable', 'string', 'max:64'],
            'subjects.*.status' => ['required', 'string', 'max:20'],
            'groups' => ['sometimes', 'array'],
            'groups.*' => ['array:name,code,status'],
            'groups.*.name' => ['required', 'string', 'max:255'],
            'groups.*.code' => ['required', 'string', 'max:32'],
            'groups.*.status' => ['required', 'string', 'max:20'],
        ]);

        $validator->after(function ($validator) use ($catalog): void {
            $classCodes = array_column($catalog['classes'] ?? [], 'code');
            if (count($classCodes) !== count(array_unique($classCodes))) {
                $validator->errors()->add('classes', 'Class codes must be unique within the curated catalog.');
            }

            $this->ensureUniqueDefinitions($validator, $catalog['classes'] ?? [], 'classes', 'name', 'code');
            $this->ensureUniqueDefinitions($validator, $catalog['subjects'] ?? [], 'subjects', 'name', 'code');
            $this->ensureUniqueDefinitions($validator, $catalog['groups'] ?? [], 'groups', 'name', 'code');

            foreach ($catalog['sections'] ?? [] as $index => $section) {
                if (! in_array($section['class_code'] ?? null, $classCodes, true)) {
                    $validator->errors()->add("sections.{$index}.class_code", 'Each section must reference a class code in this catalog.');
                }
            }

            $sectionKeys = array_map(fn (array $section) => ($section['class_code'] ?? '').'|'.($section['name'] ?? ''), $catalog['sections'] ?? []);
            $sectionCodes = array_map(fn (array $section) => ($section['class_code'] ?? '').'|'.($section['code'] ?? ''), $catalog['sections'] ?? []);
            if (count($sectionKeys) !== count(array_unique($sectionKeys)) || count($sectionCodes) !== count(array_unique($sectionCodes))) {
                $validator->errors()->add('sections', 'Section names and codes must be unique within each curated class.');
            }
        });

        $validator->validate();
    }

    /** @return array{0: AcademicYear, 1: bool} */
    private function resolveYear(School $school, array $definition): array
    {
        $records = AcademicYear::query()->where('school_id', $school->id)->where('name', $definition['name'])->lockForUpdate()->get();
        if ($records->isEmpty()) {
            return [AcademicYear::create([
                'school_id' => $school->id,
                'name' => $definition['name'],
                'start_date' => $definition['start_date'],
                'end_date' => $definition['end_date'],
                'status' => 'draft',
            ]), true];
        }

        $year = $this->singleRecord($records, 'academic year');
        $this->ensureCompatible($year, [
            'start_date' => $definition['start_date'],
            'end_date' => $definition['end_date'],
        ], 'academic year');

        return [$year, false];
    }

    /** @return array{0: AcademicClass, 1: bool} */
    private function resolveClass(School $school, array $definition): array
    {
        $records = AcademicClass::query()->where('school_id', $school->id)
            ->where(fn ($query) => $query->where('name', $definition['name'])->orWhere('code', $definition['code']))
            ->lockForUpdate()->get();
        if ($records->isEmpty()) {
            return [AcademicClass::create(['school_id' => $school->id] + $definition), true];
        }

        $class = $this->singleRecord($records, 'class');
        $this->ensureCompatible($class, $definition, 'class');

        return [$class, false];
    }

    /** @return array{0: Section, 1: bool} */
    private function resolveSection(School $school, AcademicClass $class, array $definition): array
    {
        $records = Section::query()->where(['school_id' => $school->id, 'class_id' => $class->id])
            ->where(fn ($query) => $query->where('name', $definition['name'])->orWhere('code', $definition['code']))
            ->lockForUpdate()->get();
        if ($records->isEmpty()) {
            return [Section::create([
                'school_id' => $school->id,
                'class_id' => $class->id,
                'name' => $definition['name'],
                'code' => $definition['code'],
                'capacity' => $definition['capacity'] ?? null,
                'status' => $definition['status'],
            ]), true];
        }

        $section = $this->singleRecord($records, 'section');
        $this->ensureCompatible($section, [
            'name' => $definition['name'],
            'code' => $definition['code'],
            'capacity' => $definition['capacity'] ?? null,
            'status' => $definition['status'],
        ], 'section');

        return [$section, false];
    }

    /** @return array{0: Subject, 1: bool} */
    private function resolveSubject(School $school, array $definition): array
    {
        $records = Subject::query()->where('school_id', $school->id)
            ->where(fn ($query) => $query->where('name', $definition['name'])->orWhere('code', $definition['code']))
            ->lockForUpdate()->get();
        if ($records->isEmpty()) {
            return [Subject::create(['school_id' => $school->id] + $definition), true];
        }

        $subject = $this->singleRecord($records, 'subject');
        $this->ensureCompatible($subject, $definition, 'subject');

        return [$subject, false];
    }

    /** @return array{0: AcademicGroup, 1: bool} */
    private function resolveGroup(School $school, array $definition): array
    {
        $records = AcademicGroup::query()->where('school_id', $school->id)
            ->where(fn ($query) => $query->where('name', $definition['name'])->orWhere('code', $definition['code']))
            ->lockForUpdate()->get();
        if ($records->isEmpty()) {
            return [AcademicGroup::create(['school_id' => $school->id] + $definition), true];
        }

        $group = $this->singleRecord($records, 'group');
        $this->ensureCompatible($group, $definition, 'group');

        return [$group, false];
    }

    private function ensureCompatible(object $record, array $expected, string $entity): void
    {
        foreach ($expected as $field => $value) {
            $actual = $record->getAttribute($field);
            if ($actual instanceof \DateTimeInterface) {
                $actual = $actual->format('Y-m-d');
            }
            if ((string) $actual !== (string) $value) {
                throw ValidationException::withMessages(['catalog' => "Existing {$entity} is incompatible on [{$field}]; no records were changed."]);
            }
        }
    }

    private function ensureUniqueDefinitions(object $validator, array $definitions, string $key, string ...$fields): void
    {
        foreach ($fields as $field) {
            $values = array_column($definitions, $field);
            if (count($values) !== count(array_unique($values))) {
                $validator->errors()->add($key, ucfirst($key).' '.$field.' values must be unique within the curated catalog.');
            }
        }
    }

    private function singleRecord(object $records, string $entity): object
    {
        if ($records->count() !== 1) {
            throw ValidationException::withMessages(['catalog' => "Existing {$entity} identity is ambiguous; no records were changed."]);
        }

        return $records->first();
    }

    /** @param array{created: int, resolved: int} $counter */
    private function count(array &$counter, bool $created): void
    {
        $counter[$created ? 'created' : 'resolved']++;
    }

    /** @return array{school: array{id: int, name: string}, academic_year: array{created: int, resolved: int, activated: bool}, classes: array{created: int, resolved: int}, sections: array{created: int, resolved: int}, subjects: array{created: int, resolved: int}, groups: array{created: int, resolved: int}} */
    private function emptySummary(School $school): array
    {
        return [
            'school' => ['id' => $school->id, 'name' => $school->name],
            'academic_year' => ['created' => 0, 'resolved' => 0, 'activated' => false],
            'classes' => ['created' => 0, 'resolved' => 0],
            'sections' => ['created' => 0, 'resolved' => 0],
            'subjects' => ['created' => 0, 'resolved' => 0],
            'groups' => ['created' => 0, 'resolved' => 0],
        ];
    }
}
