# EduBangla Phase 5A Security Matrix

This is the authoritative inventory of exposed Phase 5A mutations. Every row is either covered by an automated test or explicitly not applicable because the operation is not exposed to that actor.

| Actor | Component | Mutation | Input | Attack boundary | Expected | DB Mutation |
|---|---|---|---|---|---|---|
| Admin | ExamManagement | save | year/type | active school ownership | reject foreign records | none |
| Admin | ExamManagement | transition | exam ID/status | school + lifecycle | reject foreign/illegal state | none |
| Admin | ExamScheduleManagement | save | academic/scope IDs | school, year, class, section, assignment | reject mismatch | none |
| Admin | QuestionBankManagement | save/toggle bank | bank/subject IDs | school ownership | reject foreign ID | none |
| Admin | QuestionBankManagement | save/toggle question | question/bank IDs | bank and school ownership | reject foreign ID | none |
| Admin | QuestionBankManagement | newVersion | question ID | school ownership | reject foreign ID | none |
| Admin | QuestionBankManagement | option CRUD | version/option IDs | version and school ownership | reject foreign ID | none |
| Admin | QuestionVersions | save version | question ID | school ownership, immutable history | reject foreign ID | none |
| Admin | QuestionVersions | option CRUD | option IDs | version and school ownership | reject foreign ID | none |
| Admin | ExamPaperManagement | add/remove/reorder | paper/version/question IDs | school + exam lifecycle | reject foreign/locked | none |
| Admin | ExamMarkCorrections | correct | mark/exam IDs | admin membership + school + reason | reject foreign/invalid | none |
| Teacher | ExamMarks | mount/save | exam/schedule/student/enrollment | own assignment and academic scope | reject mismatch/locked | none |
| Teacher | QuestionBank/QuestionVersions | all writes | — | Admin-only route | N/A — ADMIN ONLY | none |
| Teacher | ExamPaperManagement | all writes | — | Admin-only route | N/A — ADMIN ONLY | none |
| Admin | PhaseThreeManagement | teacher/staff edits | profile IDs | school ownership | reject foreign ID | none |

## Automated evidence

- `ExaminationTeacherScopeTest`: unassigned exam, unassigned/foreign schedule, student substitution, enrollment substitution, locked lifecycle; rejected writes leave mark count unchanged.
- `ExaminationSecurityMatrixTest`: foreign bank/question/version/option/exam/paper-question IDs, option CRUD, and direct component invocation; rejected writes leave records unchanged.
- Existing attendance and tenant tests cover membership, TenantContext, and Phase 4 isolation.

Teachers have only assignment-scoped exam visibility and marks entry. Question banks, questions, versions, options, papers, schedules and corrections are school-admin mutations; teacher write rows are therefore explicitly N/A rather than silently permitted.
