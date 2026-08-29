# Engineering Rules

Attendance status values are centralized in `AttendanceStatus`; domain actions own validation and transactions, while Livewire remains an adapter. Finalization is audited and locks normal teacher edits.

1. Inspect Git status/history before significant work; make focused conventional commits and push without force.
2. Never commit secrets, `.env` files, credentials, raw production data, or sensitive database dumps.
3. Do not put business logic in views, controllers, or Livewire components. Use domain Actions/Services and explicit contracts.
4. Use Form Requests for validation, Policies for authorization, and tenant-scoped queries for every tenant record.
5. Never bypass tenant isolation. Verify it in feature tests.
6. Use transactions for critical multi-step writes such as enrollment, payment, result publication, and promotion.
7. Make curriculum, assessment, grading, and promotion rules configurable and versioned where behaviour can change.
8. Use migrations for schema changes; version seeders, factories, tests, and documentation with code.
9. Write unit tests for academic calculations and feature tests for authorization, tenant isolation, and critical workflows.
10. APIs remain under versioned paths and retain backward compatibility within a version.
11. Audit sensitive and consequential operations without recording secrets.
12. Record material architectural choices in `docs/ADR/`; update the relevant ADR or create a new one before changing a frozen decision.
13. Before each push, run relevant tests, inspect `git diff`/`git status`, and confirm staged files contain no sensitive data.
14. Run the migration suite against MySQL before a production release; SQLite tests are valuable but do not replace MySQL verification.
15. Livewire actions must re-query tenant-owned records through an active tenant scope; client-side dropdown filtering is never authorization.
