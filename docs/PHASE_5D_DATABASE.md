# Phase 5D Database

`grade_rules` stores ordered, active school rules. `result_items` stores applied rule, letter, point and pass snapshot; `results` stores GPA and overall status. `report_cards` uniquely references a Result and stores a presentation snapshot. Published records are never recalculated or mutated by Phase 5D actions.
