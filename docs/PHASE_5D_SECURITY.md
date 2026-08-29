# Phase 5D Security

Every grade rule, result and report-card action verifies school ownership. Admin mutations are tenant-admin only; student report cards are limited to the authenticated student's published cards. Server actions reject locked/published recalculation, missing grade coverage, draft report generation and duplicate report cards. Teacher-facing result access remains assignment-scoped where provided by existing routes.
