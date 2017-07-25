<?php
return [
    'createEmployee' => [
        'type' => 2,
        'description' => 'create an employee',
    ],
    'deleteEmployee' => [
        'type' => 2,
        'description' => 'delete employee',
    ],
    'manager' => [
        'type' => 1,
        'children' => [
            'createEmployee',
        ],
    ],
    'admin' => [
        'type' => 1,
        'children' => [
            'deleteEmployee',
            'manager',
        ],
    ],
];
