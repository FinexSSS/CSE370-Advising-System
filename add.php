<?php
require_once 'auth.php';
require_once 'schedule.php';
require_role([ROLE_STUDENT, ROLE_FACULTY, ROLE_ADMIN]);

[$user_id, $back] = advising_target();

// Course codes from a comma-separated list such as "CSE110, MAT110". Empty
// entries and the text "Null" mean no course.
function course_list($text) {
    $codes = array_map(fn($code) => strtoupper(trim($code)), explode(',', (string) $text));
    return array_values(array_filter($codes, fn($code) => $code !== '' && $code !== 'NULL' && $code !== 'NONE'));
}

// Enroll the student in a section, or explain why not.
function add_course($conn, $user_id, $section_id) {
    // Retrieve section and course code based on section ID
    $stmt = $conn->prepare("SELECT s.C_Code, s.section, c.Prereq FROM section s JOIN course c ON c.C_Code = s.C_Code WHERE s.section_id = ?");
    $stmt->bind_param("i", $section_id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    if (!$row) {
        return "Failed to retrieve section details.";
    }
    $C_Code = $row['C_Code'];
    $section_number = (int) $row['section'];

    $stmt = $conn->prepare("SELECT C_Completed FROM student WHERE Student_ID = ?");
    $stmt->bind_param("s", $user_id);
    $stmt->execute();
    $student = $stmt->get_result()->fetch_assoc();
    if (!$student) {
        return "Student not found.";
    }

    // Check if the student has already enrolled in this course
    $stmt = $conn->prepare("SELECT 1 FROM enrollment WHERE Student_ID = ? AND C_Code = ?");
    $stmt->bind_param("ss", $user_id, $C_Code);
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) {
        return "Already enrolled in $C_Code.";
    }

    // Prerequisites count as done if listed as completed or passed here with a grade.
    $stmt = $conn->prepare("SELECT C_Code FROM enrollment WHERE Student_ID = ? AND Grade IS NOT NULL AND Grade <> 'F'");
    $stmt->bind_param("s", $user_id);
    $stmt->execute();
    $passed = array_merge(course_list($student['C_Completed']), array_column($stmt->get_result()->fetch_all(MYSQLI_ASSOC), 'C_Code'));
    $missing = array_diff(course_list($row['Prereq']), array_map('strtoupper', $passed));
    if ($missing) {
        return "$C_Code needs " . implode(' and ', $missing) . " to be completed first.";
    }

    // The new section must not overlap a class the student is taking now (no grade yet).
    $stmt = $conn->prepare("SELECT Day, s_time, e_time FROM room WHERE C_Code = ? AND Sections = ?");
    $stmt->bind_param("si", $C_Code, $section_number);
    $stmt->execute();
    $new_classes = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt = $conn->prepare("SELECT r.C_Code, r.Day, r.s_time, r.e_time FROM room r JOIN enrollment e ON e.C_Code = r.C_Code AND e.Section = r.Sections WHERE e.Student_ID = ? AND e.Grade IS NULL");
    $stmt->bind_param("s", $user_id);
    $stmt->execute();
    foreach ($stmt->get_result()->fetch_all(MYSQLI_ASSOC) as $taken) {
        foreach ($new_classes as $class) {
            if ($class['Day'] === $taken['Day']
                && classes_clash(class_span($class['s_time'], $class['e_time']), class_span($taken['s_time'], $taken['e_time']))) {
                return "$C_Code section $section_number clashes with {$taken['C_Code']} on {$taken['Day']} at " . format_minutes(class_minutes($taken['s_time'])) . ".";
            }
        }
    }

    $conn->begin_transaction();
    try {
        // Take a seat only if one is left, in the same statement, so two students
        // cannot both get the last seat.
        $stmt = $conn->prepare("UPDATE section SET seat = seat - 1 WHERE section_id = ? AND seat > 0");
        $stmt->bind_param("i", $section_id);
        $stmt->execute();
        if ($stmt->affected_rows === 0) {
            $conn->rollback();
            return "No seats available for this section.";
        }

        $stmt = $conn->prepare("INSERT INTO enrollment (Student_ID, C_Code, Section) VALUES (?, ?, ?)");
        $stmt->bind_param("ssi", $user_id, $C_Code, $section_number);
        $stmt->execute();

        $conn->commit();
        return "Enrolled successfully in $C_Code section $section_number!";
    } catch (mysqli_sql_exception $e) {
        $conn->rollback();
        error_log("Enrollment failed: " . $e->getMessage());
        return "Failed to enroll, please try again.";
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_course'])) {
    $_SESSION['message'] = add_course($conn, $user_id, (int) ($_POST['course_section'] ?? 0));
}

header("Location: $back");
exit;
?>
