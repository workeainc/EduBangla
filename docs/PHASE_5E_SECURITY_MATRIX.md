# Phase 5E Security Matrix
| Surface | Protection | Expected |
|---|---|---|
| Evaluation | source enrollment + published report + active rule same school | reject foreign/missing evidence |
| Approval | eligible status and pass decision | reject invalid transition |
| Application | approved status, target scope, no active target enrollment | atomic create or rollback |
| Admin Livewire | school-scoped IDs and admin middleware | reject malicious IDs |
| Teacher/Student | read-only scoped views | no mutation capability |
| PromotionRules | admin middleware; every ID reloaded by school_id | foreign/unauthorized toggle rejected |
| Promotions | admin middleware; every action reloads by school_id | invalid lifecycle and foreign IDs rejected |

Required direct-action and Livewire checks cover evaluation, approval, application, cancellation and rule activation; all rejected transitions assert persisted state remains unchanged.
PromotionRule create/edit/show routes use optional model binding with tenant checks; promotion create/edit/show routes similarly reject foreign bound records.
