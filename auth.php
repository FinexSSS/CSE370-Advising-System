<?php
// Session, database connection and access checks shared by every page.

// HttpOnly keeps the session cookie away from scripts; SameSite=Lax stops other
// sites from submitting forms here with the visitor's session.
session_set_cookie_params(['httponly' => true, 'samesite' => 'Lax']);
session_start();

require_once 'DBconnect.php';

const ROLE_STUDENT = 1;
const ROLE_FACULTY = 2;
const ROLE_ADMIN = 3;

// Escape a value for safe output inside HTML.
function e($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

// The landing page for each role.
function dashboard_for($role) {
    switch ((int) $role) {
        case ROLE_STUDENT: return 'student.php';
        case ROLE_FACULTY: return 'faculty.php';
        case ROLE_ADMIN: return 'admin.php';
        default: return 'home.php';
    }
}

// Let the page continue only for a logged-in user whose role is in $roles.
function require_role(array $roles) {
    if (!isset($_SESSION['User_ID'], $_SESSION['Role_ID'])
        || !in_array((int) $_SESSION['Role_ID'], $roles, true)) {
        header("Location: home.php");
        exit;
    }
}

// Let the page continue only for a logged-in applicant.
function require_applicant() {
    if (!isset($_SESSION['Temp_ID'])) {
        header("Location: home.php");
        exit;
    }
}

// Links back to the visitor's dashboard and to log out, for the top of each page.
function nav_links() {
    $home = isset($_SESSION['Temp_ID']) ? 'new.php' : dashboard_for($_SESSION['Role_ID'] ?? 0);
    echo '<nav class="nav-links"><a href="' . e($home) . '">Dashboard</a> <a href="logout.php">Log Out</a></nav>';
}

// The student whose courses an add or drop request changes, and the page to go back to.
// Students can change only their own courses; faculty and admins name the student.
function advising_target() {
    if ((int) $_SESSION['Role_ID'] === ROLE_STUDENT) {
        return [$_SESSION['User_ID'], 'advising_panel.php'];
    }
    $student_id = (string) ($_POST['student_id'] ?? '');
    return [$student_id, 'admin_advising_panel.php?student=' . urlencode($student_id)];
}
?>
