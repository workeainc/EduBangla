# ADR 0002: School Membership Roles and Future Identity

- Status: Accepted
- Date: 2026-08-27

## Decision

`users` remains the central, persistent person-identity anchor. The current pilot will not create a national student identifier or integrate government identity. A future student identity may be associated with school-specific student profiles and annual enrollments without changing central authentication identity.

Spatie Permission supplies the platform role catalogue. `school_users` carries the active, school-local role and membership status. Tenant authorization requires an active membership plus policy approval; a global Spatie role alone never grants school-record access. Super Admin remains platform-level and cannot activate a school context without an explicit auditable membership/support grant.

## Consequence

Future curriculum work will model central curriculum versions and allow school adoption/overrides, rather than hard-coding Bangladesh rules. Analytics stays a read-only, governed foundation until a dedicated decision authorizes aggregation.
