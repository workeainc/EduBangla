# Phase 5F Roadmap

Phase 5F establishes finance configuration, explicit enrollment assignments,
invoice charges, manual payment allocation, credit adjustments, tenant
authorization, immutable history and audited transactional operations.

The next finance design decision, if product requirements demand it, must be a
separate ADR for billing periods/recurrence and its idempotency rules. It is
not implemented here. Payment gateways, refunds, installments, tax and a
general ledger each require independent scope and security decisions.

Phase 5G is not started by this work.

## Closure verification record

- SQLite full suite: 79 tests / 186 assertions passed.
- Phase 5F focused suite: 8 tests / 20 assertions passed.
- Scoped Pint, Blade cache, route inventory and `git diff --check`: passed.
- MySQL: blocked by local connection permission (`SQLSTATE[HY000] [2002]`).
- Composer audit: blocked because `repo.packagist.org` DNS/network was unavailable.
- Git commit/push: blocked creating `.git/index.lock` (`Operation not permitted`).
- Browser: `BROWSER VERIFICATION: BLOCKED — ENVIRONMENT`.
