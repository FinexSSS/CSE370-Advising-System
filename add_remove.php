<?php
require_once 'auth.php';
require_role([ROLE_ADMIN]);

const COURSE_TYPES = ['C' => 'Core', 'G' => 'General Education', 'E' => 'Elective'];

$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    try {
        if (isset($_POST['remove_course'])) {
            $c_code = (string) ($_POST['c_code'] ?? '');

            // Removing a course that students are enrolled in would delete their records.
            $stmt = $conn->prepare("SELECT COUNT(*) AS n FROM enrollment WHERE C_Code = ?");
            $stmt->bind_param("s", $c_code);
            $stmt->execute();
            $enrolled = (int) $stmt->get_result()->fetch_assoc()['n'];

            if ($enrolled > 0) {
                $message = "$c_code cannot be removed: $enrolled student(s) are enrolled in it.";
            } else {
                // The course, its sections and its timetable go together.
                $conn->begin_transaction();
                foreach (["DELETE FROM room WHERE C_Code = ?", "DELETE FROM section WHERE C_Code = ?", "DELETE FROM course WHERE C_Code = ?"] as $query) {
                    $stmt = $conn->prepare($query);
                    $stmt->bind_param("s", $c_code);
                    $stmt->execute();
                }
                $conn->commit();
                $message = $stmt->affected_rows > 0 ? "Course removed successfully." : "That course was not found.";
            }
        } elseif (isset($_POST['assign_faculty'])) {
            $initial = (string) ($_POST['initial'] ?? '');
            $c_code = (string) ($_POST['c_code'] ?? '');
            $section = (int) ($_POST['section'] ?? 0);

            $stmt = $conn->prepare("SELECT 1 FROM faculty WHERE F_Initial = ?");
            $stmt->bind_param("s", $initial);
            $stmt->execute();
            $faculty_found = $stmt->get_result()->num_rows > 0;
            $stmt = $conn->prepare("SELECT 1 FROM section WHERE C_Code = ? AND section = ?");
            $stmt->bind_param("si", $c_code, $section);
            $stmt->execute();
            $section_found = $stmt->get_result()->num_rows > 0;

            if (!$faculty_found) {
                $message = "That faculty member was not found.";
            } elseif (!$section_found) {
                $message = "$c_code has no section $section.";
            } else {
                // Sections and the class timetable both record who teaches the section.
                $conn->begin_transaction();
                $stmt = $conn->prepare("UPDATE section SET F_initial = ? WHERE C_Code = ? AND section = ?");
                $stmt->bind_param("ssi", $initial, $c_code, $section);
                $stmt->execute();
                $stmt = $conn->prepare("UPDATE room SET F_Initial = ? WHERE C_Code = ? AND Sections = ?");
                $stmt->bind_param("ssi", $initial, $c_code, $section);
                $stmt->execute();
                $conn->commit();
                $message = "Faculty assigned successfully.";
            }
        } elseif (isset($_POST['add_course'])) {
            $c_code = strtoupper(trim($_POST['c_code'] ?? ''));
            $c_name = trim($_POST['c_name'] ?? '');
            $credit = (float) ($_POST['credit'] ?? 0);
            $prereq = strtoupper(trim($_POST['prereq'] ?? ''));
            $desc = trim($_POST['desc'] ?? '');
            $lab = isset($_POST['lab']) ? 1 : 0; // Checkbox for lab
            $seats = (int) ($_POST['seats'] ?? 0);
            $type = (string) ($_POST['type'] ?? '');
            $sections = (int) ($_POST['sections'] ?? 0);

            if (!preg_match('/^[A-Z]{3}[0-9]{3}$/', $c_code) || $c_name === '' || $credit <= 0
                || $seats < 1 || $sections < 1 || $sections > 30 || !isset(COURSE_TYPES[$type])) {
                $message = "Please enter a course code like CSE110, a name, credits, seats per section, 1 to 30 sections and a type.";
            } else {
                $conn->begin_transaction();

                // Insert or update query
                $query = "INSERT INTO course (C_Code, C_Name, Credit, Prereq, C_Desc, Lab, Seats, Type, Sections)
                          VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
                          ON DUPLICATE KEY UPDATE
                          C_Name = VALUES(C_Name),
                          Credit = VALUES(Credit),
                          Prereq = VALUES(Prereq),
                          C_Desc = VALUES(C_Desc),
                          Lab = VALUES(Lab),
                          Seats = VALUES(Seats),
                          Type = VALUES(Type),
                          Sections = VALUES(Sections)";
                $stmt = $conn->prepare($query);
                $stmt->bind_param("ssdssiisi", $c_code, $c_name, $credit, $prereq, $desc, $lab, $seats, $type, $sections);
                $stmt->execute();

                // Open any sections the course does not have yet, so students can pick them
                // in advising. Faculty are assigned to them below.
                $stmt = $conn->prepare("SELECT section FROM section WHERE C_Code = ?");
                $stmt->bind_param("s", $c_code);
                $stmt->execute();
                $existing = array_map('intval', array_column($stmt->get_result()->fetch_all(MYSQLI_ASSOC), 'section'));
                $next_id = (int) $conn->query("SELECT COALESCE(MAX(section_id), 0) AS id FROM section")->fetch_assoc()['id'];
                $stmt = $conn->prepare("INSERT INTO section (C_Code, F_initial, section, seat, section_id) VALUES (?, 'TBA', ?, ?, ?)");
                $opened = 0;
                for ($number = 1; $number <= $sections; $number++) {
                    if (!in_array($number, $existing, true)) {
                        $next_id++;
                        $stmt->bind_param("siii", $c_code, $number, $seats, $next_id);
                        $stmt->execute();
                        $opened++;
                    }
                }

                $conn->commit();
                $message = "Course added/updated successfully." . ($opened ? " Opened $opened new section(s)." : "");
            }
        }
    } catch (mysqli_sql_exception $e) {
        $conn->rollback();
        error_log("Course change failed: " . $e->getMessage());
        $message = "The change could not be saved. Please try again.";
    }
}

// Fetch courses with how many sections each has
$courses = $conn->query("SELECT c.C_Code, c.C_Name, c.Type, COUNT(s.section_id) AS sections FROM course c LEFT JOIN section s ON s.C_Code = c.C_Code GROUP BY c.C_Code, c.C_Name, c.Type ORDER BY c.C_Code")->fetch_all(MYSQLI_ASSOC);
$faculty_list = $conn->query("SELECT F_Initial FROM faculty ORDER BY F_Initial")->fetch_all(MYSQLI_ASSOC);

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Courses</title>
    <link href="style.css" rel="stylesheet">
    <style>
 body {
            font-family: 'Roboto', sans-serif;
            background: #f5f5f5;
            margin: 0;
            padding: 20px;
            color: #333;
        }
        .container {
            width: 80%;
            margin: 0 auto;
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .scroll-box {
            max-height: 300px;
            overflow-y: auto;
            border: 1px solid #ccc;
            padding: 5px;
            border-radius: 5px;
        }

        h1, h2 {
            color: #333;
        }

        .list-item {
            padding: 10px;
            border-bottom: 1px solid #eee;
            display: flex;
            justify-content: space-between;
            align-items: center;
            height: 50px; /* Fixed height */
}
        form {
            margin-top: 20px;
        }

        .list-item form {
            margin-top: 0;
        }

        .list-item input[type="submit"] {
            width: auto;
        }

        input[type="text"], input[type="number"], input[type="submit"], select {
            width: 100%;
            padding: 8px;
            margin: 10px 0;
            display: inline-block;
            border: 1px solid #ccc;
            border-radius: 4px;
            box-sizing: border-box;
        }

        input[type="submit"] {
            background-color: #4CAF50;
            color: white;
            cursor: pointer;
        }

        input[type="submit"]:hover {
            background-color: #45a049;
        }

        .button-group {
            display: flex;
            gap: 10px;
        }

        button {
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            background-color: #007bff;
            color: white;
            cursor: pointer;
        }

        button:hover {
            background-color: #0056b3;
        }

        .message {
            color: #d32f2f;
        }
    </style>
</head>
<body>
    <div class="container">
    <?php nav_links(); ?>
    <h1>Remove Courses</h1>
    <p class="message"><?php echo e($message); ?></p>
    <div class="scroll-box">
        <?php foreach ($courses as $course): ?>
        <div class="list-item">
            <?php echo e($course['C_Code'] . " - " . $course['C_Name'] . " - " . (COURSE_TYPES[$course['Type']] ?? $course['Type']) . " - " . $course['sections'] . " sections"); ?>
            <form method="post" style="display: inline;">
                <input type="hidden" name="c_code" value="<?php echo e($course['C_Code']); ?>">
                <input type="submit" name="remove_course" value="Remove">
            </form>
        </div>
        <?php endforeach; ?>
    </div>
    <form method="post">
        <h2>Add/Update a Course</h2>
        Course Code: <input type="text" name="c_code" maxlength="10" placeholder="e.g. CSE110" required><br>
        Course Name: <input type="text" name="c_name" maxlength="200" required><br>
        Credit: <input type="number" name="credit" min="0.5" step="0.5" required><br>
        Prerequisite: <input type="text" name="prereq" maxlength="30" placeholder="e.g. CSE110, MAT110"><br>
        Description: <input type="text" name="desc" maxlength="1000"><br>
        Lab: <input type="checkbox" name="lab" value="1"><br>
        Seats per section: <input type="number" name="seats" min="1" required><br>
        Type:
        <select name="type" required>
            <?php foreach (COURSE_TYPES as $code => $label): ?>
                <option value="<?php echo $code; ?>"><?php echo $label; ?></option>
            <?php endforeach; ?>
        </select><br>
        Sections: <input type="number" name="sections" min="1" max="30" required><br>
        <input type="submit" name="add_course" value="Add/Update Course">
    </form>
    <form method="post">
        <h2>Assign Faculty to Section</h2>
        Faculty:
        <select name="initial" required>
            <?php foreach ($faculty_list as $faculty): ?>
                <option><?php echo e($faculty['F_Initial']); ?></option>
            <?php endforeach; ?>
        </select><br>
        Course:
        <select name="c_code" required>
            <?php foreach ($courses as $course): ?>
                <option value="<?php echo e($course['C_Code']); ?>"><?php echo e($course['C_Code'] . " - " . $course['C_Name']); ?></option>
            <?php endforeach; ?>
        </select><br>
        Section number: <input type="number" name="section" min="1" required><br>
        <input type="submit" name="assign_faculty" value="Assign Faculty">
    </form>
    </div>
</body>
</html>
