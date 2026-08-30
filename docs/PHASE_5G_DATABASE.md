# Phase 5G Database

`notices`, `notice_audiences` and `notice_deliveries` are tenant-owned and
carry non-null `school_id` with tenant indexes and restrictive deletion rules.
`NoticeAudience` stores only normalized type and existing academic-scope IDs;
it does not duplicate identity or enrollment. `NoticeDelivery` is materialized
at publication with unique `(notice_id, user_id)` recipient delivery and a
safe recipient snapshot.

The lifecycle is `draft → published → withdrawn`. Publishing stamps audience
snapshots and delivery facts inside one transaction. Published content,
audience definitions and recipient facts are immutable; withdrawal retains all
deliveries and does not rewrite history.
