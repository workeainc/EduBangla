# Phase 5B Security Matrix

| Operation | Attack | Expected |
|---|---|---|
| Start attempt | foreign exam/school or missing enrollment | reject |
| Start attempt | duplicate active attempt | reject |
| Save answer | foreign attempt/question or invalid option | reject |
| Save answer | expired/submitted attempt | reject |
| Submit | another student's attempt | reject |
| Snapshot | mutate source question/paper after start | snapshot unchanged |
| Finalization | submitted -> finalized; other states | only submitted allowed |

Automated coverage is in `tests/Feature/OnlineExamAttemptTest.php`.

The suite covers valid start/answer/submit, duplicate and time-window rejection, immutable prompt snapshots, submitted/expired lifecycle rejection, student ownership, and direct Livewire foreign-attempt rejection.
