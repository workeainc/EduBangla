# ADR 0008: Question option integrity

Phase 5A options are tenant-scoped and attached to immutable question versions. MCQ versions require at least two options and exactly one correct option before publication/use; True/False versions use only `true` and `false`. Option ordering is persisted with `sort_order`. Short-answer and descriptive questions do not accept options. All mutations resolve the version and option through the active school.
