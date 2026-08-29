# Phase 5B Routes

| Method | Route | Middleware | Component | Actor |
|---|---|---|---|---|
| GET | `/schools/{school}/student/exams` | auth, tenant.context | Student\Exams | Student |
| GET | `/schools/{school}/student/exams/{exam}/start` | auth, tenant.context | Student\Exams | Student |
| GET | `/schools/{school}/student/attempts/{attempt}` | auth, tenant.context | Student\Attempt | Owning Student |

Ownership and tenant checks are enforced again in component mounts and domain actions.
