# ADR 0012: Finance Historical Integrity

Finance uses explicit fee assignment and invoice generation. It does not infer
or schedule recurring charges. An enrollment is the academic placement source
of truth; finance snapshots the applicable fee/category/amount data at
assignment and again on issued invoice items.

The authoritative invoice due is persisted charges minus non-reversed payment
allocations minus posted credit adjustments. Actions calculate it inside the
transaction from database facts, never from UI state. Issued invoice facts,
recorded payments, allocations and posted adjustments are immutable. An
untouched invoice may be voided; otherwise corrections create explicit
reversal/adjustment records and audit events.

All consequential finance writes, including their audits, share a database
transaction. A failure therefore rolls back both operational and audit rows.
