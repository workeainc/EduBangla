# EduBangla Backend Gap Register

This is the canonical register for backend capability gaps discovered while implementing or auditing frontend workflows. Future frontend work must append or update this file whenever the UI reveals a missing, inadequate, or ambiguous backend contract.

## Classification

- **REQUIRED / PILOT-CRITICAL:** the operator cannot complete an accepted pilot journey without a bounded backend change. Stop the dependent frontend slice and prepare a scoped fix proposal.
- **REVIEW:** the backend exists, but the contract is insufficient or ambiguous for a safe UI. Document evidence and review before changing anything.
- **DEFERRED:** intentionally outside the current pilot. Do not implement as part of frontend work.
- **RESOLVED:** a bounded fix was implemented and verified; retain the evidence and date.

## Gap register

| ID | Classification | First found | Area | Evidence in repository | Pilot impact | Current frontend behavior | Bounded-fix decision | Status |
| --- | --- | --- | --- | --- | --- | --- | --- |
| BG-001 | RESOLVED | FE-002 (2026-08-31) | Academic catalog provisioning | The catalog models/schema existed but had no approved creation authority; factories were test-only and `DatabaseSeeder` only seeds roles. | A fresh pilot school could not safely receive required year/class/section/subject data without browser CRUD or ad-hoc inserts. | Prerequisite selectors remain read-only; no frontend authority was added. | Added `ProvisionPilotAcademicCatalog` and `edubangla:provision-pilot-catalog --school-id=<ID>`. The command resolves exactly one existing school, uses reviewed `config/edubangla-pilot-catalog.php` input, and delegates to a single transaction that locks the school, resolves-or-creates only compatible school-local catalog rows, fails closed on conflicts, and reuses `ActivateAcademicYear`. It never uses TenantContext/session state, iterates schools, changes `DatabaseSeeder`, or creates assignments/people/enrollments. Focused SQLite regressions cover target isolation, activation, optional groups, idempotent rerun, incompatible-conflict rollback, and command targeting. See `docs/PILOT_ACADEMIC_CATALOG_PROVISIONING.md`. | RESOLVED — 2026-09-01; bounded pilot catalog provisioning is available without academic CRUD |
| BG-002 | REQUIRED / PILOT-CRITICAL | FE-003 (2026-08-31) | Admin student and enrollment workflow | `Student`, `Guardian`, `Enrollment`, `CreateEnrollment`, and `AttachGuardian` existed, but no admin student/enrollment route, component, or Student creation Action existed. | An administrator could not begin the Student → Guardian → Enrollment journey from the browser. | Added bounded admin-only workspace; all labels and selectors are tenant-scoped and no login identity is created. | Added `CreateStudent`, `CreateGuardian`, `StudentEnrollment`, and `admin.students.enrollment`. The workspace authorizes Student/Guardian/Enrollment creation through existing policies, resolves existing guardians with `Guardian::forSchool()`, reuses `AttachGuardian` and `CreateEnrollment`, and wraps the combined flow in a DB transaction. Focused regression tests prove happy path, existing guardian reuse, foreign IDs, policy denial, identity boundary, duplicate enrollment rollback, and no orphaned records. No migration or authorization semantic change. | RESOLVED — 2026-09-01; FE-003 unblocked for frontend completion |
| BG-003 | RESOLVED | FE-005 (2026-08-31) | Editing an existing attendance session | `student_attendance` already has the canonical unique constraint `attendance_session_id + student_id` (`student_attendance_unique`). `RecordAttendance` previously inserted rows only and `Attendance\Management::save()` skipped recording once rows existed. | Draft/open sessions now safely persist status changes without duplicate rows; finalized sessions remain immutable. | `RecordAttendance` now uses the existing canonical key to update or create each validated row inside its existing transaction; `Management::save()` delegates every non-empty save to that Action. | No migration or new Action required. SQLite regression covers create → update, no duplicates, independent multi-student updates, rollback on invalid batch, finalization, and existing tenant/authorization tests. MySQL run remains environment-blocked (`SQLSTATE[HY000] [2002] Operation not permitted`), matching prior documented local limitation. | RESOLVED — 2026-08-31; FE-005 unblocked |
| BG-004 | RESOLVED | FE-012 (2026-08-31) | Attendance report filter tenant validation | `AttendanceReportController::daily()` and `::class()` previously read `academic_year_id`, `class_id`, and `section_id` directly from request input and applied them only as scalar predicates. `::monthly()` previously accepted an unvalidated `month`; the class aggregate also used ambiguous unqualified `status` expressions after joining two status-bearing tables. | Forged/foreign/stale filter IDs could be silently accepted, and the class report was not portable across SQLite/MySQL. | Added a narrow controller filter validator using school-scoped `Rule::exists` checks, section→class relationship validation, and `Y-m` month validation while preserving existing URL names and report semantics. Qualified aggregate status expressions as `student_attendance.status`. | Security invariant: every optional filter ID must belong to the active school; section must belong to the selected class; no report query returns another school’s rows. Regression coverage verifies valid/omitted filters, foreign/stale IDs, mismatched section/class, month format, authorization, and existing attendance/report behavior. | RESOLVED — 2026-08-31; FE-012 unblocked |

## Intentionally not gaps

- Parent portal remains blocked by `docs/ADR/0015-parent-identity-boundary.md`; it is not a backend fix target until the identity decision is approved.
- Audit read UI remains governed by `docs/ADR/0014-audit-governance.md`; audit writes already exist and are not a missing authority.
- Bulk import/export, analytics, integrations, and other roadmap items remain deferred, not gaps.

## Operating procedure for future frontend work

For every frontend task, inspect the complete chain:

`UI requirement → route → Livewire/controller → middleware/Policy → Action/Query → model/database → transaction/audit → UI feedback`.

If a required pilot capability is missing, record it here before implementing a workaround. Include the exact file/symbol evidence, affected actor journey, whether the gap is pilot-critical, and the smallest acceptable bounded fix. Do not silently add business logic to Blade/Livewire, weaken authorization, add a migration, or introduce an API to hide a gap.

When a gap is fixed, update its row with the implementing files, tests, migration/API impact (normally “none”), verification date, and set `RESOLVED`. Never delete historical gap entries.

## Review gate

The next formal review is before FE-003 (Student & Enrollment). Reconfirm BG-001 and audit the Student → Guardian → Enrollment chain. A dependent frontend slice may proceed with read-only prerequisites only when the provisioning decision is explicit.
