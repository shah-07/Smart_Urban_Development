<?php
// test_database.php
require_once 'config.php';

echo "<h2>Testing Database Connection</h2>";

// Test 1: Basic connection
echo "1. PDO Connection: " . ($pdo ? "✓ SUCCESS" : "✗ FAILED") . "<br>";

// Test 2: Check users table
try {
    $stmt = $pdo->query("SHOW TABLES LIKE 'users'");
    $tableExists = $stmt->fetch();
    echo "2. Users table exists: " . ($tableExists ? "✓ YES" : "✗ NO") . "<br>";
    
    if ($tableExists) {
        // Test 3: Check admin user
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = 'admin'");
        $stmt->execute();
        $admin = $stmt->fetch(PDO::FETCH_ASSOC);
        
        echo "3. Admin user found: " . ($admin ? "✓ YES" : "✗ NO") . "<br>";
        
        if ($admin) {
            echo "<pre>Admin record: ";
            print_r($admin);
            echo "</pre>";
            
            // Test 4: Verify password
            echo "4. Password verification ('admin'): ";
            echo password_verify('admin', $admin['password']) ? "✓ MATCHES" : "✗ NO MATCH";
            echo "<br>";
            echo "Hash length: " . strlen($admin['password']) . " chars<br>";
            echo "Hash preview: " . substr($admin['password'], 0, 20) . "...<br>";
        }
        
        // Test 5: Check all users
        echo "<br>5. All users in database:<br>";
        $stmt = $pdo->query("SELECT id, username, role, LEFT(password, 20) as hash_preview FROM users");
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo "<pre>";
        print_r($users);
        echo "</pre>";
    }
} catch(PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>