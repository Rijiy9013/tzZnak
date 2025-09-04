<?php
declare(strict_types=1);

return [
    'dbname' => getenv('DB_DATABASE') ?: 'catalog',
    'user' => getenv('DB_USERNAME') ?: 'app',
    'password' => getenv('DB_PASSWORD') ?: 'secret',
    'host' => getenv('DB_HOST') ?: 'mysql',
    'driver' => 'pdo_mysql',
    'charset' => 'utf8mb4',
];
