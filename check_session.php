<?php
// check_session.php
session_start();

echo "<h2>Session Information</h2>";
echo "Session ID: " . session_id() . "<br>";
echo "Session Status: " . session_status() . " (2 = active)<br><br>";

echo "<h3>Session Variables:</h3>";
if (empty($_SESSION)) {
    echo "No session variables set.<br>";
    echo "Session is empty or not started properly.";
} else {
    echo "<pre>";
    print_r($_SESSION);
    echo "</pre>";
}

echo "<h3>Cookies:</h3>";
echo "<pre>";
print_r($_COOKIE);
echo "</pre>";
?>