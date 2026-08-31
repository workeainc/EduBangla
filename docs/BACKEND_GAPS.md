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
| BG-001 | REQUIRED / PILOT-CRITICAL if no seed/setup process exists; otherwise REVIEW | FE-002 (2026-08-31) | Academic catalog creation | `AcademicYear`, `AcademicClass`, `Section`, `AcademicGroup`, and `Subject` models/migrations exist, but no create Actions or dedicated admin routes/components were found. Existing `PhaseThreeManagement` only creates `ClassGroup`; `ActivateAcademicYear` is the only catalog lifecycle Action. | A new school administrator may be unable to create the prerequisite year/class/section/group/subject records needed before assignments, unless these are provisioned by a seed/import/operator process. | FE-002 presents these records as tenant-scoped prerequisite selectors and does not invent CRUD or new authority. | Before FE-003/pilot acceptance, confirm the intended provisioning path. If admins must create them in-browser, propose bounded create/update Actions, policies, Livewire surfaces, and tests for each catalog; no schema change is presumed. If a provisioning runbook/seed is authoritative, document it here and keep UI read-only. | OPEN — decision required before full pilot walkthrough |
| BG-002 | REQUIRED / PILOT-CRITICAL | FE-003 (2026-08-31) | Admin student and enrollment workflow | `Student`, `Guardian`, `Enrollment`, `CreateEnrollment`, and `AttachGuardian` exist, but `routes/web.php` exposes no admin student/enrollment route and `app/Livewire/Admin` contains no student/enrollment component. No Student creation Action exists; only enrollment and guardian attachment Actions are available. | An administrator cannot find/create/manage students or begin the required Student → Guardian → Enrollment journey from the browser. FE-003 cannot be completed without inventing a route/component contract and a Student mutation authority. | No application code was changed. FE-003 was stopped at the contract audit. | Prepare a bounded backend proposal before FE-003 resumes: decide whether student creation is an existing provisioning responsibility or needs an explicit Action/Policy-backed admin surface; then expose only the minimum route/component and test contract. Reuse `CreateEnrollment` and `AttachGuardian`; do not add parent authentication or alter enrollment semantics. | OPEN — FE-003 blocked pending contract decision |
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
