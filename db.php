<?php
$conn = new mysqli("sql303.infinityfree.com", "if0_42319140", "clearance4MMCL", "if0_42319140_clearance_db");

// Stop and show an error if the connection fails
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
