<?php
$password = "admin@25"; // Replace with your desired password
$hash = password_hash($password, PASSWORD_DEFAULT);
echo "Your hashed password is: " . $hash;
?>