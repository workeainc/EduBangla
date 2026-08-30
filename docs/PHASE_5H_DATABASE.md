# Phase 5H Database Notes

`timetables` stores the school, academic scope, lifecycle state, actors and timestamps. `timetable_slots` stores the recurring weekday/time slot, class scope, assignment source, teacher and a JSON publication snapshot.

Every row has `school_id`. Slot uniqueness prevents duplicate source slots within the same timetable. Application validation re-resolves all assignment and profile IDs under the school and rejects incompatible academic, class, section and group scope.

The snapshot is written only while publishing, in the same transaction as the timetable status and audit record. It preserves display facts after future source-record changes.
