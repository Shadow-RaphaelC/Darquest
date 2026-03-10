<?php

// -------------------------
// Database connection
// -------------------------
function get_pdo(): PDO|false
{
    $host = '158.69.48.57';
    $db = 'darquest5';
    $user = 'darquest5';
    $pass = '68u7hFcys9A3';
    $charset = 'utf8';

    $dsn = "mysql:host=$host;dbname=$db;charset=$charset";

    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ];

    try {
        return new PDO($dsn, $user, $pass, $options);
    } catch (\PDOException $e) {
        error_log('get_pdo error: ' . $e->getMessage());
        return false;
    }
}