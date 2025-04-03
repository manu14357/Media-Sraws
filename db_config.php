<?php
// db_config.php
$servername = "localhost";
$username = "u856685760_Sraws_Media"; // Default MySQL username
$password = "Media@sraws1226"; // Default MySQL password (empty for localhost)
$database = "u856685760_media"; // Database name

// Create connection
$conn = new mysqli($servername, $username, $password, $database);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
