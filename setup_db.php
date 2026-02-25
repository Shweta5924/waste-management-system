<?php
/**
 * Waste Segregation Monitoring System
 * @author Shweta (https://github.com/Shweta5924)
 * @copyright 2026 All Rights Reserved.
 * Unauthorized copying of this file is strictly prohibited.
 */

require_once 'config/db.php';

try {
    // Connect without DB selected to allow dropping/creating
    $pdo = new PDO("mysql:host=$host;charset=$charset", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);

    $sql = file_get_contents('database.sql');
    
    // Execute the SQL script
    $pdo->exec($sql);
    
    echo "Database schema updated successfully.\n";
    echo "Views and Tables created.\n";
    
} catch (PDOException $e) {
    echo "Error initializing database: " . $e->getMessage() . "\n";
}
?>
