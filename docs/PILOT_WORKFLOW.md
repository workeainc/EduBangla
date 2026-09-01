# Pilot Operational Workflow

This document characterizes the implemented pilot workflow. It is an operator
map, not a workflow engine, scheduler, notification trigger or new cross-domain
authority. Each domain retains ownership of its own lifecycle and calculations.

| Transition | Input authority | Output authority | Action / persistence boundary | Audit / read contract | Operator orchestration |
| --- | --- | --- | --- | --- | --- |
| User → school context | Active `school_users` membership | Request-scoped `TenantContext` | `EstablishTenantContext` activates then clears context | Membership and `SchoolPolicy`; no domain audit write | Sign in and select an authorized school route |
| Academic setup → teacher assignment | School academic structure and active teacher profile | `TeacherAssignment` | Academic/Teacher Actions validate school, year, class, section, subject and group relationship | Assignment-scoped teacher queries | School admin creates and reviews assignments |
| Student → annual placement | Persistent Student and school academic structure | active `Enrollment` with historical placement rows | `CreateEnrollment` validates school-local relationship | Enrollment/Student policies; downstream domains consume enrollment IDs | School admin records annual placement |
| Assignment + enrollment → attendance | `TeacherAssignment` and active matching enrollments | draft/finalized `AttendanceSession` and `StudentAttendance` rows | Create/record/finalize actions use transactions; finalized sessions are read-only except audited correction | `attendance.created` and correction events; reports read attendance rows | Teacher/admin records and finalizes attendance |
| Exam setup → marks | Exam, schedule and teacher assignment | `ExamMark` evidence | Examination Actions enforce exam lifecycle and assignment scope | Exam/mark policies and audit events | Admin schedules; assigned teacher enters marks |
| Marks → result | Locked/manual mark evidence for an exam | `Result` and `ResultItem` | `ComputeExamResult`, grade, lock and publish Actions own calculation and lifecycle | Result lifecycle records; student reads published own record | Admin deliberately computes, locks and publishes |
| Result → report card | Published/locked result and grade snapshot | immutable `ReportCard` snapshot | Generate/publish Actions | Report-card policy; student reads own published card | Admin generates and publishes |
| Report card → promotion | Published source `ReportCard` and active `PromotionRule` | `Promotion` and a new target `Enrollment` | Evaluate/approve/apply Actions; apply is transactional | `promotion.applied`; source evidence is retained | Admin evaluates, approves and applies |
| Enrollment → finance | Active `Enrollment`, active fee structure and explicit assignments | fee assignments, invoice, payment/allocation/adjustment history | Finance Actions own invoice/due calculation in transactions | Finance audit events; student reads own invoice/due | Admin explicitly assigns, invoices, records payment or adjustment |
| Admin-authored notice → recipient inbox | Active tenant membership/profile/enrollment audience facts | immutable published `Notice` and materialized deliveries | Notice publish transaction resolves audience, creates deliveries and audit | Recipient-owned delivery query/read state | Admin authors and publishes; no automatic domain notification |
| Assignment → timetable | Existing teacher and subject assignments | draft/published/archived timetable and slot snapshots | Timetable Actions validate scope and conflicts; publication is transactional | Teacher/student scoped timetable queries; publication audit | Admin drafts, publishes or archives |

## Stable contracts and non-contracts

- `TeacherAssignment` is the academic operational scope authority for attendance,
  examination and timetable; a global user role does not substitute for it.
- `Enrollment` is the annual placement authority for attendance, result,
  promotion, finance and student timetable visibility.
- Result calculation never moves into examination UI; promotion consumes only
  published report-card evidence; finance does not infer recurring charges.
- Notice publication materializes recipients but does not become automatic
  attendance, result, promotion or finance notification.
- No generic process runner, synchronous cross-domain write chain, queue, API,
  timetable-driven attendance or examination integration is introduced.

## Characterization evidence

The feature suites cover each consequential transaction, tenant substitution,
immutable snapshot and lifecycle separately. Cross-domain pilot acceptance must
run the ordered workflow above with a real authorized school user after the
environment supports authenticated browser verification.

## PILOT-001-P0-001 resolution

The 2026-09-01 rich rehearsal exposed a promotion-apply failure: the target
enrollment schema requires a non-null roll, while `ApplyPromotion` previously
created that enrollment without one. `ApplyPromotion` now allocates the next
server-side roll within the exact school/year/class/section/group-scope and
delegates creation to `CreateEnrollment`, preserving its existing validation and
transaction boundary. Regression coverage verifies successful apply, scope-safe
non-colliding allocation, rollback, repeated-apply rejection, and the existing
promotion/enrollment suites. Status: **RESOLVED**; PILOT-001 is ready to resume
from the blocked promotion step.
