# Phase 5C Routes

| Route | Actor | Purpose |
|---|---|---|
| `/schools/{school}/admin/results` | School Admin | list, compute, lock, publish |
| `/schools/{school}/admin/exams/{exam}/results` | School Admin | exam-scoped result management |
| `/schools/{school}/student/results` | Student | own published results only |
| `/schools/{school}/admin/academic/grade-rules` | School Admin | manage grade rules |
| `/schools/{school}/admin/report-cards` | School Admin | generate/publish report cards |
| `/schools/{school}/student/report-cards` | Student | own published report cards |
- `GET /schools/{school}/teacher/results` — শিক্ষক assigned-scope ফলাফল
