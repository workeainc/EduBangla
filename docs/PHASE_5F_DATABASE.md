# Phase 5F Database

All finance tables are tenant-owned with `school_id`: `fee_categories`,
`fee_structures`, `fee_structure_items`, `student_fee_assignments`,
`invoices`, `invoice_items`, `payments`, `payment_allocations` and
`financial_adjustments`.

`FeeStructure` belongs to existing `AcademicYear` and `AcademicClass`.
Assignments reuse an existing Enrollment and snapshot the structure item,
category label, money amount and academic placement. An Invoice reuses the
same student/enrollment placement and copies its assignment facts into
immutable InvoiceItems. PaymentAllocation joins a receipt Payment to one
compatible issued Invoice. FinancialAdjustment is a credit-only event against
one invoice, with an explicit reversal self-reference.

Money uses `decimal(12,2)` in BDT. Invoice totals are cache/read fields
maintained transactionally; the authoritative balance is invoice item charges
minus active payment allocations minus posted credits. Every foreign-key
relationship is also checked by tenant-scoped Actions because individual SQL
foreign keys cannot prove composite same-school ownership.
