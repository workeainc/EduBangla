# Phase 5F Scope — Finance Foundation

Phase 5F provides an explicit, tenant-scoped finance foundation. It reuses the
existing Student identity and Enrollment academic placement; finance creates no
parallel student, academic-year, class or section identity.

Included: fee categories, academic-year/class fee structures and items,
enrollment fee assignments, invoices/items, manual receipt payments and
allocations, credit-only financial adjustments, server-authoritative due
calculation, audit events, school-admin operations and student own-record
read-only visibility.

Financial documents are historical. Future fee-structure/category changes do
not rewrite assignments or issued invoice items. Recorded payments,
allocations and posted adjustments are not edited or deleted; corrections use
an invoice void when untouched, or an explicit payment/adjustment reversal.

Excluded: recurring or monthly billing, BillingPeriod, installments, payment
gateways, refunds, taxes, ledger/accounting, late fees, multi-currency,
teacher finance, GPA/result logic and promotion logic. UI is an adapter only;
it has no financial calculation or lifecycle authority.
