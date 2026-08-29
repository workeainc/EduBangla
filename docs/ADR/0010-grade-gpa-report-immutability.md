# ADR 0010: Grade, GPA and Report Card Historical Immutability

Grade rules are configurable, but calculation copies the selected letter, point, pass decision and rule reference onto each ResultItem. GPA and overall status are persisted on Result. ReportCard stores a JSON presentation snapshot. Actions reject recalculation after lock/publication, so later rule edits cannot rewrite published history.

Because subjects currently have no credit field, GPA uses equal weighting across valid graded items. Promotion is deliberately outside Phase 5D.
