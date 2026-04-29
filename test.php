<?php
// Temporary diagnostic file - DELETE after fixing
error_reporting(E_ALL);
ini_set('display_errors', 1);
header('Content-Type: text/plain');

echo "PHP version: " . phpversion() . "\n";
echo "mysqli available: " . (extension_loaded('mysqli') ? 'YES' : 'NO') . "\n";
echo "PDO available: " . (extension_loaded('pdo_mysql') ? 'YES' : 'NO') . "\n\n";

$host = 'sql312.infinityfree.com';
$user = 'if0_41782169';
$pass = 'McjRocks02';
$db   = 'if0_41782169_flashlearning';

echo "Attempting connection...\n";
$conn = @new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    echo "FAILED: " . $conn->connect_error . "\n";
    echo "Error code: " . $conn->connect_errno . "\n";
} else {
    echo "SUCCESS - Connected to database!\n";
    $r = $conn->query("SHOW TABLES");
    echo "Tables found: " . $r->num_rows . "\n";
    while ($row = $r->fetch_row()) {
        echo " - " . $row[0] . "\n";
    }
    $conn->close();
}
