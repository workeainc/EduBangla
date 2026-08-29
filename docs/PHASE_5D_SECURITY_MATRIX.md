# Phase 5D Security Matrix

| Surface | Tenant check | Lifecycle check | Historical protection |
|---|---|---|---|
| GradeRule UI | school-admin middleware and scoped queries | active toggle only in tenant | applied rules are copied to ResultItem |
| Grade calculation | result and rules share school | computed only | locked/published rejected |
| Report generation | result/report school equality | locked/published graded only | JSON snapshot |
| Report publication | report school equality | generated only | published timestamp and snapshot |
| Student reports | student middleware and user/student scope | published only | no mutation methods |
| Admin report detail | school-admin middleware, route school/report equality | read-only | snapshot is rendered |
| Teacher report cards | teacher middleware, teacher_id on assigned schedules | locked/published only | no mutation methods |

## Direct malicious-ID coverage

All mutation components scope incoming IDs by the current school before invoking domain actions. Grade-rule overlap, foreign result/report IDs, locked/published recalculation, duplicate report generation and student ownership are covered by feature tests; rejected actions are expected to leave persisted state unchanged. Teacher views use schedule teacher assignments and cannot query unrelated class, section, subject or year records.
