# Phase 5F Security Matrix

| Surface | Protection | Expected |
|---|---|---|
| Admin finance routes | `auth`, tenant context, active school-admin membership | foreign/teacher/student requests rejected |
| Finance Actions | actor membership plus every referenced model reloaded by `school_id` | foreign school, student, enrollment, year, class, section, invoice and payment rejected without mutation |
| Student finance | active student membership + `Student.user_id` ownership + invoice policy | issued own records visible; peer/foreign/void records rejected |
| Livewire mutations | scalar validation then tenant-scoped Domain Action | forged/stale hydrated IDs cannot write data |
| Due calculation | `FinanceBalance` uses persisted items, allocations and posted credits | browser values are never authoritative |
| Historical facts | model guards plus lifecycle Actions | issued items, recorded payments, allocations and posted adjustments are not silently rewritten |
| Corrections | void only before allocation; otherwise explicit reversals | original facts and audit trail remain visible |
| Audit | `RecordAudit` is written inside each database transaction | failed business transactions do not leave false audit events |

Teachers have no finance access in Phase 5F. No UI filtering is accepted as an
authorization boundary.

## Automated evidence

| Boundary | Test | Expected / actual result |
|---|---|---|
| Tenant and relationship scope | `PhaseFiveFTest::test_foreign_finance_ids_and_mismatched_same_tenant_relationships_leave_no_financial_rows` | Foreign, nonexistent, and student/enrollment mismatch IDs reject; no payment, adjustment, audit, or balance mutation — PASS |
| Payment allocation and balance | `PhaseFiveFTest::test_invoice_and_balance_are_server_authoritative` | Allocations produce server-calculated outstanding balance — PASS |
| Historical snapshots | `PhaseFiveFTest::test_historical_invoice_snapshot_survives_future_structure_change` | Later category-master change does not rewrite invoice line — PASS |
| Immutable financial facts | `PhaseFiveFTest::test_issued_invoice_items_and_recorded_payment_cannot_be_silently_changed` | Issued item/payment mutation throws and persisted facts remain protected — PASS |
| Transaction rollback | `PhaseFiveFTest::test_payment_transaction_rolls_back_after_first_allocation` | Payment, allocation, balance, and audit all roll back after a post-write failure — PASS |
| Reversal/void rollback | `PhaseFiveFTest::test_reversal_and_void_roll_back_after_mutation_without_false_audit` | Reversal and void state changes roll back after deterministic post-write failure — PASS |
| Lifecycle idempotency | `PhaseFiveFTest::test_repeated_reversal_and_void_are_rejected_with_persisted_state_unchanged` | Repeated reversal/void rejects with original persisted state intact — PASS |
| Student/teacher route boundary | `PhaseFiveFScopeTest` | Students receive only own finance entry point; teachers and foreign-school users are denied — PASS |
