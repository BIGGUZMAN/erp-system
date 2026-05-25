<?php
try {
    $pdo = new PDO('mysql:host=127.0.0.1;port=3306', 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Check if database 'erp' exists
    $stmt = $pdo->query("SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = 'erp'");
    $exists = $stmt->fetch();
    
    if (!$exists) {
        $pdo->exec("CREATE DATABASE erp");
        echo "DATABASE_CREATED\n";
    } else {
        echo "DATABASE_EXISTS\n";
    }
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
