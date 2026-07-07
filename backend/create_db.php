<?php
try {
    $pdo = new PDO('mysql:host=127.0.0.1', 'root', 'Airpayun2004');
    $pdo->exec('CREATE DATABASE IF NOT EXISTS sistem_kampus');
    echo "DB created successfully with Airpayun2004\n";
} catch (PDOException $e) {
    echo "Error 1: " . $e->getMessage() . "\n";
    try {
        $pdo = new PDO('mysql:host=127.0.0.1', 'root', 'Airpayun2004#');
        $pdo->exec('CREATE DATABASE IF NOT EXISTS sistem_kampus');
        echo "DB created successfully with Airpayun2004#\n";
    } catch (PDOException $e2) {
        echo "Error 2: " . $e2->getMessage() . "\n";
    }
}
