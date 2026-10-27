<?php
require_once 'auth.php';
require_role([ROLE_STUDENT, ROLE_FACULTY, ROLE_ADMIN]);

[$user_id, $back] = advising_target();

// Drop a course and give its seat back, or explain why not.
function remove_course($conn, $user_id, $course_code, $section) {
    // Start a transaction
    $conn->begin_transaction();

    try {
        $stmt = $conn->prepare("SELECT Grade FROM enrollment WHERE Student_ID = ? AND C_Code = ? AND Section = ? FOR UPDATE");
        $stmt->bind_param("ssi", $user_id, $course_code, $section);
        $stmt->execute();
        $enrollment = $stmt->get_result()->fetch_assoc();

        if (!$enrollment) {
            $conn->rollback();
            return "No such course found to remove.";
        }
        if ($enrollment['Grade'] !== null) {
            $conn->rollback();
            return "$course_code already has a grade, so it cannot be dropped.";
        }

        $stmt = $conn->prepare("DELETE FROM enrollment WHERE Student_ID = ? AND C_Code = ? AND Section = ? LIMIT 1");
        $stmt->bind_param("ssi", $user_id, $course_code, $section);
        $stmt->execute();

        // Increase the seat count of that one section
        $stmt = $conn->prepare("UPDATE section SET seat = seat + 1 WHERE C_Code = ? AND section = ? LIMIT 1");
        $stmt->bind_param("si", $course_code, $section);
        $stmt->execute();

        $conn->commit();
        return "Successfully removed $course_code and updated the seat count.";
    } catch (mysqli_sql_exception $e) {
        $conn->rollback();
        error_log("Drop failed: " . $e->getMessage());
        return "Could not remove the course, please try again.";
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['remove_course'])) {
    $_SESSION['message'] = remove_course($conn, $user_id, (string) ($_POST['C_Code'] ?? ''), (int) ($_POST['section'] ?? 0));
}

header("Location: $back");
exit;
?>
