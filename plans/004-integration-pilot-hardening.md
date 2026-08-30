# Integration / Pilot Hardening Plan

**Status:** Proposed discovery plan only — no implementation started  
**Baseline:** `af201c6` on `codex/phase-5h-academic-timetable`  
**Scope rule:** This plan is read-only discovery output. It does not authorize Phase 5I or any new domain.

## 1. Current baseline

Phase 4 is frozen. Phase 5A–5E are accepted/frozen. Phase 5F Finance, Phase 5G Communication/Notice and Phase 5H Timetable are code-delivered. The current branch is clean and pushed; `origin/main` remains `191b64f`.

The application is a Laravel modular monolith using Livewire, shared-database row-scoped tenancy, school-local memberships, Actions/Services, Policies, MySQL and SQLite test support. Full SQLite/MySQL suites previously passed; authenticated browser verification remains environment-blocked.

## 2. Hardening objectives

1. Make every portal use one school-local membership authorization model.
2. Characterize the implemented pilot workflow without creating a generic workflow engine.
3. Prove disposable-MySQL backup/restore and post-restore integrity.
4. Decide audit read, mutation, retention and export governance before creating UI.
5. Make timetable screens usable without weakening server-side authority.
6. Reconcile stale architecture and roadmap statements.
7. Keep the Guardian identity boundary explicit and approved before parent access.

## 3. HARDEN-01 Authorization normalization

**Priority:** P0  
**Effort:** M  
**Risk:** MED — tightening school-local checks can expose legacy role assumptions.  
**Dependencies:** Identity, `TenantContext`, school memberships, existing policies and route tests.

**Current evidence:**

- `app/Http/Middleware/RequireTeacher.php:10-12` checks global Spatie role `Teacher` only.
- `app/Http/Middleware/RequireSchoolRole.php:8-19` checks active school-local membership.
- `app/Http/Middleware/RequireStudent.php:9-18` checks school-local student membership plus active student profile.
- `app/Http/Middleware/EstablishTenantContext.php:14-27` activates a school for any active member and clears it after the request.
- Teacher routes still using `middleware('teacher')`: `routes/web.php:85-89,136`; timetable/notices use `school.role:teacher` at `routes/web.php:116,118-119`.
- `AttendanceSessionPolicy.php:10-23` and `SchoolPolicy.php:9-17` use global role checks in places where school-local role is the required boundary.
- Livewire components generally reload IDs in tenant-scoped queries, but authorization conventions differ across components and Actions.

**Exact inconsistency:** teacher access is split between global role, school membership, profile existence and policy checks; admin/student paths are more consistently school-local. A global Teacher role is not equivalent to an active teacher membership in the requested school.

**Proposed change:** inventory every route → middleware → component → policy → Action → query path; choose active `school_users` membership as the role authority; standardize teacher/admin/student gates while preserving assignment/profile ownership checks. Do not centralize business rules in middleware.

**Required tests:** cross-school teacher membership; global Teacher role without active school membership; inactive membership; teacher profile without teacher membership; student profile/membership mismatch; admin membership mismatch; forged Livewire IDs; direct Action calls bypassing routes.

**Acceptance evidence:** route inventory shows one consistent chain; focused security tests pass on SQLite and MySQL; no accepted domain behavior changes outside authorization.

## 4. HARDEN-02 Pilot workflow characterization

**Priority:** P0  
**Effort:** L  
**Risk:** HIGH — integration tests can accidentally make one domain authoritative over another.  
**Dependencies:** all accepted domain Actions and existing feature tests.

| Transition | Authority / Action | Boundary and audit | Current state |
|---|---|---|---|
| Identity → School/Tenant | `TenantContext::activate`, school membership | Request-scoped context; no domain write | Implemented; manually selected per request |
| Academic setup → TeacherAssignment | `CreateTeacherAssignment` and academic Actions | Transactional write; assignment is scope authority | Implemented; manually administered |
| Academic → Enrollment | `CreateEnrollment` | Enrollment is annual placement authority; audited where action applies | Implemented; manually administered |
| Assignment/Enrollment → Attendance | `CreateAttendanceSession`, `RecordAttendance`, `FinalizeAttendance` | Session/rows transactional; finalization/correction audited | Implemented; manually orchestrated by teacher/admin |
| Examination setup → Marks | Examination Actions, `EnterExamMark` | Exam lifecycle and mark writes are transactional/audited | Implemented; manually orchestrated |
| Marks → Result | `ComputeExamResult`, grade calculation Actions | Result calculation transaction; result lifecycle audit | Implemented; manually triggered |
| Result → ReportCard | `GenerateReportCard`, publication Actions | Snapshot and publication transaction | Implemented; manually triggered |
| Published ReportCard → Promotion | `EvaluatePromotion`, `ApplyPromotion` | New enrollment transaction; source history retained | Implemented; manually triggered |
| Enrollment → Finance | `GenerateFeeAssignments`, `GenerateInvoice` | Explicit assignment/invoice transaction; no recurrence | Implemented; manually triggered |
| Communication | `SaveNoticeDraft`, `PublishNotice`, `WithdrawNotice` | Recipient materialization, snapshot and audit transaction | Implemented; manually authored; no automatic domain notifications |
| Assignment/scope → Timetable | `SaveTimetableDraft`, `PublishTimetable`, `ArchiveTimetable` | Slot snapshots and lifecycle audit transaction | Implemented; manually administered |

**Current gap:** there is no stable pilot-wide read model or characterized “school year” workflow spanning these transitions. Cross-domain consumption is mostly direct query composition in UI components rather than a documented narrow contract.

**Proposed change:** create characterization tests and a documented operator runbook for the sequence above. Add only narrow read/query contracts or post-commit events where an actual consumer requires them. Do not create a generic workflow engine, automatic notifications, timetable-driven attendance, or finance recurrence.

**Required tests:** happy path from setup through publication; invalid transition; rollback at each consequential transaction; historical snapshot survival; tenant substitution at each boundary; repeat/idempotency behavior where already supported.

**Acceptance evidence:** an operator can follow a deterministic pilot checklist; each transition names its authority, transaction, audit event and read contract; no cross-domain calculation authority moves.

## 5. HARDEN-03 Backup/restore rehearsal

**Priority:** P0  
**Effort:** M  
**Risk:** HIGH — restore commands are destructive if pointed at a real database.  
**Dependencies:** MySQL 8.x, disposable database/schema, isolated credentials supplied by environment.

**Current evidence:** `docs/PILOT_SCOPE.md:12` requires backups/restore; `docs/ARCHITECTURE.md:61-63` requires procedures before production. No repository runbook or rehearsal artifact was found.

**Proposed change:** document a minimum pilot-safe runbook covering logical backup, isolated restore, migration/schema verification, row-count/checksum spot checks, tenant isolation checks, audit/history checks and application smoke checks. Use disposable MySQL only; never include credentials or production-target commands.

**Required tests:** create representative data in two schools; backup; restore to a new disposable schema; verify tenant counts, foreign-key integrity, published snapshots, report-card/finance/notice history and audit rows; run focused and full tests against the restored schema.

**Acceptance evidence:** dated rehearsal log, commands redacted of secrets, verification results and explicit recovery limitations.

## 6. HARDEN-04 Audit governance

**Priority:** P0  
**Effort:** M  
**Risk:** MED — changing audit retention or mutability affects support and compliance evidence.  
**Dependencies:** `RecordAudit`, `AuditLog`, all consequential Actions, school membership policy.

**Current evidence:** `app/Domain/Audit/RecordAudit.php:9-13` writes actor, school, action, target and before/after JSON. `app/Models/AuditLog.php:7-18` has no explicit tenant scope, read policy, immutable model guard or retention behavior. `database/migrations/2026_08_29_100001_create_audit_logs_table.php:10-27` has target indexing but no governance metadata. No audit viewer was found.

**Proposed change:** first produce a decision covering who may read audits, whether school admins can see only their school, whether support access is separate, append-only enforcement, retention/export, sensitive-field minimization and whether current snapshots are sufficient. Defer UI until the decision is approved.

**Required tests:** tenant-scoped reads; non-admin denial; cross-school target denial; attempted update/delete behavior; audit transaction rollback; snapshot sufficiency for finance, notices, results, promotions and timetable.

**Acceptance evidence:** approved audit governance decision and test matrix; no audit UI unless separately authorized.

## 7. HARDEN-05 Timetable UX hardening

**Priority:** P1  
**Effort:** M  
**Risk:** LOW/MED — selector changes can create stale browser values, but Actions remain authoritative.

**Dependencies:** Phase 5H models/actions and tenant-scoped academic queries.

**Current evidence:** `resources/views/livewire/admin/timetables.blade.php:4-31` exposes academic, class, section, teacher-assignment and subject-assignment numeric IDs. `resources/views/livewire/academic/teacher-timetable.blade.php:1` and `student-timetable.blade.php:1` display only weekday and time. Phase 5H snapshots already contain teacher and subject display facts in published slots.

**Proposed change:** replace raw IDs with school-scoped labels/selectors; show subject, teacher, class/section and snapshot-backed context; add empty/error states. Preserve assignment authority, publication immutability, schema and server-side revalidation.

**Required tests:** selector values cannot cross schools; stale/forged IDs still fail; teacher/student see only their authorized rows; snapshot display survives source edits.

**Acceptance evidence:** browser walkthrough with an authenticated test user, focused Livewire security tests, and no timetable schema/authority change.

## 8. HARDEN-06 Documentation reconciliation

**Priority:** P1  
**Effort:** S  
**Risk:** LOW — documentation-only, but stale statements can misdirect future implementation.

**Dependencies:** accepted commit history and current code inventory.

**Current evidence and corrections required:**

- `docs/ARCHITECTURE.md:5`: remove the claim that Phase 5 is design-only and no examination code exists; describe delivered 5A–5H boundaries.
- `docs/MODULES.md:14`: update the statement that Result/GPA are excluded; distinguish delivered Result, Report Card and Promotion from still-deferred expansions.
- `docs/ROADMAP.md:29-30`: replace “Phase 5H implementation has not started” with the delivered commit and browser blocker.
- `plans/003-academic-timetable-foundation.md:19`: update the plan status from TODO only after implementation evidence is formally recorded.
- `README.md` still says architecture is frozen before application implementation; update only if the project owner wants a current-project description rather than a historical statement.
- `docs/ROLES_AND_PERMISSIONS.md` and older Phase 5 documents should be checked for “proposal/design-only” wording that contradicts delivered phases.

**Acceptance evidence:** repository-wide search finds no stale claims about delivered phases; documents explicitly preserve deferred boundaries.

## 9. HARDEN-07 Parent identity ADR requirement

**Priority:** P1 decision gate  
**Effort:** S for ADR; L for any later implementation  
**Risk:** HIGH — incorrect linking can expose children across schools.

**Dependencies:** central `User`, `Guardian`, student-guardian links, `school_users`, TenantContext and privacy policy.

**Current evidence:** `docs/PILOT_SCOPE.md:10` lists a parent portal; `docs/ADR/0013-communication-notice-boundary.md:34` states Guardian is not linked to authenticated User/membership; no parent route group exists in `routes/web.php`.

**Proposed change:** create an ADR only if approved, defining Guardian ↔ authenticated User ↔ school membership, consent/linking authority, multi-school behavior, revocation, child visibility and audit. Do not use Communication as a shortcut.

**Required tests:** design-level threat cases and, only after approval, cross-school child-linking, revocation and parent-read ownership tests.

**Acceptance evidence:** approved ADR before any parent route or delivery work.

Required invariant: **Parent portal remains blocked until Guardian ↔ authenticated User ↔ school membership identity is explicitly designed and approved.**

## 10. Security test matrix

| Area | Required evidence |
|---|---|
| Tenant context | inactive/non-member/foreign school denied; context cleared after request |
| Admin | active school-admin required in route, Livewire and direct Action |
| Teacher | global role alone denied; active school-local teacher membership and profile required; assignment ownership enforced |
| Student | active school-local student membership/profile/enrollment required; own-record reads only |
| Hydrated IDs | foreign timetable, invoice, notice, exam, attendance, report-card and promotion IDs fail without mutation |
| Policies | policy result agrees with route middleware and Action authorization |
| Audit | tenant-scoped read decision, attempted mutation behavior and rollback coverage |
| Deferred boundaries | no new finance recurrence, providers, parent delivery, timetable expansion or API surface |

## 11. Recovery test matrix

- Two-school disposable MySQL fixture.
- Backup completes and artifact is integrity-checked.
- Restore into a new disposable schema only.
- Migrations/schema/FK/index verification passes.
- Per-school row counts and representative tenant queries match source.
- Published report-card, finance, notice and timetable snapshots survive.
- Audit rows, actor references and before/after payloads survive.
- Application smoke route and full test suite pass against restored schema.
- Document what is not covered: binary files, external provider delivery and production-scale RPO/RTO until separately designed.

## 12. Browser verification plan

Use an authenticated browser session only after environment access is available. Verify admin setup/draft/publish/archive, teacher own timetable, student enrollment-filtered timetable, cross-school denial, stale-ID denial and visible snapshot fields. Record environment blockers verbatim; do not convert them to code acceptance.

## 13. SQLite/MySQL verification plan

For each hardening change:

1. `php artisan config:clear`
2. Fresh SQLite migration and focused security/workflow tests.
3. Full SQLite suite.
4. Fresh disposable MySQL migration and focused tests.
5. Full MySQL suite.
6. `vendor/bin/pint --test`, `php artisan view:cache`, route inventory, `composer audit --no-interaction`, `git diff --check`.

Expected result: zero test failures, no audit advisories, clean diff, and no modifications outside approved hardening scope.

## 14. Git/delivery gates

- Start from `af201c6`; verify clean tree and branch identity.
- No Phase 4–5H behavior, migrations or domain feature changes without explicit scope approval.
- Every hardening commit names its evidence and remains reviewable by category.
- Do not push or call the work accepted until SQLite/MySQL, recovery and authorized browser gates are recorded.
- Final branch must have clean working tree and explicit comparison with `origin/main`.

## 15. Stop conditions

Stop and report instead of improvising if:

- authorization semantics cannot be unified without changing an accepted domain contract;
- recovery requires production credentials or a non-disposable target;
- audit requirements imply regulated retention or export not covered by an approved decision;
- parent linking cannot be proven school-local;
- a proposed fix needs recurring finance, external providers, queues, parent delivery, timetable rooms/substitutions/holidays, automatic attendance/exam integration, APIs, mobile, analytics or HR/payroll.

## 16. Recommended execution order

1. HARDEN-01 authorization normalization and characterization tests.
2. HARDEN-02 pilot workflow map and integration characterization tests.
3. HARDEN-03 disposable MySQL backup/restore rehearsal.
4. HARDEN-04 audit governance decision.
5. HARDEN-06 documentation reconciliation.
6. HARDEN-05 timetable UX hardening.
7. HARDEN-07 parent identity ADR decision gate.
8. Authenticated browser verification and final delivery review.

## 17. Explicitly deferred work

Recurring finance, `BillingPeriod`, installments, gateways, refunds, tax, ledger, SMS/email/push providers, queues, parent delivery, automatic finance/result/promotion notifications, timetable rooms, substitutions, holidays/calendar exceptions, optimizer, import/export, automatic attendance or exam integration, API surface, mobile, analytics and HR/payroll remain deferred.

## 18. Final acceptance criteria

- Authorization chain is consistent and evidenced across routes, TenantContext, membership, profile, policy, Livewire and Actions.
- Pilot workflow has a reviewed authority/transaction/audit/read-contract map and characterization tests.
- Disposable MySQL backup/restore rehearsal is reproducible and verifies tenant/history integrity.
- Audit governance decision is approved before any audit UI.
- Timetable UX is label-driven and snapshot-aware without schema/authority changes.
- Stale documentation is reconciled without removing deferred boundaries.
- Parent identity ADR is approved before parent access; otherwise parent remains blocked.
- SQLite, MySQL, Pint, cache, route, Composer and Git gates pass.
- Authenticated browser verification is either passed or recorded as environment-blocked.
- Phase 5I remains not started.
