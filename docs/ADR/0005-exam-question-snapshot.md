# ADR 0005: Immutable exam-attempt question snapshot

## Decision

When an online attempt starts, selected question versions, ordering, marks and student-visible prompt/options are copied to `exam_attempt_questions`. Answers reference that snapshot, not a mutable question-bank row.

## Rationale

Historical evidence remains reproducible after question edits, version changes or question-bank retirement.
Phase 5B materializes paper questions and options into `exam_attempt_questions` at start; subsequent authoring changes never alter the student view.
