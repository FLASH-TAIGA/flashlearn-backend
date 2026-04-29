<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
header('Content-Type: text/plain');

echo "PHP version: " . phpversion() . "\n";
echo "mysqli available: " . (extension_loaded('mysqli') ? 'YES' : 'NO') . "\n\n";

$host = 'tramway.proxy.rlwy.net';
$user = 'root';
$pass = 'OEiWzABkKVCoefbpSbJKxVixMdANqKAT';
$db   = 'railway';
$port = 18495;

echo "Host: $host\n";
echo "Port: $port\n";
echo "DB:   $db\n\n";
echo "Attempting connection...\n";

$conn = @new mysqli($host, $user, $pass, $db, $port);
if ($conn->connect_error) {
    echo "FAILED: " . $conn->connect_error . "\n";
} else {
    echo "SUCCESS!\n";
    $r = $conn->query("SHOW TABLES");
    echo "Tables: " . $r->num_rows . "\n";
    while ($row = $r->fetch_row()) { echo " - " . $row[0] . "\n"; }
    $conn->close();
}