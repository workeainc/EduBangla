# Module Map

| Module | Responsibility and main entities | Dependencies | Future expansion |
| --- | --- | --- | --- |
| Identity | Users, memberships, roles, permissions, policies | School | SSO, MFA, delegated administration |
| School | Schools, settings, academic years | Identity | Tenant provisioning, regional metadata |
| Academic | Curriculum, classes, sections, groups, subjects, assignments, timetable | School, Teacher | Multiple curricula, scheduling optimizer |
| Student | Persistent school-owned students, guardians, student-guardian links, historical enrollments | Academic, Identity | Transfers, alumni, admissions workflow; future central identity linking |
| Teacher & Staff | Tenant-owned teacher/staff profiles; academic-year-scoped class/group/subject/teacher assignments | School, Academic, Identity | HR and payroll integrations; attendance and examination consume assignments as scope, not teacher role alone |
| Attendance | Sessions and student/teacher attendance | Student, Academic | QR, biometric, device adapters |
| Examination | Exams, schedules, question bank, attempts, answers, marks | Academic, Student | Proctoring, item analytics |
| Result | Assessment structures, grade rules, calculations, results, promotion | Examination, Academic | Rule versions, transcript service |
| Finance | Fee structures, invoices/student fees, payments, receipts | Student, School | Payment gateways, accounting export |
| Communication | Notices, audiences, delivery records | Identity, School | SMS, email, push providers |
| Analytics | Approved operational aggregates | All read-only domain outputs | District/national de-identified benchmarks |
| Audit | Actor and event trail | All write operations | Retention, compliance exports |

Modules communicate through explicit actions, events, and stable contracts. Cross-module reads are authorized and minimized; result calculation never resides in an examination UI component.
