# Plan 002: Establish a tenant-safe, immutable in-app notice foundation

> **Executor instructions**: Follow this plan step by step. Run every
> verification command and confirm the expected result before moving to the
> next step. If anything in the "STOP conditions" section occurs, stop and
> report — do not improvise. When done, update the status row for this plan in
> `plans/README.md`.
>
> **Drift check (run first)**: `git diff --stat 83ac2df..HEAD -- app database/migrations routes resources/views tests docs/ADR/0013-communication-notice-boundary.md docs/ROADMAP.md plans/README.md`
> If any in-scope file changed since this plan was written, compare the current
> code to the facts below; on a material mismatch, stop and refresh this plan.

## Status

- **Priority**: P1
- **Effort**: L
- **Risk**: HIGH — a recipient or tenant-scope error can disclose school or student information.
- **Depends on**: completed Plan 001 and the accepted Phase 5F baseline.
- **Category**: direction | architecture | migration | security | tests | docs
- **Planned at**: commit `83ac2df`, 2026-08-30

## Why this matters

Notices are a pilot must-have in `docs/PILOT_SCOPE.md:11`. The architecture and
database blueprint already define Communication as a bounded domain with
notices, audiences and deliveries (`docs/ARCHITECTURE.md:34-47`,
`docs/DATABASE.md:41`). A focused in-app notice foundation supplies a reusable
communication contract for existing portals without coupling Finance, Result
or Promotion to each other or prematurely committing to external delivery
providers.

## Current state

- Tenant root and scope convention: `app/Models/Concerns/BelongsToSchool.php:9-19`
  provides `forSchool($school)`; every Communication model must use it and
  carry a non-null `school_id`.
- Request context: `app/Domain/School/TenantContext.php:13-39` activates one
  school only after an active `school_users` membership. Never accept a
  browser-supplied tenant or role.
- Audit convention: `app/Domain/Audit/RecordAudit.php:9-14` writes actor,
  school, action, polymorphic target and safe before/after data. Write audit
  rows in the same transaction as the notice mutation.
- Persistent recipient identities already available are `User`, active
  `SchoolUser`, `Student` (optional `user_id`), `Teacher` (optional `user_id`)
  and `Staff` (optional `user_id`). `Guardian` has no `user_id` or membership
  (`app/Models/Guardian.php:10-20`), so parent delivery is not implementable
  safely in this phase.
- Existing UI convention: `app/Livewire/Admin/FinanceManagement.php:68-139`
  authorizes the route school at mount, validates scalar input, and delegates
  writes to Actions. Communication components must match this shape while
  keeping audience resolution and lifecycle checks in Actions.
- Existing portal route shape: `routes/web.php` has an `auth` +
  `tenant.context` group, then `school.admin`, `teacher`, or `student` route
  middleware. Add Communication routes inside that group.
- Architecture constraints: `docs/ARCHITECTURE.md:25-30` requires tenant
  queries/policies, Action-owned rules, durable Student identity with annual
  Enrollment placement, and auditable/transactional critical operations.
- ADR 0013 is authoritative for the Phase 5G boundary. Do not create direct
  Finance/Result/Promotion foreign keys or automatic cross-domain sending.

## Commands you will need

| Purpose | Command | Expected on success |
| --- | --- | --- |
| Focused tests | `php artisan test --filter=PhaseFiveG` | exit 0; all PhaseFiveG tests pass |
| Full suite | `php artisan test` | exit 0 |
| Style | `vendor/bin/pint --test` | exit 0 |
| View compilation | `php artisan view:cache` | exit 0 |
| Route inventory | `php artisan route:list --name=notice` | only the planned admin/recipient notice routes appear |
| SQLite migration | `php artisan migrate:fresh --force` | exit 0 against the disposable configured database |
| MySQL migration | `DB_CONNECTION=mysql DB_DATABASE=edubangla_test php artisan migrate:fresh --force` | exit 0 against an explicitly disposable MySQL database |

## Scope

**In scope**

- a new ordered Communication migration; `Notice`, `NoticeAudience` and
  `NoticeDelivery` models/factories; the `BelongsToSchool` convention;
- `app/Domain/Communication/Actions/` and one narrow recipient resolver;
- policies for notices, deliveries and audience records;
- admin authoring/list/detail Livewire components and recipient inbox/detail
  components with matching Blade views;
- Communication routes only; focused PhaseFiveG feature tests; the Phase 5G
  scope/database/security documentation and this ADR if implementation
  reveals a genuinely material design correction.

**Out of scope**

- Changes to Phase 4 or Phase 5A–5F, including any Finance calculations,
  tables, Actions or screens.
- SMS, email, push, webhooks, provider credentials, queues, retries,
  templates, attachments, scheduled notices, recurring reminders, bulk
  imports, delivery receipts outside the in-app inbox, analytics, or public
  API endpoints.
- Parent delivery, because Guardian-to-User membership does not exist.
- Automatic notices for fees, attendance, exams, results or promotions; those
  require separate post-commit event/contract decisions.

## Git workflow

- Branch: `codex/phase-5g-communication-notices`.
- Use focused conventional commits consistent with `83ac2df feat: finalize phase five f finance foundation`.
- Do not push, merge, or change accepted phase behavior without explicit operator approval.

## Domain design

### Entities and schema principles

| Entity/table | Required fields and role | Lifecycle / integrity |
| --- | --- | --- |
| `Notice` / `notices` | `school_id`, title, body, status, published/withdrawn timestamps, author, optional safe metadata | `draft → published → withdrawn`; a published payload and target definition are immutable. |
| `NoticeAudience` / `notice_audiences` | `school_id`, notice, audience type (`school`, `role`, `class_section` only), scope snapshot JSON | Draft definition only; publication snapshots it and prevents mutation. |
| `NoticeDelivery` / `notice_deliveries` | `school_id`, notice, recipient `user_id`, recipient role/type snapshot, optional linked student/teacher/staff ID, delivered/published and read timestamps | Unique `(notice_id, user_id)`; recipient snapshot persists even if later membership/profile data changes. |

Every foreign relationship is reloaded through `forSchool()` in Actions. Use
restrict-on-delete for published records, `school_id` indexes, and a
school-aware unique constraint where relevant. Do not use a polymorphic,
browser-provided recipient selector. The only initial audience types are:

- `school`: all active school memberships;
- `role`: active school memberships for one allowed role;
- `class_section`: active Students with an active matching Enrollment and an
  active student membership.

Class/section audiences reuse `Enrollment`; they never copy or create student,
year, class or section records. An audience's materialized deliveries—not a
live role/class query—are the authority after publication.

### Authorization and security matrix

| Actor | Read | Mutate | Explicit denial |
| --- | --- | --- | --- |
| Active school admin | all own-school notices, audiences and deliveries | draft/create, publish, withdraw | any foreign school or post-publish content/target edit |
| Teacher | own delivered notices only | mark own delivery read | authoring, other users' delivery state |
| Student | own delivered notices only | mark own delivery read | authoring, peer delivery state |
| Staff | own delivered notices only | mark own delivery read | authoring, other delivery state |
| Parent/guardian | none in this plan | none | all Communication routes and hydrated IDs |
| Guest/inactive/non-member | none | none | all routes and mutations |

Each policy must require matching `school_id` plus active membership. Delivery
read state must use the authenticated user as the recipient authority, never a
user ID supplied by Livewire. Actions must repeat authorization and scope
checks; UI filtering and route middleware alone are insufficient.

### Transactions, audit and lifecycle

- Draft creation/update: validate same-school audience references, save the
  draft/audiences, audit `communication.notice_created` or `_updated`.
- Publish: lock the notice, re-resolve all recipients on the server, reject an
  empty recipient set, create every unique delivery and immutable audience
  snapshot, set publication timestamps, and audit `communication.notice_published`
  in a single `DB::transaction()`.
- Withdraw: lock the published notice, set only withdrawal state/timestamp,
  retain deliveries and audit `communication.notice_withdrawn` atomically.
- Read: lock/reload only the active recipient's delivery, set `read_at` once;
  repeated calls are idempotent and do not rewrite a first read timestamp.
- Audit metadata may contain IDs, counts, allowed audience types and lifecycle
  timestamps. It must not duplicate notice bodies or unnecessary recipient PII.

## Steps

### Step 1: Freeze the implementation boundary in documentation

Create `docs/PHASE_5G_SCOPE.md`, `docs/PHASE_5G_DATABASE.md` and
`docs/PHASE_5G_SECURITY_MATRIX.md` from ADR 0013 and this plan. State that the
surface is in-app notices only and list every deferred feature. Update the
roadmap and `plans/README.md` status only after the implementation is accepted.

**Verify**: `rg -n "in-app|Parent|SMS|Finance|published|school_id" docs/PHASE_5G_*.md docs/ADR/0013-communication-notice-boundary.md` → each boundary and invariant is documented.

### Step 2: Add tenant-owned persistence and model relationships

Create exactly the three tables/entities described above. Add relationships
from Notice to audiences/deliveries and from delivery to User and optional
profile. Prevent normal mutation/deletion of published notice content,
published audience records and deliveries via model guards plus Action
lifecycle checks. Add factories using only same-school related records.

**Verify**: `php artisan migrate:fresh --force && php artisan test --filter=PhaseFiveG` → migrations and focused persistence/isolation tests pass.

### Step 3: Implement authoritative Communication Actions

Create Actions for draft save, publish, withdraw and mark-read. Place all
recipient resolution in a dedicated Communication service/action that accepts
the authorized school and normalized audience definitions, returns unique
active users, and never relies on IDs already present in a component.

The publish Action must have an optional test-only failure callback after the
first delivery so rollback can be proven. Do not dispatch jobs or contact
providers. Use `RecordAudit` inside the enclosing transaction.

**Verify**: `php artisan test --filter=PhaseFiveG` → forged audience,
cross-school recipient, duplicate recipient, empty audience and forced
mid-publication rollback tests pass.

### Step 4: Add policies, routes and thin Livewire adapters

Add `NoticePolicy`, `NoticeDeliveryPolicy`, and an audience policy only if
directly needed. Register admin routes under
`/schools/{school}/admin/notices`; add recipient inbox/detail routes for
teacher, student and staff under their existing guarded portal prefixes.

Admin components may validate title/body/scalar audience inputs and call
Actions. Inbox components may load only `forSchool($school)` deliveries for
`auth()->id()` and call the mark-read Action. Re-query every route/hydrated
Notice or Delivery ID under the active school before delegation.

**Verify**: `php artisan route:list --name=notice && php artisan test --filter=PhaseFiveGScope` → route inventory is limited to the planned surfaces and all role/tenant checks pass.

### Step 5: Complete documentation and cross-engine verification

Document routes, lifecycle, audit event names, query indexes, scope limits,
and both database-engine evidence. Run the complete suite, style check and
view cache. Run the MySQL migration/test gates only against a disposable
database; if unavailable, record the exact environment block without treating
it as a pass. Perform browser verification of admin publish, each recipient
inbox, read state, denied foreign URL and withdrawn notice behavior.

**Verify**: `php artisan test && vendor/bin/pint --test && php artisan view:cache && git diff --check` → all commands exit 0.

## Test plan

Create `tests/Feature/PhaseFiveGTest.php` and `tests/Feature/PhaseFiveGScopeTest.php`, following the transaction and forged-ID structure in
`tests/Feature/PhaseFiveFTest.php` and scope structure in
`tests/Feature/PhaseFiveFScopeTest.php`.

- Draft/publish/withdraw lifecycle; published content and audience immutability.
- Tenant-isolated notice/audience/profile/enrollment references; all invalid
  payloads leave notices, deliveries and audit rows unchanged.
- Server-resolved school, role and class/section audiences; duplicate users
  receive one delivery; inactive memberships and inactive enrollments are excluded.
- Student/teacher/staff own-inbox and own-read behavior; peers, parents,
  guests, inactive members and foreign-school users are denied.
- Recipient snapshot stability after membership, profile or enrollment changes.
- Forced exception after first delivery rolls back notice status, every
  delivery and its audit record; repeated mark-read is idempotent.
- SQLite full/focused suite and the same focused tests against disposable MySQL.
- Browser verification is required and may be recorded as environment-blocked
  only with the blocking condition stated.

## Done criteria

- [ ] No Phase 4 or Phase 5A–5F code, migrations, routes or behavior changed.
- [ ] Every Communication table is tenant-owned, indexed and uses restrictive
  historical deletion semantics.
- [ ] Published audience/delivery snapshots make later membership and academic
  changes unable to rewrite historical recipients.
- [ ] Policies, tenant-scoped Actions and Livewire hydration independently
  deny foreign and unauthorized access.
- [ ] Publication/withdrawal audits share their transactions; rollback tests pass.
- [ ] `php artisan test`, `vendor/bin/pint --test`, `php artisan view:cache`,
  `git diff --check`, SQLite migration, and disposable-MySQL migration/test
  gates pass or any environment block is documented honestly.
- [ ] Browser verification covers the stated UI and authorization cases.
- [ ] Documentation and `plans/README.md` accurately record closure status.

## STOP conditions

Stop and report—do not improvise—if:

- Parent delivery is requested before a tenant-safe Guardian-to-User
  membership/link model is approved.
- The feature requires an external provider, background delivery, attachment
  storage, automatic Finance/Result/Promotion sending, or a new API contract.
- A current accepted phase must be changed to create a recipient, audience or
  delivery.
- SQLite and MySQL disagree on a proposed integrity constraint/locking rule.
- Any accepted source excerpt or ADR 0013 boundary has materially drifted.

## Maintenance notes

Future SMS/email/push must consume immutable NoticeDelivery records through a
separate provider-outbox/retry ADR; it must not recreate audience evaluation.
Automatic financial or academic notices must be post-commit integrations with
their source domain, not foreign keys from Communication into finance/results.
Reviewers should scrutinize all recipient queries, policy checks, historical
immutability, and rollback evidence before UI polish.
