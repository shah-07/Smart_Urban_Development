<?php
// setup_passwords.php - Run this once to set up proper password hashes
require_once 'config.php';

$users = [
    ['admin', 'admin', 'admin'],
    ['citizen', 'citizen', 'citizen']
];

try {
    foreach ($users as $user) {
        $username = $user[0];
        $plain_password = $user[1];
        $role = $user[2];
        
        // Generate proper password hash
        $hashed_password = password_hash($plain_password, PASSWORD_DEFAULT);
        
        // Check if user exists
        $check_stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
        $check_stmt->execute([$username]);
        $existing_user = $check_stmt->fetch();
        
        if ($existing_user) {
            // Update existing user
            $stmt = $pdo->prepare("UPDATE users SET password = ?, role = ? WHERE username = ?");
            $stmt->execute([$hashed_password, $role, $username]);
            echo "Updated user: $username<br>";
        } else {
            // Insert new user
            $stmt = $pdo->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, ?)");
            $stmt->execute([$username, $hashed_password, $role]);
            echo "Created user: $username<br>";
        }
        
        echo "Password for $username: $plain_password -> Hash: $hashed_password<br><br>";
    }
    
    echo "Password setup completed successfully!";
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>