# Phase 5B Mutation Inventory

| Component/Action | Mutation | User-controlled IDs | Boundary |
|---|---|---|---|
| Student Exams | start via exam route | exam, school route | authenticated student, tenant, enrollment, schedule window |
| Student Attempt | saveAnswer | attempt, attempt-question, answer payload | attempt owner, snapshot options, server expiry |
| Student Attempt | submit | attempt | attempt owner, active lifecycle, server expiry |
| StartExamAttempt | create + snapshot | Exam object, school context | tenant/enrollment/paper/time/duplicate constraints |
| SaveExamAnswer | upsert answer | attempt/question IDs | owner, snapshot, expiry, type validation |
| SubmitExamAttempt | submit/finalize | attempt | owner, lifecycle, expiry |
| FinalizeExamAttempt | finalize | attempt | server-side submitted-only transition |

All student question-bank/paper/mark mutations are not applicable: no student-facing write component exists. No result, GPA or Phase 5C mutation is present.
