# Module Map

| Module | Responsibility and main entities | Dependencies | Future expansion |
| --- | --- | --- | --- |
| Identity | Users, memberships, roles, permissions, policies | School | SSO, MFA, delegated administration |
| School | Schools, settings, academic years | Identity | Tenant provisioning, regional metadata |
| Academic | Curriculum, classes, sections, groups, subjects, assignments, timetable | School, Teacher | Multiple curricula, scheduling optimizer |
| Student | Persistent school-owned students, guardians, student-guardian links, historical enrollments | Academic, Identity | Transfers, alumni, admissions workflow; future central identity linking |
| Teacher & Staff | Tenant-owned teacher/staff profiles; academic-year-scoped class/group/subject/teacher assignments | School, Academic, Identity | HR and payroll integrations; attendance and examination consume assignments as scope, not teacher role alone |
| Attendance | Sessions and student/teacher attendance | Student, Academic | QR, biometric, device adapters |

Phase 4 attendance is implemented as a separate domain. Sessions are DRAFT or FINALIZED; student rows use present, absent, late and excused statuses. Bulk recording is transactional, teacher scope is assignment-based, and finalized sessions are read-only. Percentage formula: `(present + late) / (present + absent + late + excused)`.

Phase 5A Examination, Question Bank, scheduling, paper structure and manual marks foundation are implemented. Online attempts and Result/GPA remain explicitly excluded.

Operational reports are available for daily, monthly, class/section and student views. School Admins may correct a finalized status through a transaction; the old/new status is recorded in the audit log. Students with no records render safely with zero percentage.

The dedicated admin correction screen is `/schools/{school}/admin/attendance/corrections`; it is tenant-scoped and exposes only finalized rows and status changes.
| Examination | Exams, schedules, question bank, attempts, answers, marks | Academic, Student | Proctoring, item analytics |
| Result | Assessment structures, grade rules, calculations, results, promotion | Examination, Academic | Rule versions, transcript service |
| Finance | Fee structures, invoices/student fees, payments, receipts | Student, School | Payment gateways, accounting export |
| Communication | Notices, audiences, delivery records | Identity, School | SMS, email, push providers |
| Analytics | Approved operational aggregates | All read-only domain outputs | District/national de-identified benchmarks |
| Audit | Actor and event trail | All write operations | Retention, compliance exports |

Modules communicate through explicit actions, events, and stable contracts. Cross-module reads are authorized and minimized; result calculation never resides in an examination UI component.

Phase 3 UI workflows are limited to tenant School Admin management of teacher/staff profiles, class-group applicability, subject assignments and teacher assignments, plus a teacher's own profile/assignment view. These screens are not attendance or examination functionality.
### Phase 5A Examination Foundation

Offline examination lifecycle, schedules, question banks/questions/version history, paper composition, manual marks and audited corrections. Online attempts/results remain Phase 5B scope.
