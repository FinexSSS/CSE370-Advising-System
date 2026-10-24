<?php
include 'DBconnect.php';  // Ensure this path is correct

session_start();

if (!isset($_SESSION['User_ID'])) {
    header("Location: login.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['remove_course'])) {
    $user_id = $_SESSION['User_ID'];
    $course_code = $_POST['C_Code'];  
    $section = $_POST['section'];     

    // Start a transaction
    $conn->begin_transaction();

    try {
        // Prepare to delete the enrollment
        $delete_query = "DELETE FROM enrollment WHERE Student_ID = ? AND C_Code = ? AND Section = ?;";
        $stmt = $conn->prepare($delete_query);
        if ($stmt === false) {
            throw new Exception("Prepare failed: " . $conn->error);
        }

        $bind = $stmt->bind_param("sss", $user_id, $course_code, $section);
        if (!$bind) {
            throw new Exception("Bind param failed: " . $stmt->error);
        }

        $execute = $stmt->execute();
        if (!$execute) {
            throw new Exception("Execute failed: " . $stmt->error);
        }

        if ($stmt->affected_rows > 0) {
            // If the enrollment is successfully deleted, increase the seat count
            $update_seat_query = "UPDATE section SET seat = seat + 1 WHERE C_Code = ? AND Section = ?;";
            $stmt = $conn->prepare($update_seat_query);
            if (!$stmt) {
                throw new Exception("Prepare failed: " . $conn->error);
            }

            $stmt->bind_param("ss", $course_code, $section);
            $stmt->execute();
            if ($stmt->affected_rows > 0) {
                $_SESSION['message'] = "Successfully removed the course and updated seat count.";
                $conn->commit();  // Commit the transaction
            } else {
                throw new Exception("Failed to update seats, no seats were modified.");
            }
        } else {
            $_SESSION['message'] = "No such course found to remove.";
            $conn->rollback();  // Rollback the transaction
        }
    } catch (Exception $e) {
        error_log($e->getMessage());
        $_SESSION['message'] = $e->getMessage();
        $conn->rollback();  // Ensure transaction is rolled back on error
    }

    $stmt->close();
    header("Location: advising_panel.php");
    exit;
}
?>
