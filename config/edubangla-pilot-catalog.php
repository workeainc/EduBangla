<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Curated pilot academic catalog
    |--------------------------------------------------------------------------
    |
    | This is intentionally a reviewed, deployment-controlled catalog rather
    | than browser input, an import format, or a default database seeder. Set
    | these values to the pilot school's approved academic structure before
    | running the provisioning command.
    |
    */
    'catalog' => [
        'academic_year' => [
            'name' => '2026',
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'activate' => true,
        ],
        'classes' => [
            ['name' => 'Class 6', 'code' => 'C06', 'sort_order' => 6, 'status' => 'active'],
        ],
        'sections' => [
            ['class_code' => 'C06', 'name' => 'Section A', 'code' => 'A', 'capacity' => 40, 'status' => 'active'],
        ],
        'subjects' => [
            ['name' => 'Bangla', 'code' => 'BAN', 'short_name' => 'Bangla', 'status' => 'active'],
            ['name' => 'English', 'code' => 'ENG', 'short_name' => 'English', 'status' => 'active'],
            ['name' => 'Mathematics', 'code' => 'MATH', 'short_name' => 'Math', 'status' => 'active'],
        ],
        'groups' => [],
    ],
];
