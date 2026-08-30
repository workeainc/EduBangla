# Plan 003: Establish a tenant-safe academic timetable foundation

> **Executor instructions**: Follow every step and verification gate. Stop and
> report if a STOP condition applies; do not invent a scheduling rule. Update
> the Plan 003 row only after every closure gate has actual evidence.
>
> **Drift check (run first)**: `git diff --stat cf01e26..HEAD -- app database/migrations routes resources/views tests docs plans`
> Compare any in-scope drift to the current-state facts below. A material
> mismatch requires a refreshed plan before implementation.

## Status

- **Priority**: P1
- **Effort**: L
- **Risk**: HIGH — scope errors can expose another school's routine or create conflicting teaching obligations.
- **Depends on**: completed Plans 001–002; accepted Academic, Teacher, tenant, audit and portal foundations.
- **Category**: direction | architecture | migration | security | tests | docs
- **Planned at**: commit `cf01e26`, 2026-08-30
- **Status**: Implemented at `af201c6`; authenticated browser verification blocked.

## Why this matters

Timetable is an explicit pilot “should have” (`docs/PILOT_SCOPE.md:14-16`) and
is a named Academic responsibility (`docs/MODULES.md:7`). It is the next
independent operational foundation: it makes existing teacher assignments and
student enrollment placement usable as a weekly routine without coupling money,
results, promotions, communication delivery, or external infrastructure.

## Current repository state

- `AcademicYear`, `AcademicClass`, `Section`, `AcademicGroup` and `Subject`
  are school-owned (`database/migrations/2026_08_28_000002_create_academic_structure_tables.php:11-54`).
- `SubjectAssignment` is scoped to school/year/class/optional group, with a
  school-local unique constraint (`database/migrations/2026_08_28_100002_create_subject_assignments_table.php:14-24`).
- `TeacherAssignment` binds teacher/year/class/section/subject assignment and
  optional group; its historical rows are retained (`database/migrations/2026_08_28_100003_create_teacher_assignments_table.php:14-25`).
- `CreateTeacherAssignment` validates the entire academic relationship inside
  a transaction (`app/Domain/Teacher/Actions/CreateTeacherAssignment.php:18-49`).
- Tenant context comes from route School plus active `school_users` membership
  (`app/Http/Middleware/EstablishTenantContext.php:13-28`); models should use
  `BelongsToSchool::forSchool()` (`app/Models/Concerns/BelongsToSchool.php:9-19`).
- `RecordAudit` writes safe metadata in the transaction (`app/Domain/Audit/RecordAudit.php:9-14`).
- Existing teacher portal convention resolves the authenticated teacher under
  active school, then loads only own assignments (`app/Livewire/Teacher/MyAssignments.php:12-19`).

## Domain boundary

Phase 5H owns recurring weekly instructional slots only. A slot expresses one
authorized `TeacherAssignment` at a day-of-week and a start/end time for its
year/class/section/group scope. TeacherAssignment remains the authority for
who may teach the scope; timetable never recreates academic, subject, teacher
or enrollment identity.

**Out of scope:** rooms, room capacity, substitutions, holiday/calendar
exceptions, automatic attendance sessions, exam schedules, clash overrides,
optimizer algorithms, bell scheduling, import/export, printable exports,
notifications, SMS/email/push, queues and API endpoints. Do not modify Phase
4–5G code or behavior.

## Proposed entities and schema principles

| Entity/table | Purpose | Required integrity |
| --- | --- | --- |
| `Timetable` / `timetables` | Tenant/year/class/section header with `draft`, `published`, `archived` status and published timestamps. | Non-null `school_id`; unique school/year/class/section/name; historical rows restrictive on delete. |
| `TimetableSlot` / `timetable_slots` | Recurring weekday start/end entry referencing its timetable, teacher assignment, subject assignment, teacher, year/class/section/group snapshots. | Non-null `school_id`; weekday/time check; end after start; source relationship must match header and tenant. |

Use indexed `(school_id, academic_year_id, class_id, section_id, status)` on
headers and `(school_id, timetable_id, weekday, starts_at)` on slots. Enforce
server-side conflicts before publication: no overlapping slots for the same
published class/section/group, teacher, or same class-group scope. Database
unique constraints may protect exact duplicate start times but cannot replace
overlap checks. Snapshot teacher/subject/class/section/group display facts at
publication; later assignment/profile edits cannot rewrite a published routine.

## Authorization and ownership model

| Actor | Access | Denied |
| --- | --- | --- |
| Active school admin | Create/edit drafts; publish/archive; view all own-school schedules | foreign school, invalid lifecycle, edits to published facts |
| Assigned teacher | Read only slots where authenticated teacher matches source assignment | all timetable mutations and other teachers' scopes |
| Active student | Read only published slots matching own active enrollment/year/class/section/group | drafts, peers/other class scopes, mutation |
| Staff/parent/guest/inactive/non-member | none in this foundation | all timetable routes and hydrated IDs |

Every policy, Action and Livewire method must reload the route/hydrated models
with the active `school_id`; a browser school, teacher, student, group or
assignment ID is not authoritative.

## Action and lifecycle architecture

- `SaveTimetableDraft`: school-admin authorization; validate every scoped
  academic/teacher assignment; create/update draft header and slots in one
  transaction; audit create/update.
- `PublishTimetable`: lock and reload draft header/slots; validate all source
  relationships and overlaps; stamp immutable snapshots; publish atomically;
  audit publication; support a test-only failure callback after the first slot.
- `ArchiveTimetable`: only published timetable; preserve slots/snapshots;
  audit archival atomically.
- `TeacherTimetableQuery` and `StudentTimetableQuery`: read-only scoped query
  services. They must not infer authorization from route parameters.

Lifecycle is `draft → published → archived`. Published slot/source-display
facts are immutable; correction requires archive plus a superseding draft/new
timetable. No deletion of published timetables or slots.

## Livewire and route surface

- Admin: `/schools/{school}/admin/timetables`, create, draft detail/edit and
  published detail. Thin adapter only; scalar validation and Actions.
- Teacher: `/schools/{school}/teacher/timetable`, own current published slots.
- Student: `/schools/{school}/student/timetable`, own active-enrollment
  published slots.

Use existing `auth`, `tenant.context`, `school.admin`, `teacher`, and `student`
middleware patterns; add no parent route. Each component must authorize at
mount and re-query all IDs under the tenant before invoking an Action.

## Implementation steps

### Step 1: Record the accepted timetable boundary

Create Phase 5H scope/database/security documentation and an ADR only if a
decision beyond this plan is needed (for example an approved exception model).
Document all exclusions, the lifecycle, immutable publication snapshots and
that Attendance/Examination schedules are independent.

**Verify**: `rg -n "draft|published|archived|Attendance|Exam|school_id|snapshot" docs/PHASE_5H_*.md` finds every declared boundary.

### Step 2: Add tenant-owned schema, models and factories

Add the two tables/models above with `BelongsToSchool`, relationships/casts,
model guards for published/archived records, restrictive foreign keys and the
specified indexes. Do not alter existing academic or teacher tables.

**Verify**: `php artisan migrate:fresh --force && php artisan test --filter=PhaseFiveH` exits 0.

### Step 3: Implement Actions and authoritative conflict validation

Resolve every year/class/section/group/teacher/subject-assignment/teacher-
assignment under the authorized school. Require the slot source to agree with
the timetable header. Validate time ranges and conflicts transactionally with
locks where required. Publish snapshots and audits in the same transaction.

**Verify**: focused tests prove foreign/mismatched references, overlap, empty
draft, invalid time range and injected failure leave no timetable state/audit.

### Step 4: Add policies, routes and thin portal adapters

Implement timetable policy/read scopes and the planned admin/teacher/student
Livewire surfaces. Teacher and student lists must use server-resolved profiles
and active enrollment; no direct selector may choose another person/scope.

**Verify**: `php artisan route:list --name=timetable` returns only planned routes; scope tests pass for foreign URLs and hydrated IDs.

### Step 5: Complete evidence and documentation

Run SQLite and disposable-MySQL migration/full suite, Pint, Blade cache, route
inventory, Composer audit where network permits, `git diff --check`, and
authenticated browser workflows. Record—not conceal—any environment block.

## Test matrix

Create `tests/Feature/PhaseFiveHTest.php` and `PhaseFiveHScopeTest.php`,
following PhaseFiveG transaction/scope structure and TeacherAssignment fixture
relationships.

- lifecycle: draft create/update, publish, archive, invalid transitions and
  post-publication mutation rejection;
- scope: every foreign/mismatched academic and teacher assignment ID, stale
  Livewire ID and foreign route rejects without rows or false audit;
- conflict: invalid/overlapping same scope, teacher and group time windows;
- ownership: admin own-school; teacher own slots only; student own active
  enrollment only; staff/parent/guest/inactive/foreign denied;
- integrity: snapshots survive later teacher/subject/assignment/enrollment
  changes; archive retains slots; duplicate exact slots rejected;
- rollback: forced failure after first published snapshot rolls back header,
  slots and audit atomically;
- SQLite and full disposable-MySQL suite; authenticated browser paths for
  admin draft/publish/archive and teacher/student views.

## Verification commands

| Purpose | Command |
| --- | --- |
| Focused | `php artisan test --filter=PhaseFiveH` |
| SQLite full | `php artisan test` |
| Style | `vendor/bin/pint --test` |
| Views | `php artisan view:cache` |
| Routes | `php artisan route:list --name=timetable` |
| SQLite migration | `php artisan migrate:fresh --force` |
| MySQL migration | `DB_CONNECTION=mysql DB_DATABASE=edubangla_test php artisan migrate:fresh --force` |
| MySQL full | `DB_CONNECTION=mysql DB_DATABASE=edubangla_test php artisan test` |
| Diff | `git diff --check` |

## Git workflow and STOP conditions

Use branch `codex/phase-5h-academic-timetable`, focused conventional commits,
and do not push without operator permission. Stop if timetable requires a
Phase 4–5G change, an exception/calendar/room/substitution model, automatic
attendance/exam integration, a provider/API/queue, parent access, or a
SQLite/MySQL integrity disagreement. Do not improvise a resolution.

## Done criteria

- [ ] Only Phase 5H timetable files and explicitly required documentation change.
- [ ] Every record/query/policy/Action is tenant-scoped and role-owned.
- [ ] Published timetable and slot snapshots are immutable; archive retains history.
- [ ] Publication conflict validation, audit and rollback are transactional.
- [ ] SQLite and disposable MySQL migrations/full suites, Pint, view cache,
  route inventory and diff check pass; Composer/browser evidence is recorded honestly.
- [ ] Plan index and documentation record the actual closure state.
