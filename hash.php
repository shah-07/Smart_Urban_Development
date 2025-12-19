<?php
echo "admin hash: " . password_hash('admin', PASSWORD_DEFAULT);
echo "<br>";
echo "citizen hash: " . password_hash('citizen', PASSWORD_DEFAULT);
?>