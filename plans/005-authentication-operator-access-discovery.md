# Authentication & Operator Access Foundation — Discovery

**Status:** Implementation complete; bounded session/operator access only
**Baseline:** `58c73e1` on `codex/authentication-operator-access`
**Relationship:** Separate identity/access track; not Phase 5I and not a new business domain.

## Purpose

The pilot domains are browser-verifiable only through an authenticated session,
but the repository currently has no usable login UI or login workflow. The
`/login` route returns `401 Login required`, while `User` already has a Laravel
session guard, password hashing, Sanctum tokens and school memberships.

This plan defines the smallest identity/access discovery needed before any
authentication implementation. It must preserve the canonical chain:

`Authentication → User → active school membership → TenantContext → school-local role → profile → Policy/Action`

## Current evidence

- `routes/web.php:49-51` exposes only a placeholder `/login` response; no login,
  logout, registration or password-reset UI exists.
- `config/auth.php:17-110` already defines a session `web` guard, Eloquent User
  provider and password broker configuration.
- `app/Models/User.php:8-53` uses `Authenticatable`, `HasApiTokens`, hashed
  passwords, hidden credentials and `schoolMemberships()`.
- `app/Models/School.php:20-24` defines active membership lookup.
- `app/Http/Middleware/EstablishTenantContext.php:14-30` activates and clears
  request-scoped tenant context after membership validation.
- `app/Http/Middleware/RequireSchoolRole.php:8-21`, `RequireStudent.php:9-22`
  and normalized teacher routes establish school-local role gates.
- `database/seeders/RoleSeeder.php:14-16` seeds global role catalogue entries;
  these must not become authorization authority for school data.
- `docs/ADR/0002-school-membership-roles-and-identity.md` makes `users` the
  persistent person anchor and `school_users` the school-local role boundary.
- `docs/ADR/0015-parent-identity-boundary.md` blocks parent access pending an
  explicit Guardian ↔ authenticated User ↔ school membership decision.

## Discovery decisions required

1. **Login architecture:** session-based web authentication first; no API or
   mobile authentication in this track.
2. **Operator identities:** Admin, Teacher, Student and Staff only. Parent is
   excluded until ADR 0015 is approved.
3. **Registration:** determine whether pilot accounts are seeded/admin-created
   only; do not expose open registration by default.
4. **Password lifecycle:** determine reset, confirmation timeout, verification,
   lockout/rate-limit and password rotation requirements.
5. **School selection:** define behavior for users with zero, one or multiple
   active school memberships. Tenant context must remain request-scoped and
   server-authorized.
6. **Inactive membership:** login may authenticate the User, but every school
   route must deny inactive membership and must not retain an invalid active
   school context.
7. **Profile eligibility:** teacher/student portal access requires both the
   matching active school membership and active school-owned profile.
8. **Logout/session safety:** session regeneration on login, session invalidation
   and CSRF-protected logout; no browser-supplied role or school becomes trusted.
9. **Browser strategy:** define seeded disposable users and a safe authenticated
   browser environment without exposing real credentials.

## Explicit scope

- Session-based login/logout and operator access design only after approval.
- School-local membership selection/activation rules.
- Password/reset and session-security policy.
- Seeded disposable pilot-user strategy.
- Browser walkthrough enablement.

## Explicitly out of scope

- Phase 5I or any new academic/finance/communication domain.
- Parent authentication or parent portal.
- API/Sanctum token endpoints, mobile auth or OAuth/SSO/MFA integrations.
- Recurring finance, gateways, refunds, tax, ledger or installments.
- Communication providers, queues, SMS/email/push or automatic notifications.
- Timetable rooms/substitutions/holidays/optimizer/import/export.
- Attendance/exam automation, analytics, HR/payroll or government identity.

## Proposed implementation boundary after approval

If the owner approves implementation, it should be a separately named identity
track with only the authentication routes, views/components, session handling,
seed strategy and tests necessary to establish an authenticated browser session.
Every existing route/policy/action remains authoritative for tenant and role
authorization. No business domain should inspect global Spatie roles as a
substitute for active `school_users` membership.

## Required test matrix after approval

- Valid login and logout; session regeneration/invalidation.
- Invalid credentials and rate-limit behavior.
- User with no school membership.
- User with inactive membership only.
- User with memberships in multiple schools and explicit school selection.
- Global Spatie role without matching active school membership.
- Active membership without matching active teacher/student profile.
- Cross-school URL and Livewire hydrated-ID attempts.
- CSRF-protected state changes and password reset/confirmation policy.
- Parent account remains denied until ADR 0015 approval.

## Acceptance gate

Authentication discovery is accepted only when the owner approves the identity
and school-selection decisions, confirms seeded disposable users, and authorizes
a separate implementation track. Until then:

```text
AUTHENTICATED BROWSER: BLOCKED
PARENT PORTAL: BLOCKED
PHASE 5I: NOT STARTED
```
