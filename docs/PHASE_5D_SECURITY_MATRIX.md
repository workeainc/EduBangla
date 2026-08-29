# Phase 5D Security Matrix

| Surface | Tenant check | Lifecycle check | Historical protection |
|---|---|---|---|
| GradeRule UI | school-admin middleware and scoped queries | active toggle only in tenant | applied rules are copied to ResultItem |
| Grade calculation | result and rules share school | computed only | locked/published rejected |
| Report generation | result/report school equality | locked/published graded only | JSON snapshot |
| Report publication | report school equality | generated only | published timestamp and snapshot |
| Student reports | student middleware and user/student scope | published only | no mutation methods |
