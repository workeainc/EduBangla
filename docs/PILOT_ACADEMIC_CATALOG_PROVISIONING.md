# Pilot Academic Catalog Provisioning

## Purpose and scope

`edubangla:provision-pilot-catalog` provisions the prerequisite academic catalog for **one explicitly selected existing school**. It is a controlled operational tool for the pilot; it is not academic catalog CRUD, a browser workflow, import system, onboarding wizard, or default demo seed.

It provisions only:

- one academic year;
- classes;
- sections belonging to those classes;
- subjects; and
- groups only when the curated catalog includes them.

It never creates class-group links, subject assignments, teacher assignments, people, memberships, enrollments, finance, examinations, timetables, or login identities.

## Curated input

The reviewed source is [`config/edubangla-pilot-catalog.php`](../config/edubangla-pilot-catalog.php). Update that file through the normal deployment review process with the pilot school's approved year, class, section, subject, and optional group data before provisioning.

The input is deliberately not supplied by a browser, request, API, CSV, or spreadsheet. Its schema is:

```php
[
    'academic_year' => [
        'name' => '2026',
        'start_date' => '2026-01-01',
        'end_date' => '2026-12-31',
        'activate' => true,
    ],
    'classes' => [
        ['name' => 'Class 6', 'code' => 'C06', 'sort_order' => 6, 'status' => 'active'],
    ],
    'sections' => [
        ['class_code' => 'C06', 'name' => 'Section A', 'code' => 'A', 'capacity' => 40, 'status' => 'active'],
    ],
    'subjects' => [
        ['name' => 'Mathematics', 'code' => 'MATH', 'short_name' => 'Math', 'status' => 'active'],
    ],
    'groups' => [],
]
```

Every section must reference a class code in the same curated catalog. The command validates required fields and uniqueness before it mutates data.

## Safe operator procedure

1. Identify the target school from a controlled server-side query or administration record. Confirm its numeric ID and name with the pilot operator.
2. Review `config/edubangla-pilot-catalog.php`; do not use factory data or edit `DatabaseSeeder`.
3. Run the command for that exact school. It prompts with the resolved ID and name:

   ```bash
   php artisan edubangla:provision-pilot-catalog --school-id=42
   ```

4. For an approved non-interactive deployment or rehearsal only, use `--force`:

   ```bash
   php artisan edubangla:provision-pilot-catalog --school-id=42 --force
   ```

5. Record the printed created/resolved summary. Confirm the intended academic year is active when `activate` is `true`.
6. Continue with the separate operator workflows for teachers, assignments, students, guardians, and enrollments. This command does not create them.

The command refuses a missing, zero, or invalid school ID and never iterates over schools. It does not use `TenantContext`, browser sessions, or `active_school_id`.

## Idempotency and conflicts

The action resolves exact existing records using the catalog tables' school-local natural keys:

- academic year name;
- class name or code;
- section name or code within its class;
- subject name or code; and
- group name or code.

An exact compatible rerun is safe: it creates no duplicate records and reports resolved counts. Existing records are never overwritten.

If an existing record matches a natural key but differs in requested immutable catalog fields (for example year dates, class code/name/order/status, section capacity/status, or subject short name/status), provisioning fails closed. Ambiguous identities also fail closed. Correct the reviewed curated catalog or resolve the existing data through an approved future process; do not manually force the command through a conflict.

## Transaction and activation behavior

All validation, record resolution/creation, and activation execute in one database transaction after the exact target school is locked. A conflict or failure rolls back records created by that attempt while preserving pre-existing records.

When `academic_year.activate` is true, the Action delegates to the existing `ActivateAcademicYear` Action. That lifecycle authority closes any other active year for the same school and activates the curated year; no parallel activation logic exists in the provisioner.

## Verification rehearsal

Use a disposable school/database first:

```bash
php artisan test tests/Feature/PilotAcademicCatalogProvisioningTest.php
php artisan test
php artisan optimize:clear
php artisan view:cache
git diff --check
```

Where the environment permits, repeat the focused provisioning test with the repository's MySQL test configuration before provisioning a real pilot school. Run the command twice for the disposable school and confirm the second output reports resolved rather than created records.
