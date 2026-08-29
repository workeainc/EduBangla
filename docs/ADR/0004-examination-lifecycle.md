# ADR 0004: Examination lifecycle

## Decision

Exams transition only through domain actions: `draft → scheduled → ongoing → completed → locked → published`. Backward transitions and arbitrary UI status writes are forbidden. School Admin owns scheduling, locking and publishing; teachers operate within assignment scope; students only start and submit eligible attempts.

## Rationale

Explicit transitions protect marks and provide an auditable handoff from operational assessment to the future Result domain.
