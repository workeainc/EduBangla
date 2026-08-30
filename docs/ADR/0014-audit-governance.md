# ADR 0014: Pilot Audit Governance

- Status: Proposed for pilot hardening review
- Date: 2026-08-30

## Context

The accepted domains record consequential writes through
`App\Domain\Audit\RecordAudit`. `audit_logs` stores school, actor, action,
auditable target and before/after JSON. The pilot currently has no audit viewer,
retention schedule or explicit audit read policy.

## Decision

1. Audit records are append-only evidence. Application actions must not update
   or delete an existing audit row; corrections are new audit events.
2. School administrators may read only audit rows for their active school.
   Teachers, students, parents and staff have no audit read access. Any future
   platform support access requires a separately authorized, audited path.
3. Audit reads must use an explicit tenant scope and minimized fields. Raw
   sensitive credentials, secrets and unnecessary personal data must never be
   placed in `before` or `after` payloads.
4. Retention, legal hold and export periods require pilot-owner approval before
   production; until then, rows are retained with the operational history and
   no automatic purge is introduced.
5. A read-only audit UI is not part of this decision. It may be proposed after
   the read policy and retention owner approve the surface.

## Consequences

Existing write transactions remain the source of audit atomicity. A future
governance implementation needs an append-only model/database policy, a
school-admin read policy, tenant-isolation tests and a retention/export
procedure. This ADR does not add queues, providers, APIs, parent delivery or
cross-domain automatic notifications.

## Required verification before acceptance

- Cross-school audit rows cannot be read by a school admin.
- Non-admin portal users cannot read audit rows.
- Update/delete attempts are rejected or otherwise proven impossible.
- Failed domain transactions leave no audit row.
- Finance, Result/ReportCard, Promotion, Communication and Timetable audit
  payloads contain enough state to explain the event without secrets.
