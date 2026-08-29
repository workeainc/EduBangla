# EduBangla Phase 5 Scope — Examination Foundation

## Objective

Phase 5 designs the examination foundation for the Bangladesh high-school pilot as a modular-monolith domain. It consumes the existing School, TenantContext, academic structure, enrollment, teacher-assignment and audit foundations without duplicating them.

## In scope

- Exam types and exams
- Exam schedules connecting subject/class/section/group and teacher assignments
- Question bank: versioned MCQ, true/false, short-answer and descriptive questions
- Exam papers and selected questions
- Online attempt and answer foundation
- Manual mark-entry foundation with provenance
- Draft → scheduled → ongoing → completed → locked → published lifecycle
- Tenant-safe authorization and examination audit

## Out of scope

Result calculation, grade rules, GPA, report cards, promotion, finance, communication, analytics dashboards and government integration remain later domains. Examination exposes marks and evidence as inputs; it does not calculate final results.

## Existing concepts reused

`School`, `TenantContext`, `AcademicYear`, `AcademicClass`, `Section`, `AcademicGroup`, `Subject`, `Enrollment`, `Teacher`, `TeacherAssignment`, `SubjectAssignment` and the existing audit approach remain authoritative.

## Proposed domain boundaries

`App\Domain\Examination` owns exam lifecycle, schedules, papers, attempts and marks. `App\Domain\QuestionBank` owns question versions and options. Future Result consumes locked subject marks through a stable contract.

## Future UI and API proposals

Admin: `/schools/{school}/admin/exams`, `/create`, `/{exam}`, `/{exam}/edit`, `/{exam}/schedules`  
Teacher: `/schools/{school}/teacher/exams`, `/{exam}/marks`  
Student: `/schools/{school}/student/exams`, `/{schedule}`, `/{schedule}/attempt`

Phase 5A implements the examination foundation, question bank versioning, lifecycle foundation, manual marks foundation and Admin/Teacher listing interfaces. Online attempts, answer submission and Result remain unimplemented.

Implemented routes include Admin exam/question-bank/question screens and Teacher exam/marks screens. Schedule and paper models/actions are available as foundation; dedicated online attempt routes remain unimplemented.
