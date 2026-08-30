# Phase 5H Security Matrix

| Actor | Access |
| --- | --- |
| Active school admin | Create/update drafts, publish, archive, view own-school timetables |
| Active teacher | Read only their own published slots through active teacher profile |
| Active student | Read only published slots matching an active enrollment and its group scope |
| Parent, staff, guest, inactive/non-member | No timetable access |

All mutation actions verify active school-admin membership. Query services verify the matching active school membership and tenant-local profile before reading. Foreign or forged identifiers resolve under `school_id` and fail without mutation.
