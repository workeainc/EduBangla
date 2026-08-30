# Phase 5G Security Matrix

| Surface | Protection | Expected |
| --- | --- | --- |
| Admin notices | active school-admin membership, tenant-scoped Actions | only own-school draft/publish/withdraw |
| Audience resolution | server resolver, normalized allowed types, scoped academic reload | no forged/foreign scope or arbitrary recipient |
| Recipient inbox | active teacher/student/staff membership plus `delivery.user_id` | own deliveries only |
| Mark read | authenticated recipient reloaded under active school | peer/foreign IDs rejected; first timestamp retained |
| Publication | transaction + audit in the same transaction | failure leaves no delivery/audit/status snapshot |
| Historical records | model guards and lifecycle Actions | published/withdrawn notices retain content, audience and delivery history |

Parent/guardian routes are absent because Guardian has no authenticated
tenant-membership link. Livewire only validates scalar input and delegates to
tenant-scoped Actions; browser values never decide tenant, recipient or
audience authority.
