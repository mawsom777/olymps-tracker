<?php
declare(strict_types=1);

const DB_HOST = '127.0.0.1';
const DB_PORT = '3306';
const DB_NAME = 'olymp_tracker';
const DB_USER = 'stuff';
const DB_PASS = 'GdC*7gM@FX8q5sw#N73X1Dv69Qv(LuBi';

function db(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $pdo = new PDO(
            'mysql:host=' . DB_HOST . ';port=' . DB_PORT . ';dbname=' . DB_NAME . ';charset=utf8mb4',
            DB_USER, DB_PASS,
            [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]
        );
        // страховка: кодировка соединения всегда utf8mb4, независимо от настроек сервера
        $pdo->exec("SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci");
    }
    return $pdo;
}