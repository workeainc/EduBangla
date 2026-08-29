# Phase 5C Security

Result actions require school ownership and admin membership. Student visibility is limited to own published results through `ResultPolicy`; published and locked rows have no mutation action. Computation consumes only validated `ExamMark` rows; submitted online attempts without finalized scored evidence are not treated as marks.

The student result screen independently scopes by authenticated student and published status; changing route school or result IDs cannot expose another tenant's data.
