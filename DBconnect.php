<?php
// Database connection. The defaults match a fresh XAMPP install; set DB_HOST,
// DB_PORT, DB_USER, DB_PASS or DB_NAME in the environment to override them.
$servername = getenv('DB_HOST') ?: "localhost";
$port = (int) (getenv('DB_PORT') ?: 3306);
$username = getenv('DB_USER') ?: "root";
$password = getenv('DB_PASS') !== false ? getenv('DB_PASS') : "";
$dbname = getenv('DB_NAME') ?: "advising";

//creating connection
try {
	$conn = new mysqli($servername, $username, $password, $dbname, $port);
	$conn->set_charset('utf8mb4');
} catch (mysqli_sql_exception $e) {
	// Details go to the server log, not to the visitor.
	error_log("Database connection failed: " . $e->getMessage());
	http_response_code(500);
	die("The database is not available right now. Please try again later.");
}

?>
