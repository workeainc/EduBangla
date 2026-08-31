# EduBangla Frontend Architecture Map

**Audit date:** 2026-08-31  
**Scope:** repository-read-only audit of the accepted Laravel 12 / Livewire 3 pilot.  
**Authority rule:** the UI is an adapter. Tenant middleware, Policies, Domain Actions and Queries remain authoritative.

## Status summary

| Status | Count | Meaning |
| --- | ---: | --- |
| UI EXISTS | 19 | A route and usable page/component exist, subject to normal UX polish. |
| UI PARTIAL | 18 | A page exists but has raw IDs, incomplete states, missing dependent selection, or incomplete workflow coverage. |
| UI MISSING | 8 | Backend capability exists but no browser route/page is exposed. |
| BACKEND ONLY | 3 | Authority exists with no intended current pilot UI (for example audit reads). |
| DEFERRED | 2 | Explicitly outside this pilot boundary. |
| BLOCKED | 1 | Parent identity boundary requires ADR approval. |

## Backend → UI capability matrix

| Domain | Backend authority | Existing route | Existing Livewire/Blade | Required UI | Actor | Read/Write | Status |
| --- | --- | --- | --- | --- | --- | --- | --- |
| Identity | `AuthenticatedSessionController`, `EstablishTenantContext`, `RequireSchoolRole` | `login`, `logout` | `auth/login.blade.php` | Sign-in, errors, session feedback | All operators | R/W | UI EXISTS |
| Workspace | `SchoolAccessController`, `SchoolUser`, `TenantContext` | `schools.index`, `schools.select`, `schools.dashboard` | `schools/index.blade.php`, `schools/dashboard.blade.php`, `components/layouts/app.blade.php` | School selection, active school, role-aware dashboard/shell | All operators | R/W | UI EXISTS |
| Academic year | `AcademicYear`, `ActivateAcademicYear` | No dedicated route | No dedicated page | List/create/activate/archive year | Admin | R/W | UI MISSING |
| Academic structure | `AcademicClass`, `Section`, `AcademicGroup`, `ClassGroup`, `CreateClassGroup` | `admin.class-groups` | `Admin\PhaseThreeManagement`, `phase-three-management.blade.php` | Dependency-safe class/section/group setup | Admin | R/W | UI PARTIAL |
| Subjects | `Subject`, `SubjectAssignment`, `CreateSubjectAssignment` | `admin.subject-assignments` | `Admin\PhaseThreeManagement` | Subject setup and academic-year/class-scoped assignment | Admin | R/W | UI PARTIAL |
| Teacher profiles | `Teacher`, `TeacherPolicy` | `admin.teachers*`, `admin.teacher.profile` | `Admin\PhaseThreeManagement`, `admin/profile.blade.php` | Profile, status, detail, assignments | Admin | R/W | UI EXISTS |
| Staff profiles | `Staff`, `StaffPolicy` | `admin.staff*`, `admin.staff.profile` | `Admin\PhaseThreeManagement`, `admin/profile.blade.php` | Profile/status management | Admin | R/W | UI EXISTS |
| Teacher assignments | `TeacherAssignment`, `CreateTeacherAssignment`, `TeacherAssignmentPolicy` | `admin.teacher-assignments`, `teacher.assignments` | `Admin\PhaseThreeManagement`, `Teacher\MyAssignments` | Cascading year/class/section/subject/teacher selectors | Admin / Teacher | R/W / R | UI PARTIAL |
| Students | `Student`, `StudentPolicy` | No dedicated route | No dedicated page | Student list/profile/search | Admin | R/W | UI MISSING |
| Guardians | `Guardian`, `AttachGuardian`, `GuardianPolicy` | No dedicated route | No dedicated page | Guardian relation in student workflow | Admin | R/W | UI MISSING |
| Enrollments | `Enrollment`, `CreateEnrollment`, `EnrollmentPolicy` | No dedicated route | No dedicated page | Annual placement and history | Admin | R/W | UI MISSING |
| Attendance | `CreateAttendanceSession`, `RecordAttendance`, `FinalizeAttendance`, `CorrectAttendance`, `AttendanceReport` | `teacher.attendance`, `admin.attendance`, `admin.attendance.corrections`, `admin.attendance.reports.*`, `admin.students.attendance` | `Attendance\Management`, `Admin\AttendanceCorrections`, report Blade views | Scoped session, status entry, finalize/correct, daily/monthly/class/student reports | Admin / Teacher | R/W | UI PARTIAL |
| Examination | `CreateExam`, `TransitionExam`, `CreateExamSchedule`, `CreateExamPaper`, paper/question Actions | `admin.exams*`, `admin.exams.schedules*`, `admin.exams.paper` | `Admin\ExamManagement`, `ExamScheduleManagement`, `ExamPaperManagement` | Lifecycle-aware exam setup and schedule/paper management | Admin | R/W | UI PARTIAL |
| Question bank | Question/version/option Actions and Policies | `admin.question-banks*`, `admin.questions*`, `admin.questions.versions*` | `QuestionBankManagement`, `QuestionVersions`, `QuestionVersionDetail` | Version history, ordered options, correct answer, immutable states | Admin | R/W | UI PARTIAL |
| Teacher marks | `EnterExamMark`, `CorrectExamMark`, `ExamMarkPolicy` | `teacher.exams`, `teacher.exams.marks`, admin correction routes | `Teacher\Exams`, `Teacher\ExamMarks`, `Admin\ExamMarkCorrections`, `ExamCorrectionHistory` | Assignment-scoped entry, reasoned corrections, history | Teacher / Admin | R/W | UI PARTIAL |
| Online attempts | `StartExamAttempt`, `SaveExamAnswer`, `SubmitExamAttempt`, `FinalizeExamAttempt` | `student.exams*`, `student.attempts.show` | `Student\Exams`, `Student\Attempt` | Server timer, autosave, expiry, submission state | Student | R/W | UI PARTIAL |
| Results | `ComputeExamResult`, `CalculateResultGrades`, `LockResult`, `PublishResult`, `ResultPolicy` | `admin.results`, `admin.exams.results`, `teacher.results`, `student.results` | `Admin\ResultManagement`, `Teacher\Results`, `Student\Results` | Compute→lock→publish lifecycle and scoped views | Admin / Teacher / Student | R/W / R | UI PARTIAL |
| Report cards | `GenerateReportCard`, `PublishReportCard`, `ReportCardPolicy` | `admin.report-cards*`, `teacher.report-cards`, `student.report-cards` | `Admin\ReportCards`, `ReportCardDetail`, `Teacher\ReportCards`, `Student\ReportCards` | Snapshot presentation, publish state, print/download affordance | Admin / Teacher / Student | R/W / R | UI PARTIAL |
| Promotion | `EvaluatePromotion`, `CreatePromotion`, `ApprovePromotion`, `ApplyPromotion`, `CancelPromotion` | `admin.promotions*`, `admin.promotion-rules*`, teacher/student promotion routes | `Admin\Promotions`, `PromotionRules`, `Teacher\Promotions`, `Student\Promotions` | Source enrollment→evaluation→approval→new placement | Admin / Teacher / Student | R/W / R | UI PARTIAL |
| Finance setup | Fee category/structure/assignment Actions | `admin.finance*` | `Admin\FinanceManagement` | Fee setup, activation, scoped assignment generation | Admin | R/W | UI PARTIAL |
| Finance transactions | `GenerateInvoice`, `RecordPayment`, `PostFinancialAdjustment`, reversal/void Actions | `admin.finance.invoices*`, payments/adjustments screens, `student.finance*` | `Admin\FinanceManagement`, `Student\Finance`, `FinanceDetail` | Server balance, immutable invoice evidence, payment/reversal flows | Admin / Student | R/W / R | UI PARTIAL |
| Notices | `SaveNoticeDraft`, `PublishNotice`, `WithdrawNotice`, `MarkNoticeDeliveryRead`, `RecipientResolver` | `admin.notices*`, teacher/student/staff notices | `Admin\Notices`, `Communication\Inbox` | Audience-safe authoring, publish/withdraw, inbox/read state | Admin / Teacher / Student / Staff | R/W / R/W | UI PARTIAL |
| Timetable | `SaveTimetableDraft`, `PublishTimetable`, `ArchiveTimetable`, `TimetableValidator` | `admin.timetables*`, `teacher.timetable`, `student.timetable` | `Admin\Timetables`, `Academic\TeacherTimetable`, `StudentTimetable` | Conflict-safe draft/publish/archive and label-based views | Admin / Teacher / Student | R/W / R | UI PARTIAL |
| Audit writes | `RecordAudit` invoked by critical Actions | No audit route | Audit records only | Preserve evidence and show success/history links where already available | System / Admin | W / limited R | BACKEND ONLY |
| Audit reads/governance | `AuditLog`, `docs/ADR/0014-audit-governance.md` | None | None | Read-only viewer only after governance approval | Approved support role | R | BACKEND ONLY |
| Parent portal | `ADR 0015 parent identity boundary` | None | None | No UI until identity/authorization decision | Parent | R | BLOCKED |
| Bulk import/export | Pilot “should have”, no current implementation | None | None | Explicit future capability | Admin | R/W | DEFERRED |
| Analytics / integrations | Roadmap future boundary | None | None | Explicit future capability | System | R | DEFERRED |

## Actor journeys and contracts

### Admin

`login` → `schools.index`/`schools.select` → `schools.dashboard` → academic setup (`admin.class-groups`, `admin.subject-assignments`, `admin.teacher-assignments`) → teacher/staff (`admin.teachers`, `admin.staff`) → **missing student/enrollment UI** → `admin.timetables` → `admin.attendance`/reports → `admin.exams`/schedules/paper/questions → `admin.results` → `admin.report-cards` → `admin.promotions` → `admin.finance` → `admin.notices`.

Every write is guarded by `school.admin` plus a Policy/Action check. The UI must show only records returned by `forSchool()` or equivalent tenant queries; IDs remain untrusted input. Lifecycle mutations are Action calls: save draft, finalize, publish, lock, approve, apply, reverse, or void. Empty, validation, conflict, stale, unauthorized and transaction-failure states are required at each boundary.

### Teacher

`teacher.assignments` → `teacher.timetable` → `teacher.attendance` → `teacher.exams` → `teacher.exams.marks` → `teacher.results`/`teacher.report-cards` → `teacher.notices`.

The actual mutation authority is assignment-scoped attendance and marks entry only; teacher routes do not grant finance, timetable publication, result publication, promotion application, or admin setup authority. `RequireSchoolRole('teacher')`, profile eligibility, assignment queries, and Domain Actions remain authoritative.

### Student

`schools.index`/`schools.select` → student dashboard → `student.timetable` → `student.exams`/`student.attempts.show` → `student.results` → `student.report-cards` → `student.finance`/invoice detail → `student.notices`.

Reads must resolve the authenticated student through `RequireStudent`/`FinanceAuthorizer`/domain policies and active enrollment relationships. Attempts are server-timed and owned by the authenticated student; balances and report snapshots are server values.

### Parent

`BLOCKED — PARENT IDENTITY ADR REQUIRED` (`docs/ADR/0015-parent-identity-boundary.md`). No route, navigation item, component, or browser test should be added.

## UI contract and state requirements

The contract for every Livewire mutation is: browser event → component method → tenant/profile re-query → Policy/middleware → Domain Action/Query → transaction and audit (where defined) → returned model/state → flash or field errors. Components must not calculate balances, grades, conflicts, audience membership, eligibility, or timing.

All pages require initial/action loading indicators, empty states, field and cross-field validation, stale/forged ID rejection, authorization errors, success feedback, transaction/server error feedback, and historical presentation for finalized/locked/published/archived/snapshot records. Destructive or irreversible actions require confirmation and consequence copy.

## Navigation and reusable primitives

The shared `components.layouts.app` currently provides responsive navigation, role-aware links, current school, switch-school, logout, active route styling, and flash output. Missing shell contract items are breadcrumbs, contextual page headers, mobile collapse/drawer behavior, notification count, and reusable confirmation/modal focus handling. `wire:navigate` is not currently used; adoption should be consistent across all Livewire links if introduced.

Required primitives (only where used): `Button`, `Input`, `Select`, `Textarea`, `DateInput`, `TimeInput`, `Checkbox`, `Alert`, `FlashMessage`, `Badge/StatusBadge`, `Card/StatCard`, `DataTable`, `EmptyState`, `LoadingState`, `ErrorState`, `Breadcrumbs`, `PageHeader`, `FilterBar`, `Tabs`, `ConfirmDialog`, `SnapshotCard`, and a responsive table-to-card pattern for dense mobile views.

## Responsive, accessibility and security rules

- Desktop/tablet/mobile are acceptance targets. Attendance, marks, finance, exam schedules and timetable use tables on desktop and stacked cards/list rows on narrow screens.
- Every control has a visible label/focus state, semantic button/link, keyboard path, associated error text, sufficient contrast, and meaningful status announcements. Dialogs must manage focus and Escape/submit behavior.
- UI visibility is not authorization. Never trust browser-supplied `school_id`, role, teacher/student/profile ID, enrollment ID, assignment ID, or lifecycle status. Keep tenant queries, Policies, middleware, and Actions unchanged.

## Frontend blockers

1. Parent portal is blocked by ADR 0015 and must not be designed.
2. Student/guardian/enrollment administration has no current route/component; it is a genuine workflow gap, not a styling task.
3. Audit viewer/read governance is intentionally unavailable pending ADR 0014 approval.

## Recommended implementation order

1. Harden shared shell/primitives and state conventions.
2. Add authorized academic-year/structure and student/enrollment administration screens.
3. Refine teacher workspace and assignment-safe selectors.
4. Refine attendance and reports.
5. Refine examination, online attempts and marks.
6. Refine result/report-card lifecycle, then promotion.
7. Refine finance, notices and timetable lifecycle views.
8. Add responsive/accessibility polish and complete the multi-role browser walkthrough.

## Backend gap register

All backend gaps discovered during frontend work are tracked in [docs/BACKEND_GAPS.md](BACKEND_GAPS.md). FE-002 recorded the academic catalog provisioning gap as `BG-001`; it remains open pending confirmation of whether a seed/runbook provisions prerequisite records or admins must create them in-browser.

## Audit conclusion

Backend capability coverage is mapped above; no backend schema, Action, Policy, middleware or authorization change is required for the frontend sequence. The next implementation task should consume `docs/FRONTEND_BACKLOG.md` in priority order.
