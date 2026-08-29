# Phase 5 Security and Audit Design

## Tenant and scope

Every request activates `TenantContext`; all resources resolve through school-scoped queries and policies. Teachers may manage only schedules and marks matching their active school, academic year, teacher assignment, subject, class, section and group scope. Students may access only their own eligible schedule and attempt; parents may access only their linked child. School Admins are limited to their own school. Super Admin has no implicit school bypass.

## Lifecycle authorization

School Admin creates and schedules exams, controls lock/publish transitions and manages corrections. Teachers may enter marks only while permitted and before lock. Students can start/submit only an ongoing, eligible schedule before server expiry. No UI-supplied state transition is trusted; domain actions enforce transitions.

## Attempt protection

The server clock and persisted `server_expires_at` determine validity. Client timers are advisory. Attempt creation is idempotent and unique by schedule/student/attempt number. Duplicate submit is safe and returns the existing terminal state. Published or locked data is immutable except for an explicitly authorized, audited correction operation.

## Threat tests

Plan tests for cross-school exam, schedule, question, student/enrollment and attempt IDs; teacher outside assignment scope; student viewing another attempt; parent viewing another child; expired access; duplicate attempt/submission; post-lock submission; publication mutation; and question mutation after attempt start. Mixed-school bulk mark payloads must reject atomically.

## Audit events

Audit exam creation, scheduling, start, submission, lock, publication, question creation/update, mark entry/correction and lifecycle changes. Store actor, school, target, timestamp and safe before/after metadata. Never log passwords, tokens or unnecessary answer content.

## API boundary

Phase 5A policies and actions enforce school, assignment and lifecycle boundaries for exams, schedules, questions and manual marks. Future `/api/v1/exams`, `/schedules`, `/questions`, `/attempts`, `/answers` and `/marks` endpoints must invoke the same policies/actions as Livewire. Online attempt security remains design-only.

Manual marks are accepted only for the schedule's school, academic scope, enrollment population and assigned teacher; locked or published exams reject entry.

Paper mutations additionally reject foreign paper/version IDs, duplicate versions and locked/published exams. Livewire methods re-query every supplied identifier within the active school before invoking domain actions. Correction reasons are mandatory and stored in audit after-metadata.

## Final closure matrix

| Domain | Attack | Expected | Tested |
| --- | --- | --- | --- |
| Exam | foreign school / unassigned teacher | reject | yes |
| Schedule | foreign teacher, class, section or subject assignment | reject | yes |
| Paper | foreign question version / locked paper | reject | yes |
| Question | foreign bank or version | reject | yes |
| Option | foreign version or option ID | reject | yes |
| Marks | unassigned schedule or foreign enrollment | reject | yes |
| Livewire | foreign bank/question/option/schedule IDs | reject | yes |

Teachers have assignment-scoped exam visibility and marks entry only. Question-bank, question, option, paper and correction mutations remain school-admin-only.

The direct component regression suite now covers the teacher marks component's unassigned/foreign schedule and unassigned exam paths, plus admin foreign exam and bank/question/option IDs. Unsupported teacher mutations are rejected at route middleware and are intentionally not exposed.
