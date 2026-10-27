<?php
require_once 'auth.php';

// Check a password against what is stored. Accounts saved before passwords were
// hashed still hold plain text: those are accepted once and upgraded to a hash.
function password_matches($conn, $table, $id_column, $id, $password, $stored) {
	if (password_get_info((string) $stored)['algo'] !== null) {
		return password_verify($password, $stored);
	}
	if (!hash_equals((string) $stored, $password)) {
		return false;
	}
	// $table and $id_column are fixed names from this file, never user input.
	try {
		$stmt = $conn->prepare("UPDATE $table SET PASSWORD = ? WHERE $id_column = ?");
		$hash = password_hash($password, PASSWORD_DEFAULT);
		$stmt->bind_param("ss", $hash, $id);
		$stmt->execute();
	} catch (mysqli_sql_exception $e) {
		// A database made from the old advising.sql has a PASSWORD column too short
		// for a hash. Log in anyway; re-importing advising.sql fixes the column.
		error_log("Could not upgrade the password for $id: " . $e->getMessage());
	}
	return true;
}

if (!isset($_POST['email'], $_POST['password'])) {
	header("Location: home.php");
	exit;
}

$email = trim($_POST['email']);
$password = $_POST['password'];

// Students, faculty and admins
$stmt = $conn->prepare("SELECT User_ID, Role_ID, PASSWORD FROM users WHERE Email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

if ($user && password_matches($conn, 'users', 'User_ID', $user['User_ID'], $password, $user['PASSWORD'])) {
	// A new session ID on login prevents session fixation.
	session_regenerate_id(true);
	unset($_SESSION['Temp_ID']);
	$_SESSION['User_ID'] = $user['User_ID'];
	$_SESSION['Role_ID'] = (int) $user['Role_ID'];
	header("Location: " . dashboard_for($user['Role_ID']));
	exit;
}

// Applicants who registered but have not been admitted yet
$stmt = $conn->prepare("SELECT Temp_ID, PASSWORD FROM new WHERE Email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$applicant = $stmt->get_result()->fetch_assoc();

if ($applicant && password_matches($conn, 'new', 'Temp_ID', $applicant['Temp_ID'], $password, $applicant['PASSWORD'])) {
	session_regenerate_id(true);
	unset($_SESSION['User_ID'], $_SESSION['Role_ID']);
	$_SESSION['Temp_ID'] = $applicant['Temp_ID'];
	header("Location: new.php");
	exit;
}

header("Location: home.php?error=1");
exit;
?>
