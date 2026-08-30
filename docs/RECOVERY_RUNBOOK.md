# Pilot Recovery Runbook

This runbook is for a **disposable MySQL rehearsal only**. It does not contain
production credentials, a production target name or operational approval to
restore a live school database.

## Preconditions

- Obtain an approved backup artifact and its integrity metadata.
- Identify a new, explicitly disposable target schema.
- Confirm the source and restore target are not production systems.
- Record operator, timestamps, source backup identifier and expected tenant
  counts without placing secrets in this document or audit evidence.

## Rehearsal sequence

1. Create representative disposable data in at least two school tenants,
   including an active membership and one audit event.
2. Take a consistent logical MySQL backup.
3. Restore only into a newly created disposable schema.
4. Verify table/schema count, migrations, foreign-key metadata and indexes.
5. Compare source/restore counts by tenant for schools, memberships and
   representative domain records.
6. Verify audit actor, target, action and before/after payload preservation.
7. Verify published historical snapshots for report cards, finance, notices
   and timetable when representative source data is available.
8. Run application smoke checks and the suite against an isolated MySQL test
   database; never point automated `migrate:fresh` at a recovery target.
9. Record RPO/RTO observations and limitations, then remove the disposable
   restore schema.

## 2026-08-30 disposable rehearsal evidence

- Source: disposable `edubangla_test` MySQL schema.
- Restore target: separately created disposable schema, removed after checks.
- Schema tables: source `67`; restored `67`.
- Representative tenant records: source/restored schools `2/2`.
- Representative audit records: source/restored audit rows `1/1`.
- School-local membership and audit `school_id` values were present after
  restore.

This rehearsal proves the documented logical-backup/restore path at small
scale. It does not establish production-scale RPO/RTO, binary-file recovery,
external-provider recovery or production restore authorization.
