# Plan 001: Establish a tenant-safe, immutable finance foundation

> **Executor instructions**: Follow this plan step by step. Run every
> verification command and confirm the expected result before moving to the
> next step. If anything in the "STOP conditions" section occurs, stop and
> report — do not improvise. When done, update the status row for this plan in
> `plans/README.md`.
>
> **Drift check (run first)**: `git diff --stat debabeb..HEAD -- app database/migrations routes resources/views tests docs`
> If any in-scope file changed since this plan was written, compare the current
> code to the excerpts below. On a material mismatch, treat it as a STOP
> condition and refresh the plan before implementation.

## Status

- **Priority**: P1
- **Effort**: L
- **Risk**: HIGH — this introduces money records, access control, and immutable history.
- **Depends on**: none (accepted Phase 5E baseline only)
- **Category**: architecture | migration | security | tests | docs
- **Planned at**: commit `debabeb`, 2026-08-29
- **Completed**: Phase 5F closure; SQLite and MySQL suites, Pint, view cache,
  route inventory, and Composer audit verified before the closure commit.

## Why this matters

Finance needs the same tenant boundary and historical guarantees as Phase 5D
report cards and Phase 5E promotions. A fee rule may change for future work,
but that must not rewrite an issued invoice, receipt, allocation or waiver.
Every balance must be calculated by trusted server-side records, never from a
Livewire property or browser payload.

This plan deliberately delivers an explicit-assignment finance foundation.
It does not invent a billing-calendar or recurrence engine because the existing
academic model has no billing-period concept and this phase does not define one.

## Existing architecture and integration rules

### Reuse — do not duplicate

- `app/Models/Student.php` is the durable tenant-owned person record.
  `Student::enrollments()` is its annual placement history.
- `app/Models/Enrollment.php` already owns `student_id`, `academic_year_id`,
  `class_id` and `section_id`; an assignment and an invoice must reference this
  record instead of reproducing student/class/year entities.
- `app/Models/AcademicYear.php`, `AcademicClass.php` (table `classes`) and
  `Section.php` are the existing tenant-scoped academic scope.
- Results, report cards and promotions are not finance dependencies. Finance
  must not change their lifecycle or infer payment state from them.

### Tenant and authorization conventions

`app/Models/Concerns/BelongsToSchool.php` supplies the prevailing model
contract:

```php
public function scopeForSchool(Builder $query, School|int $school): Builder
{
    return $query->where('school_id', $school instanceof School ? $school->id : $school);
}
```

`app/Http/Middleware/EstablishTenantContext.php` resolves the route school,
activates `TenantContext`, and verifies an active membership. Route middleware
then grants role access. Use this route shape and middleware order:

```php
Route::middleware(['auth', 'tenant.context'])->group(function () {
    Route::get('schools/{school}/admin/promotions', AdminPromotions::class)
        ->middleware('school.admin');
});
```

`app/Livewire/Admin/Promotions.php` is the applicable adapter pattern: it
stores the route `School`, authorizes on `mount`, reloads hydrated records by
`school_id`, validates scalar UI input, and delegates lifecycle changes to
Actions. Finance components must follow that shape, but must not put financial
calculation, state transition, or scope checks solely in Livewire.

`SchoolOwnedPolicy` makes school-admin mutations the default. Finance needs
dedicated policies because students need their own read-only visibility;
teachers receive no finance authority in this phase.

### Audit and immutability conventions

`app/Domain/Audit/RecordAudit.php` writes `school_id`, actor, action,
auditable target and before/after JSON to `audit_logs`. Call it inside the
same transaction as each consequential finance mutation. Do not put card,
bank, token, or other sensitive payment-provider data in its JSON fields.

`app/Models/ReportCard.php` rejects updates when the original status is
`published`, and ADR 0010 records the established principle: snapshot applied
values at the authoritative transition and do not recalculate closed history.
Use that principle for invoices, payments, allocations and adjustments.

### Existing verification commands

| Purpose | Command | Expected on success |
|---|---|---|
| Focused finance tests | `php artisan test --filter=PhaseFiveF` | exit 0; every PhaseFiveF test passes |
| Full Laravel test suite | `php artisan test` | exit 0 |
| Route inventory | `php artisan route:list --name=finance` | all planned finance routes appear |
| Style check | `vendor/bin/pint --test` | exit 0 |
| Migration smoke test | `DB_CONNECTION=mysql DB_DATABASE=edubangla_test php artisan migrate:fresh --force` | exit 0 against an explicitly configured disposable MySQL database |

The standard test configuration is SQLite in-memory (`phpunit.xml`). The MySQL
command is required before release because production migrations use MySQL; do
not run it against any non-disposable database.

## Domain design

### Proposed entities

| Entity / table | Purpose and scope | Lifecycle and mutability | Historical requirement |
|---|---|---|---|
| `FeeCategory` / `fee_categories` | Tenant-owned catalogue definition such as admission, tuition, examination. `school_id`, immutable code, name, description, active flag. | Draft/active/inactive via `status`; name/description may change, code must not. Inactivation only prevents future use. | Invoice and assignment lines copy category code/name; old documents do not render live category text. |
| `FeeStructure` / `fee_structures` | Versionable fee schedule header for exactly one `school_id + academic_year_id + class_id`; optional human name and active/draft/retired status. | Draft is editable. Activate only after items exist. A structure that has produced assignments is retired/superseded, never destructively altered. | Assignment stores source structure IDs plus a captured schedule snapshot. |
| `FeeStructureItem` / `fee_structure_items` | Category amount and due date within a structure. `fee_category_id`, non-negative decimal amount, optional due date, line order. | Editable only while parent is draft and no assignment references it; otherwise retire/supersede the parent. | Assignment copies category code/name, amount and due date. |
| `StudentFeeAssignment` / `student_fee_assignments` | Explicit eligibility/charge assignment to one existing enrollment. Links the source structure item and target student/enrollment; captures academic and fee snapshots. | `assigned → invoiced`, or `cancelled` only before invoicing. Do not update financial snapshot fields after assignment. | Invoice creation copies the assignment snapshot again; old invoices survive assignment cancellation or future structure change. |
| `Invoice` / `invoices` | Authoritative charge document for one student enrollment. Includes invoice number, academic scope IDs, status, issue/due dates and header snapshot. | `draft → issued → partially_paid → paid`; `draft/issued → void` only when no active allocation, otherwise create correction/adjustment, never rewrite charges. | Header stores student and enrollment/class/year display snapshot and locked amount totals at issuance. |
| `InvoiceItem` / `invoice_items` | Immutable charge lines created only from assignments. Contains `invoice_id`, source assignment ID, category reference, copied code/name, unit/line amount and due date. | Created with draft invoice; no update/delete after issue. Voiding does not delete lines. | All amount and label fields are invoice-local snapshots. |
| `Payment` / `payments` | A tenant receipt, not an invoice payment itself. Holds unique receipt number, payer student/enrollment, received amount/date, method, safe reference, recorder and status. | `recorded → allocated` (or partially allocated); `recorded/allocated → reversed`. Do not edit monetary fields after recording. | Reversal creates an explicit reversal record linked by `reversal_of_payment_id`; never alter original receipt amount. |
| `PaymentAllocation` / `payment_allocations` | Portion of one recorded payment applied to one tenant-compatible issued invoice. | Active unless its payment is reversed. Never edit allocation amount; reverse payment and create a replacement receipt if correction is needed. | Keep allocation row and amount immutable, including after reversal. |
| `FinancialAdjustment` / `financial_adjustments` | Explicit invoice credit for waiver/discount. It is a separate financial event so the original charges remain visible. | `draft → posted → reversed`; posted amount immutable. A reversal is a new linked record, never deletion. | Copies a reason, amount, approver/actor and a snapshot of targeted invoice state. Phase 5F supports credits only; debit corrections require a later approved design. |

Every table above is tenant-owned and therefore has a non-null `school_id`, a
`BelongsToSchool` model, an indexed tenant access path, and tenant-owned
foreign IDs checked server-side. Use `restrictOnDelete()` for historical source
records (student, enrollment, year, class, category, structure) and allow
normal cascade only for unissued draft-only children where it cannot erase a
historical financial record. Do not rely on independent single-column foreign
keys to prove same-school ownership: MySQL cannot express all of these
cross-table tenant predicates, so Actions must re-query every relationship
under the active `school_id`.

### Required schema shape and indexes

- Use `decimal(12,2)` for all money; never float. Currency is `BDT` only in
  Phase 5F, represented by a non-null `currency` default/check at invoice and
  payment level. Do not add multi-currency conversion.
- Fee structures: unique `school_id, academic_year_id, class_id, name` and
  index `school_id, status`.
- Structure items: unique `fee_structure_id, fee_category_id`; order index.
- Assignments: unique `school_id, enrollment_id, fee_structure_item_id`;
  index `school_id, student_id, status`. This means a structure item is an
  explicit one-time charge per enrollment; it is not a monthly recurrence.
- Invoices: unique `school_id, invoice_number`; index
  `school_id, enrollment_id, status`; never unique only by a global number.
- Invoice items: unique `invoice_id, student_fee_assignment_id`, protecting
  double billing from the same assignment.
- Payments: unique `school_id, receipt_number`; index
  `school_id, student_id, received_at`; external reference is not globally
  unique because cash receipts may have none.
- Allocations: unique `payment_id, invoice_id`; indexes on invoice and payment.
- Adjustments: index `school_id, invoice_id, status`; a reversal self-reference
  has a unique constraint on `reversal_of_adjustment_id`.

Add model relationships for every actual foreign key (including enrollment,
student, academic year/class/section, category, assignment, invoice, payment,
allocations and adjustments) so eager loading can be explicit and no view
performs N+1 queries.

## Authoritative invariants

For an issued, non-void invoice, compute server-side and in the database
transaction:

```
outstanding = sum(issued invoice item amounts)
            - sum(active, non-reversed payment allocations to the invoice)
            - sum(posted, non-reversed financial-adjustment credits)
```

Clamp neither side silently: a calculation below zero is a validation failure,
not a negative "due". Store denormalized `charged_total`, `allocated_total`,
`adjustment_total` and `outstanding_total` only as transactionally refreshed
read fields, if they are needed for list performance; the line/event rows
remain authoritative and must be reconciled before every state transition.

The Actions must also enforce all of the following.

- A fee structure's year, class, any section used by an assignment, its items,
  and its category all belong to the active school. An enrollment must belong
  to the active school and match the selected structure year/class; if a
  section scope is supplied, it must match the enrollment section.
- An invoice's student, enrollment, assignments and item sources belong to the
  active school and agree with each other. The student must equal the
  enrollment's student.
- A payment may allocate only to issued, non-void invoices of its own school,
  student and enrollment. Sum of its allocations cannot exceed payment amount.
  Sum applied to an invoice cannot exceed the current post-adjustment due.
- A posted adjustment is a non-negative credit no larger than the current due;
  it targets one eligible issued invoice in the same school. It cannot be
  silently edited or deleted.
- Published/closed financial facts are immutable. Change a mistake with a
  void (only if untouched), a reversal, a credit adjustment, or a newly issued
  replacement document, all with audit events.
- Domain Actions derive the school from `TenantContext` or explicitly receive
  the already-authorized school ID; they must reject any supplied `school_id`
  that disagrees. No browser balance, role or tenant ID is authoritative.

## Authorization model

Create `FeeCategoryPolicy`, `FeeStructurePolicy`, `StudentFeeAssignmentPolicy`,
`InvoicePolicy`, `PaymentPolicy` and `FinancialAdjustmentPolicy`. They must be
registered/discovered according to the existing Laravel policy convention.

| Actor | Permitted | Explicitly denied |
|---|---|---|
| Active school admin | All tenant finance configuration, assignments, draft/issue invoices, receipts, allocations, posted credits/reversals, finance lists/detail and audit history. | Foreign school records; any mutation outside lifecycle. |
| Teacher | Nothing in Phase 5F. | All finance routes, components, policy reads and mutations. |
| Active student with matching `Student.user_id` | Read only own issued/non-void invoices, their immutable items, receipt/allocation summaries and outstanding balance. | Other students, fee configuration, adjustments' internal notes, raw audit records, every mutation. |
| Guest/inactive/non-member | Nothing. | All finance routes and Livewire hydration. |

Policies must always include the record's `school_id` in their decision. Student
policy reads additionally require active `school_users` role `student`, matching
tenant `Student.user_id`, matching invoice student, and a student-visible
status. Route/middleware protection is only the first boundary; each Action and
Livewire mutation must re-query model IDs with `where('school_id', $schoolId)`.

## Transaction boundaries and audit events

Use `DB::transaction()` exactly as the Phase 5E `ApplyPromotion` action does.
Accept an optional closure only where needed for deterministic test failure
injection; production callers do not supply it.

| Action | Atomic work | Audit action |
|---|---|---|
| Create/update/activate/retire structure | validate all same-tenant links; mutate draft configuration; status transition | `finance.fee_structure_created`, `_updated`, `_activated`, `_retired` |
| Generate assignments | lock/validate target enrollment set; create every assignment and snapshot; optional injected failure | `finance.fee_assignments_generated` with count and source IDs |
| Generate/issue invoice | lock assignments; create header + all invoice items; mark assignments invoiced; calculate totals; optional failure | `finance.invoice_generated`, `finance.invoice_issued` |
| Record payment with allocations | validate receipt + every target invoice; create receipt; create all allocations; recalculate all invoice balances/statuses; optional failure | `finance.payment_recorded`, `finance.payment_allocated` |
| Post/reverse adjustment | validate invoice and amount; create/post credit or linked reversal; recalculate invoice; optional failure | `finance.adjustment_posted`, `finance.adjustment_reversed` |
| Reverse payment | create linked reversal; mark original/reversal lifecycle state without changing money; recalculate affected invoices | `finance.payment_reversed` |

Pass only safe identifiers, statuses, amounts, dates and reason codes to
`RecordAudit`. Do not audit payment credentials, gateway payloads or full
personal data snapshots. For update/reversal audit records, use meaningful
before/after status and derived-total snapshots.

## Scope and file map

**In scope — create or modify only these areas:**

- `database/migrations/` — one ordered Finance migration set after Phase 5E.
- `app/Models/` — the nine finance models and their relationships/casts.
- `app/Domain/Finance/Actions/` — all financial mutation and balance actions.
- `app/Policies/` — finance policies only.
- `app/Livewire/Admin/` and `resources/views/livewire/admin/` — finance admin
  adapter components and views.
- `app/Livewire/Student/` and `resources/views/livewire/student/` — own-finance
  read-only components and views.
- `routes/web.php` — finance route registrations only.
- `tests/Feature/PhaseFiveFTest.php` and
  `tests/Feature/PhaseFiveFScopeTest.php` (and a small focused unit test only
  if a standalone balance calculator is extracted).
- `database/factories/` — finance factories required by tests.
- `docs/PHASE_5F_SCOPE.md`, `docs/PHASE_5F_DATABASE.md`,
  `docs/PHASE_5F_SECURITY_MATRIX.md`, `docs/PHASE_5F_ROADMAP.md`, and
  `docs/ADR/0012-finance-historical-integrity.md`.

**Out of scope — do not touch:**

- Phase 4 attendance and accepted Phase 5A–5E migrations, models, actions,
  policies, routes and behavior.
- Communication, analytics, government integrations, payroll, HR, attendance,
  library, transport, parent portal and payment gateway integration.
- A recurring billing engine, billing-period table, late fees, refunds,
  installments, multi-currency, accounting ledger, tax, and payment-provider
  webhooks. Bring any of these back for a separate ADR/scope decision.
- Teacher finance access. There is no present business requirement supporting it.

## Implementation steps

### Step 1: Record the finance boundary before code

Create the four Phase 5F docs and ADR 0012. State that `school_id` is the
tenant boundary; student identity and yearly placement are reused from Student
and Enrollment; assignment/invoice/payment/adjustment snapshots are immutable;
and balance derives from events. Document the explicit-assignment limitation
and all deferred features above.

The security matrix must enumerate admin configuration/mutations, student
read-only, teacher denial, every foreign-ID rejection, audit obligations, and
immutability/reversal behavior. The roadmap must name Phase 5G only as a later
unstarted consideration — do not begin it.

**Verify**: `rg -n "Finance|allocation|immutable|recurr" docs/PHASE_5F_*.md docs/ADR/0012-finance-historical-integrity.md` → each document contains the declared boundary and invariants.

### Step 2: Add the normalized schema, models and factories

Create ordered migrations for the nine tables in the domain-design table. Use
the exact naming and indexed paths described above. Foreign keys establish
referential integrity but Actions provide composite tenant validation. Build
models with `BelongsToSchool`, narrow `$fillable`, `decimal:2` casts, dates and
timestamps, and relationship methods following `ReportCard`/`Promotion`.

Protect model-level history: an issued/paid/void invoice must reject direct
changes to snapshots/items; a recorded/reversed payment must reject monetary
updates; posted/reversed adjustments and allocation amounts must reject edits.
Prefer Action-level state validation plus focused model guards that prevent
accidental Eloquent updates, as ReportCard does. Do not use database cascades
that can erase issued finance history.

Create minimal factories for valid same-school combinations. Factory defaults
must not fabricate cross-tenant related IDs.

**Verify**: `php artisan migrate:fresh --env=testing --force && php artisan test --filter=PhaseFiveF` → migration exits 0 and the schema/factory tests pass.

### Step 3: Implement trusted finance Actions and one balance authority

Create a `FinanceBalance` query/service (or equivalent private Action method)
that computes the invariant only from database rows scoped by school and
invoice. Use it from invoice issuance, allocation, payment reversal,
adjustment posting/reversal, list projection and student views; do not copy
formula logic across Livewire components.

Implement actions with a signature that receives an authorized actor/context
and a server-resolved school, then validates all records by `school_id` before
use. Match the `DB::transaction` plus `RecordAudit` pattern in
`app/Domain/Promotion/Actions/ApplyPromotion.php`. Add an optional test-only
failure callback immediately after the first consequential child write in the
four multi-record workflows; production component code must never expose it.

Use database row locks (`lockForUpdate`) for invoice and payment/adjustment
rows whose current due is checked and changed inside the transaction, so two
payments cannot both allocate the same due. Generate tenant-unique invoice and
receipt numbers inside the transaction and handle unique-key collision by
retrying/rejecting safely; never rely on `max()+1` outside a lock.

**Verify**: `php artisan test --filter=PhaseFiveFTest` → valid configuration, assignment, invoice, payment/allocation, adjustment and reversal workflow tests pass.

### Step 4: Add policies and constrained admin/student routes

Add the six dedicated policies. Put all admin finance routes below:

```
/schools/{school}/admin/finance/fee-categories
/schools/{school}/admin/finance/fee-structures
/schools/{school}/admin/finance/fee-assignments
/schools/{school}/admin/finance/invoices
/schools/{school}/admin/finance/invoices/{invoice}
/schools/{school}/admin/finance/payments
/schools/{school}/admin/finance/adjustments
```

Each uses existing `auth`, `tenant.context`, then `school.admin` middleware.
Add only these student routes under the existing `student` middleware:

```
/schools/{school}/student/finance
/schools/{school}/student/finance/invoices/{invoice}
```

There is deliberately no teacher finance route. Use route-model binding only
after the component/action confirms `school_id`; foreign bound models return
404/forbidden consistently with the accepted modules.

**Verify**: `php artisan route:list --name=finance` → exactly the admin and student finance routes exist; `php artisan test --filter=PhaseFiveFScopeTest` → guest, teacher, inactive, foreign-school and foreign-student route checks pass.

### Step 5: Implement Livewire as a thin adapter layer

Create small focused admin components, preferably `FeeCategories`,
`FeeStructures`, `FeeAssignments`, `Invoices`, `InvoiceDetail`, `Payments`,
and `Adjustments`, instead of a finance god component. On every `mount`, keep
the route school, authorize it, and re-query any bound finance model under that
school. On every mutation method, re-query every hydrated ID by active school,
perform presentation validation only, call one Action, then reload display
state.

The mutation inventory must be explicit and covered: create/update/activate/
retire category and structure; add/remove draft structure item; generate/
cancel assignment; generate/issue/void eligible invoice; record/allocate/
reverse payment; create/post/reverse adjustment. No method calculates a due
from request data or calls `Model::create`/`update` for finance state directly.

Create `Student\Finance` and `Student\InvoiceDetail` as read-only components.
They resolve the authenticated Student under `school_id`, query only own issued
documents, authorize the exact invoice policy, and display server-computed
totals. Never hydrate a selectable student ID in student-facing components.

**Verify**: `php artisan test --filter=PhaseFiveFScopeTest` → every Livewire mutation rejects forged foreign IDs with no database change; student own-record success and foreign-record failure pass.

### Step 6: Prove security, history and rollback before accepting the phase

Write the full test matrix below using `RefreshDatabase`, existing Phase 5E
security test arrangement, and direct Action calls in addition to Livewire.
For every rejected case, assert both the exception/forbidden response and
`assertDatabaseMissing` for the intended finance mutation. For transactions,
invoke the deterministic injected callback after the initial child write and
assert all parent/child/status/audit changes are rolled back — not merely that
pre-validation rejects the input.

Run all focused tests, complete Laravel suite, Pint test and disposable MySQL
migration smoke test. Inspect the final diff to ensure no frozen accepted-phase
file behavior changed and no payment secret/sensitive data was committed.

**Verify**: `php artisan test && vendor/bin/pint --test` → both exit 0; then run the disposable MySQL command from the command table → exit 0.

## Test matrix

`PhaseFiveFTest` must cover:

- Valid same-tenant category → structure/items → assignment → issued invoice
  → receipt/payment allocation → outstanding-balance workflow.
- All direct Actions reject a foreign school, academic year, class, section,
  category, student, enrollment, invoice and payment as applicable.
- Assignment rejects a school-valid but year/class/section-mismatched
  enrollment; invoice rejects an assignment belonging to another student.
- Allocation rejects a foreign invoice, different student/enrollment, void or
  draft invoice, amount above payment remaining, and amount above invoice due.
- Adjustment rejects foreign/wrong-status invoice, negative/over-due amount,
  duplicate reversal and post-closure direct edits.
- The exact balance equation after partial payment, full payment, posted waiver,
  payment reversal and adjustment reversal; no negative due is permitted.
- Historical snapshots: create structure and assignment, issue invoice, retain
  invoice/item snapshots, change/retire a future structure, reload old invoice
  and assert unchanged. Repeat persistence checks for recorded payment,
  allocation and posted/reversed adjustment.
- Deterministic rollback for assignment generation, invoice header/items and
  assignment state, payment/allocations/invoice summaries, and adjustment/
  invoice summary. Assert audit rows also roll back.
- Tenant-unique invoice/receipt number constraints and no duplicate item from a
  single assignment.

`PhaseFiveFScopeTest` must cover:

- Guest, inactive member, student and teacher cannot open admin finance routes.
- Student can open own finance summary/detail only; cannot view a same-school
  peer's invoice or a foreign-school invoice.
- Admin can perform valid finance mutation only in their active school.
- Every Livewire mutation method is invoked with every applicable forged ID:
  foreign year/class/section/student/enrollment/assignment/invoice/payment.
  Assert rejection and no row/status/audit mutation.
- Finance policy methods independently enforce admin tenant ownership and
  student identity ownership, so protection is not only route-based.

## Done criteria

- [ ] No Phase 4 or accepted Phase 5A–5E behavior is changed.
- [ ] All nine finance entities use `school_id`, normalized foreign keys,
  tenant indexes, relationships and correct money/date casts.
- [ ] No invoice, payment allocation or posted adjustment can be silently
  rewritten; reversal/void mechanisms preserve the original facts.
- [ ] The server-side balance authority implements the stated equation, and
  no Livewire/browser balance is trusted.
- [ ] Every multi-record finance mutation is transactional and has deterministic
  partial-write rollback coverage, including audit rollback.
- [ ] Admin finance routes/mutations, student own-record read-only access, and
  teacher denial are covered by policies, middleware and focused tests.
- [ ] `php artisan test --filter=PhaseFiveF`, `php artisan test`, and
  `vendor/bin/pint --test` exit 0.
- [ ] The migrations pass against a disposable MySQL test database.
- [ ] All five Phase 5F documentation artifacts exist and agree with code.
- [ ] `git diff --check` exits 0 and no files outside this plan's scope changed.

## STOP conditions

Stop and report before improvising if:

- Product requirements require monthly/term recurrence, installments, refunds,
  late fees, multiple currencies, tax, gateways or a general ledger. These
  need a billing-period/payment-integration design decision first.
- A requirement needs teacher access to student balances; document the precise
  business role, class/section boundary and privacy rationale before adding it.
- Current code has drifted from the accepted tenant/middleware/action patterns
  quoted above, or a frozen Phase 4/5A–5E change appears necessary.
- The existing audit table cannot safely hold the required action metadata
  without a migration that affects another accepted module.
- SQLite and MySQL differ on a finance constraint or locking behavior; keep the
  issue open and resolve it with a database-specific test/ADR, not a silent
  weaker guarantee.
- Any attempted verification fails twice after a reasonable in-scope repair.

## Maintenance notes

Future recurring billing must introduce an explicit billing-period/charge-run
model and revise assignment uniqueness in a separate ADR; it must not overload
the one-time assignment key in this phase. Payment gateways must enter through
a separately authenticated, idempotent integration boundary; do not make a
gateway callback a Livewire method. Reviewers should focus first on composite
tenant validation, lock scope, reversal semantics, and whether any convenience
update bypasses the immutable event record.
