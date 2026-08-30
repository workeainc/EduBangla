# ADR 0013: Communication Notice Foundation Boundary

- Status: Accepted for Phase 5G planning; implementation not started
- Date: 2026-08-30

## Context

The pilot scope requires notices, while the architecture already reserves a
separate Communication domain with `notices`, `notice_audiences` and delivery
records. Attendance, examination, result, promotion and finance now have
their own authoritative, tenant-scoped records. They must not each invent a
separate message table or delivery mechanism.

## Decision

Phase 5G will establish only an in-app, tenant-scoped Notice foundation.
Communication owns authored notice content, immutable publication snapshots,
audience definitions, materialized recipient deliveries and read state. It
consumes stable recipient identifiers from Identity, Student, Teacher and
Staff, but does not copy their profile or academic-placement data.

Audience evaluation occurs on the server at publication time inside the same
transaction that creates delivery rows and audit events. A published notice is
never silently edited or retargeted: it is withdrawn, superseded, or followed
by a new notice. Livewire is an adapter only; policies, tenant-scoped actions
and server-side audience resolution are authoritative.

No domain may reference Finance, Result or Promotion database tables merely to
send a notice. Later domain actions may emit a narrow, versioned
communication request after their own transaction commits. SMS, email, push,
queued provider delivery, attachments, templates, recurring reminders and
automatic finance/result/promotion notifications remain separate decisions.

## Consequences

The initial foundation is immediately useful to school admins, teachers,
students and staff without making notification delivery or external-provider
reliability a prerequisite. Parent delivery is deferred because a Guardian is
not yet linked to a central authenticated User/membership. The plan must prove
cross-school and forged-audience rejection, recipient snapshot stability,
publication rollback and read-state ownership on SQLite and MySQL.
