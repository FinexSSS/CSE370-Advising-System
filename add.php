<?php
include 'DBconnect.php';  // Ensure this path is correct

session_start();

if (!isset($_SESSION['User_ID'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['User_ID'];

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_course'])) {
    $section_id = $_POST['course_section']; // Assuming this contains Section_ID
    // Start transaction
    $conn->begin_transaction();

    try {
        // Retrieve section and course code based on section ID
        $section_query = "SELECT C_Code, Section, seat FROM section WHERE section_ID = ?";
        $stmt = $conn->prepare($section_query);
        $stmt->bind_param("i", $section_id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $C_Code = $row['C_Code'];
            $section_number = $row['Section'];
            $current_seats = $row['seat'];

            if ($current_seats <= 0) {
                $_SESSION['message'] = "No seats available for this section.";
                header("Location: advising_panel.php");
                exit;
            }

            // Check if the student has already enrolled in this course
            $enrollment_query = "SELECT 1 FROM enrollment WHERE Student_ID = ? AND C_Code = ?";
            $stmt = $conn->prepare($enrollment_query);
            $stmt->bind_param("ss", $user_id, $C_Code); // Assuming C_Code is a string
            $stmt->execute();
            $enrollment_result = $stmt->get_result();

            if ($enrollment_result->num_rows > 0) {
                $_SESSION['message'] = "You have already enrolled in this course.";
                header("Location: advising_panel.php");
                exit;
            }

            // Update seats
            $update_seat_query = "UPDATE section SET seat = seat - 1 WHERE Section = ?";
            $stmt = $conn->prepare($update_seat_query);
            $stmt->bind_param("i", $section_number);
            $stmt->execute();

            // Insert into the enrollment table if the course has not been taken
            $add_enrollment_query = "INSERT INTO enrollment (Student_ID, C_Code, Section) VALUES (?, ?, ?)";
            $stmt = $conn->prepare($add_enrollment_query);
            $stmt->bind_param("sss", $user_id, $C_Code, $section_number);
            $stmt->execute();

            if ($stmt->affected_rows > 0) {
                $_SESSION['message'] = "Enrolled successfully in Section " . $section_number . "!";
                $conn->commit(); // Commit the transaction
            } else {
                $_SESSION['message'] = "Failed to enroll, please try again.";
                $conn->rollback(); // Rollback the transaction
            }
        } else {
            $_SESSION['message'] = "Failed to retrieve section details.";
            $conn->rollback(); // Rollback the transaction
        }
    } catch (Exception $e) {
        $_SESSION['message'] = "An error occurred: " . $e->getMessage();
        $conn->rollback(); // Rollback the transaction
    }

    header("Location: advising_panel.php");
    exit;
}
?>
