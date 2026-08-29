# ADR 0006: Server-authoritative online timing

## Decision

The server persists start and expiry timestamps and rejects start/answer/submit operations after expiry. Client timers are presentation-only; duplicate submissions are idempotent.

## Rationale

Client clocks and JavaScript timers cannot provide reliable examination validity or audit evidence.
Phase 5B calculates expiry from the schedule end time on the server. Every answer and submit request rechecks expiry; post-expiry requests finalize the attempt and reject mutation.
