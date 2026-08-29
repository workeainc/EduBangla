# ADR 0007: Examination and Result boundary

## Decision

Phase 5 owns exam evidence, question-level answers and locked subject marks. Result calculation, grading, GPA, report cards and promotion consume a future versioned marks contract and do not belong to Examination.

## Rationale

The boundary prevents premature grading policy decisions and allows national or school-specific result rules to evolve independently.
