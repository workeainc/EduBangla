# ADR 0003: Attendance provenance and lifecycle

Attendance stores explicit school, academic year, class, section, teacher assignment and enrollment references. Sessions remain `draft` until finalized; finalization makes them read-only for normal teacher operations. This preserves historical meaning when current assignments or enrollments change.
