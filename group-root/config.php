<?php
/**
 * StudySpace configuration.
 * Environment variables (STUDYSPACE_*) override the defaults below.
 */
return [
    'db' => [
        'host'    => getenv('STUDYSPACE_DB_HOST') ?: '127.0.0.1',
        'port'    => getenv('STUDYSPACE_DB_PORT') ?: '3306',
        'name'    => getenv('STUDYSPACE_DB_NAME') ?: 'studyspace',
        'user'    => getenv('STUDYSPACE_DB_USER') ?: 'studyspace',
        'pass'    => getenv('STUDYSPACE_DB_PASS') !== false ? getenv('STUDYSPACE_DB_PASS') : 'studyspace_pw',
        'charset' => 'utf8mb4',
    ],

    'timezone' => getenv('STUDYSPACE_TZ') ?: 'Asia/Kuala_Lumpur',

    // Admin gate. Default credentials: admin / Admin@2026
    // Generate a new hash with: php -r 'echo password_hash("your-password", PASSWORD_DEFAULT);'
    'admin' => [
        'username'      => 'admin',
        'password_hash' => '$2y$10$ta/eR2.r0zTsmkk3UTelEOzBR9H2wSyCDp29Gj7WFN67HBM8S8Y16',
    ],

    // Booking rules (24h clock). Slots are bookable in 30 minute steps.
    'booking' => [
        'open_hour'     => 8,
        'close_hour'    => 20,
        'max_hours'     => 3,
        'max_days_ahead' => 60,
    ],
];
