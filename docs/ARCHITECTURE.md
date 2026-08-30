# EduBangla Architecture v1.0

Attendance actions and policies extend the frozen Phase 1–3 tenant architecture. Explicit session scope and enrollment references preserve historical meaning after later academic changes.

Phase 5 architecture is documented in the phase-specific scope, database,
security and acceptance documents. Phases 5A–5H are code-delivered foundations;
their deferred boundaries remain explicit.

## System overview

EduBangla is a modular Laravel application for Bangladesh schools. It begins with a single-school pilot but uses shared-database, row-scoped multi-tenancy from the first release. A school is the tenant boundary. The web application serves school administrators, teachers, students, parents, and staff; a versioned API enables future mobile and external integrations.

```mermaid
flowchart TB
    UI[Livewire / Tailwind UI] --> APP[Laravel application]
    API[/API v1/] --> APP
    APP --> DOM[Domain services and actions]
    DOM --> DB[(MySQL operational data)]
    DOM --> CACHE[(Redis cache / queue)]
    DOM --> FILES[Laravel Storage]
    DB --> AUDIT[Audit records]
    DB -. de-identified aggregates, later .-> INTEL[Education intelligence]
```

## Principles

1. Every tenant-owned record is scoped to a school, and tenant checks occur in queries and policies.
2. Livewire components, controllers, and views orchestrate requests only; domain services/actions own business rules.
3. Curriculum, assessment, grading, and promotion rules are configurable data, not scattered constants.
4. Student identity is persistent; yearly enrollment represents academic placement.
5. Critical operations are auditable, validated, authorized, transactional, and tested.
6. Pilot simplicity must not compromise future multi-school operation or API compatibility.

## Module boundaries

`School` owns tenant metadata and settings. `Academic` owns curriculum, years, class structure, subjects, and timetables. `Student` owns identity, guardians, documents, and enrollment history. `Attendance`, `Examination`, `Result`, `Finance`, `Communication`, and `Analytics` are separate domains. `Identity` provides authentication, roles, permissions, policies, and tenant context. No module directly encodes another module's calculation rules.

Suggested source layout after Laravel is initialized:

```text
app/Domain/{School,Academic,Student,Teacher,Attendance,Examination,Result,Finance,Communication,Analytics}/
app/{Actions,Policies,Jobs,Events,Http}/
database/{migrations,seeders,factories}/
tests/{Feature,Unit,Architecture}/
```

## Multi-school strategy

The initial model is one MySQL database with `school_id` on tenant-owned records. Central data includes users, permission definitions, curriculum definitions, and platform configuration. `users` is deliberately capable of becoming a future person/student-identity anchor; no national identifier is implemented. Tenant data includes school-specific memberships, academic operations, school student profiles/enrollments, assessments, fees, notices, files, and audit events. Tenant context is resolved from the authenticated membership/request, never trusted from a user-supplied identifier. Repository/query scopes and Laravel Policies must enforce it together. A future tenant-per-database migration is possible only behind these domain boundaries.

## Authentication and authorization

Laravel authentication provides credential and session management. Sanctum protects API tokens. Spatie Permission provides the platform role catalogue; an active `school_users` membership binds a school-local role and Laravel Policies enforce record-level authorization. This prevents a global role assignment from granting access across a user's schools. A user may have memberships in multiple schools, but each request has exactly one authorized school context. Parents can access only linked children; students only their own records; teachers only assigned academic work.

## Data flow

An authorized request resolves tenant context, validates input through a Form Request, calls an Action/Service, writes within a transaction where needed, records an audit event, and returns a UI/API response. Long-running work (report generation, notification delivery, imports) is placed on Redis-backed queues. Result calculation follows: raw marks → assessment weights → subject total → grade/point → GPA → publication/report card.

## API strategy

All external endpoints live under `/api/v1`. API resources expose only authorized, minimized fields. Sanctum, policies, throttling, validation, pagination, and stable error formats are mandatory. Changes that break public behaviour require a new API version. Initial domains: auth, schools, students, academic, attendance, exams, results, fees, notices, and analytics.

## Security and privacy

Use CSRF protection, secure password hashing, rate limits, validation, authorization policies, private file storage with authorized downloads, encrypted sensitive fields where justified, and immutable-style audit events. Do not centralize personally identifiable student data for analytics; future analytics uses validated, minimized, de-identified aggregates. Document backup, restore, retention, and incident procedures before production deployment.

## Scaling path

Phase 1 serves one school through a modular monolith. Redis queues/cache and stateless Laravel processes support horizontal web scaling. Indexed tenant keys and asynchronous jobs keep operational queries bounded. Later, read models/aggregates can serve benchmarking without coupling reporting workloads to transactional tables. Government integrations require an explicit data-governance ADR before activation.

## Phase 3 implementation

School Admin routes use authenticated membership, tenant-context middleware, and a dedicated School Admin membership middleware. Teacher and Staff profiles remain school-owned records linked optionally to central Users; no credentials are duplicated. Assignment writes resolve the current tenant server-side, validate the complete academic relationship, and preserve prior-year records. Teacher profile and assignment pages resolve the profile from the authenticated user and active school, not a browser-supplied teacher identifier. MySQL 8.4 migration and seed verification was completed locally on 2026-08-29.
Admin management screens use Livewire and expose create/list/edit/status workflows; assignment review queries eager-load related teacher, year, class, section, subject and group records and apply tenant-scoped filters. Direct Livewire methods re-resolve all profile IDs through `forSchool()` queries, so forged IDs cannot cross the active tenant.
