<?php
// Test password verification
$test_password = "admin";

// USE SINGLE QUOTES for the hash to prevent $ from being treated as variables
$stored_hash = '$2y$10$TIZI3nH39fnKfqP1/ramb.qsJiqfVc2aaBsq.im1wFugsHrOeCSLW';

if (password_verify($test_password, $stored_hash)) {
    echo "✓ Password 'admin' matches the hash!<br>";
} else {
    echo "✗ Password 'admin' does NOT match!<br>";
}

$test_password2 = "citizen";
// SINGLE QUOTES here too
$stored_hash2 = '$2y$10$SwPvfu5M13LQKMNZhNnE3Ong1IBssYyFhQHIGgZsZeCT2RWGtVRSe';

if (password_verify($test_password2, $stored_hash2)) {
    echo "✓ Password 'citizen' matches the hash!";
} else {
    echo "✗ Password 'citizen' does NOT match!";
}

// Also test direct hash generation to confirm
echo "<hr><h3>Direct Hash Test:</h3>";
$test = password_hash("admin", PASSWORD_DEFAULT);
echo "New admin hash: " . $test . "<br>";
echo "Verify with our hash: " . (password_verify("admin", $stored_hash) ? "YES" : "NO");
?>