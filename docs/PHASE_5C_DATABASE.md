# Phase 5C Database

`results` stores tenant/student/exam aggregate evidence and lifecycle timestamps. `result_items` stores subject-level marks and source schedule with unique result/subject protection. Both tables use explicit tenant foreign keys and short MySQL-safe indexes.
