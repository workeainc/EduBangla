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

Future `/api/v1/exams`, `/schedules`, `/questions`, `/attempts`, `/answers` and `/marks` endpoints must invoke the same policies/actions as Livewire. API serialization must not expose another tenant's identifiers or answer evidence.
