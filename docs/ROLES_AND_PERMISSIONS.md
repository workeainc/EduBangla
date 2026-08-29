# Roles and Permission Matrix

Attendance teachers operate only on their own assignments; School Admins operate within their active school. Cross-school IDs are rejected server-side.

All permissions are evaluated with an active school membership. A school-level role is restricted to its own `school_id`; Super Admin platform access is explicitly audited and not a shortcut for ordinary school staff.

| Role | View | Create / update | Delete / publish | Limits |
| --- | --- | --- | --- | --- |
| Super Admin | Platform schools, system configuration, audited aggregates | Provision schools, platform roles/settings | Controlled platform administration | No routine school-data editing without explicit support authority |
| School Admin | All own-school operational records | Own-school teacher/staff profiles, class-group, subject and teacher assignments | Own-school records; publish results/notices | Never another school |
| Teacher | Own profile and only own assignment records | Assigned attendance, questions, marks, materials in future modules | Submit marks; no final result publication | Role is insufficient: assignment scope and tenant are required |
| Student | Own profile, attendance, results, routines, notices, allowed exams | Own permitted exam answers/profile requests | Submit own attempt only | Own identity only |
| Parent | Linked children's attendance, results, routines, dues, notices | Limited contact/profile requests | None | Linked children only |
| Accountant | Own-school fees, dues, payments, receipts | Fee/payment records | Void only with audited authority | No academic or unrelated PII access |
| Librarian | Own-school library-facing student/staff identity subset | Library transactions | Library transaction reversal | No results/finance access |
| Staff | Assigned operational data and notices | Assigned tasks only | None unless a specific permission grants it | Minimum necessary access |

Permissions use granular verbs such as `students.view`, `students.manage`, `attendance.record`, `marks.enter`, `results.publish`, `fees.collect`, and `notices.publish`. Policies enforce record ownership even when a role permission is present.
