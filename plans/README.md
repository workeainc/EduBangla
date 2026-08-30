# Implementation Plans

Generated from the Phase 5F discovery on 2026-08-29, against commit
`debabeb`. Execute the plan below only after the Phase 5F implementation is
explicitly approved. It is intentionally a single cohesive foundation plan:
its schema, invariants, actions, policies, UI and tests must land together to
avoid introducing a partially secure finance surface.

## Execution order & status

| Plan | Title | Priority | Effort | Depends on | Status |
|------|-------|----------|--------|------------|--------|
| 001 | Establish a tenant-safe, immutable finance foundation | P1 | L | — | DONE |

Status values: TODO | IN PROGRESS | DONE | BLOCKED (with one-line reason) |
| REJECTED (with one-line rationale).

## Dependency notes

- Phase 5F may depend on the accepted Phase 5E baseline only; do not modify
  Phase 4 or the accepted Phase 5A–5E behaviours.
- A recurring billing/calendar feature is explicitly deferred. The Phase 5F
  charge model is explicit assignment and invoice generation, not a scheduler.

## Considered and rejected

- A separate `BillingPeriod` / recurring-invoice engine: no equivalent domain
  entity exists and the requested scope does not define recurrence rules.
  Adding one now would make financial meaning ambiguous.
- Teacher finance screens: the existing teacher capabilities are
  assignment-scoped academic work only; no business requirement justifies
  student financial visibility.
- Storing a client-calculated invoice balance as the authority: balances must
  be derived server-side from invoice items, posted adjustments and active
  allocations.
