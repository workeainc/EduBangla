# Phase 5C Routes

| Route | Actor | Purpose |
|---|---|---|
| `/schools/{school}/admin/results` | School Admin | list, compute, lock, publish |
| `/schools/{school}/admin/exams/{exam}/results` | School Admin | exam-scoped result management |
| `/schools/{school}/student/results` | Student | own published results only |
- `GET /schools/{school}/teacher/results` — শিক্ষক assigned-scope ফলাফল
