<?php
/**
 * BlackStrike — configuration
 *
 * The defaults below match a fresh XAMPP install (MySQL/MariaDB user "root"
 * with no password). Change them if your XAMPP MySQL uses a password.
 */
return [
    'db' => [
        'driver'   => getenv('BLACKSTRIKE_DB_DRIVER') ?: 'mysql',   // 'mysql' for XAMPP (MariaDB). GitHub Codespaces sets 'sqlite'.
        'host'     => '127.0.0.1',
        'port'     => 3306,
        'database' => 'jn_guns',
        'username' => 'root',
        'password' => '',
        'sqlite_path' => __DIR__ . '/storage/jn_guns.sqlite',
    ],
    'app' => [
        'timezone'     => 'Asia/Manila',
        'session_name' => 'jn_guns_session',
        'debug'        => false,       // true shows detailed PHP errors (development only)
    ],
];
