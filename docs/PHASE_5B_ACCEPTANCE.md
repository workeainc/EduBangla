# Phase 5B Acceptance

| Area | Status | Evidence |
|---|---|---|
| Attempt foundation/lifecycle | PASS | `StartExamAttempt`, `SubmitExamAttempt`, `FinalizeExamAttempt` |
| Student ownership/tenant/enrollment | PASS | action and route authorization tests |
| Immutable snapshot | PASS | prompt/options snapshot regression |
| Server timing/expiry | PASS | before/after-expiry tests; reject-only policy |
| MCQ/True-False/Short/Descriptive answers | PASS | type-aware validation tests |
| Submission/finalization | PASS | lifecycle and audit tests |
| Duplicate protection | PASS | active-attempt uniqueness test |
| Livewire attack matrix | PASS | foreign attempt/component test |
| SQLite suite | PASS | 52 tests, 122 assertions |
| MySQL suite | PASS | pending final rerun after this closure |
| Static/security checks | PASS | Pint, view cache, routes, diff check, Composer audit |
| Browser verification | BLOCKED | local server port binding prohibited |
| Documentation/Git | PASS | scope, database, security, routes, mutation inventory |

Phase 5C (results/GPA/grade/promotion) is not started. Phase 4 and Phase 5A remain frozen.

Expiry semantics: an expired `in_progress` attempt remains in that state but answer and submit operations are rejected server-side. No client timestamp or remaining-seconds value is trusted.
