# Phase 5E Database
`promotion_rules` stores tenant-scoped source/target class thresholds. `promotions` records source and target academic scope, lifecycle, eligibility basis and target enrollment. Unique school/student/source-year and foreign keys prevent duplicate or cross-tenant progression.

Routes expose list/create/show/edit promotion screens and a dedicated promotion-rule management screen; applied records are rendered read-only.
