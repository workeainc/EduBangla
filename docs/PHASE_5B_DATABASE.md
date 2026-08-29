# Phase 5B Database

`exam_attempts` stores lifecycle and server timestamps. `exam_attempt_questions` stores immutable prompt, marks, ordering and option snapshots. `exam_answers` stores one tenant-scoped answer per snapshot question. Foreign keys, short composite indexes and uniqueness constraints protect tenant and duplicate integrity.
