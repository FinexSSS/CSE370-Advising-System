<?php
require_once 'auth.php';
require_once 'schedule.php';
require_role([ROLE_FACULTY, ROLE_ADMIN]);

// Result of the last add or drop, shown once
$message = $_SESSION['message'] ?? '';
unset($_SESSION['message']);
$search_results = null;
$student = null;

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['search'])) {
    $search_term = trim($_POST['search_term'] ?? '');
    $search_query = "SELECT s.Student_ID, u.name, s.CGPA FROM student s JOIN users u ON u.User_ID = s.Student_ID WHERE s.Student_ID LIKE CONCAT('%', ?, '%') OR u.name LIKE CONCAT('%', ?, '%') ORDER BY s.Student_ID";
    $stmt = $conn->prepare($search_query);
    $stmt->bind_param("ss", $search_term, $search_term);
    $stmt->execute();
    $search_results = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

// The student being advised, chosen from the search results
if (isset($_GET['student'])) {
    $chosen = (string) $_GET['student'];
    $stmt = $conn->prepare("SELECT s.Student_ID, u.name, s.CGPA, s.C_Completed FROM student s JOIN users u ON u.User_ID = s.Student_ID WHERE s.Student_ID = ?");
    $stmt->bind_param("s", $chosen);
    $stmt->execute();
    $student = $stmt->get_result()->fetch_assoc();
}

if ($student) {
    $user_id = $student['Student_ID'];

    // Fetch available courses with sections and faculty initials
    $fetch_courses_query = "SELECT c.C_Code, c.C_Name, r.seat, r.section, r.section_id, r.F_initial FROM course c JOIN section r ON c.C_Code = r.C_Code ORDER BY c.C_Code, r.section";
    $stmt = $conn->prepare($fetch_courses_query);
    $stmt->execute();
    $courses = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    // Fetch current enrollments
    $fetch_enrollments_query = "SELECT c.C_Code, c.C_Name, e.Section, e.Grade FROM enrollment e JOIN course c ON e.C_Code = c.C_Code WHERE e.Student_ID = ?";
    $stmt = $conn->prepare($fetch_enrollments_query);
    $stmt->bind_param("s", $user_id);
    $stmt->execute();
    $enrollments = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Advising Panel</title>
    <link href="style.css" rel="stylesheet">
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f0f2f5;
            margin: 0;
            padding: 20px;
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
            max-height: 150px;
            overflow-y: auto;
            border: 1px solid #ccc;
            padding: 5px;
            border-radius: 5px;
        }

        select, input[type="text"] {
            width: 100%;
            padding: 8px;
            margin-top: 5px;
            border-radius: 4px;
            border: 1px solid #ccc;
            background: white;
            box-sizing: border-box;
        }

        button {
            background-color: #007BFF;
            color: white;
            padding: 10px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
        }

        button:hover {
            background-color: #0056b3;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        th, td {
            padding: 8px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }

        th {
            background-color: #007BFF;
            color: white;
        }
    </style>
</head>
<body>
    <div class="container">
        <?php nav_links(); ?>
        <h1>Advising Panel</h1>
        <?php if (!empty($message)): ?>
        <p><?php echo e($message); ?></p>
        <?php endif; ?>
        <form method="post" action="admin_advising_panel.php">
            <label for="search_term">Search Student ID or Name:</label>
            <input type="text" id="search_term" name="search_term" required>
            <button type="submit" name="search">Search</button>
        </form>

        <?php if ($search_results !== null): ?>
            <?php if (empty($search_results)): ?>
                <p>No students found.</p>
            <?php else: ?>
            <table>
                <tr>
                    <th>Student ID</th>
                    <th>Name</th>
                    <th>CGPA</th>
                    <th>Action</th>
                </tr>
                <?php foreach ($search_results as $result): ?>
                <tr>
                    <td><?php echo e($result['Student_ID']); ?></td>
                    <td><?php echo e($result['name']); ?></td>
                    <td><?php echo e($result['CGPA']); ?></td>
                    <td><a href="admin_advising_panel.php?student=<?php echo urlencode($result['Student_ID']); ?>">Advise</a></td>
                </tr>
                <?php endforeach; ?>
            </table>
            <?php endif; ?>
        <?php elseif (isset($_GET['student']) && !$student): ?>
            <p>Student not found.</p>
        <?php endif; ?>

        <?php if ($student && $search_results === null): ?>
        <h2>Advising <?php echo e($student['name']); ?> (<?php echo e($student['Student_ID']); ?>)</h2>
        <p>Completed courses: <?php echo e($student['C_Completed'] ?? 'None'); ?></p>
        <form action="add.php" method="post">
            <input type="hidden" name="student_id" value="<?php echo e($user_id); ?>">
            <label for="search_course">Search Course:</label>
            <input type="text" id="search_course" onkeyup="filterDropdown()" placeholder="Search for courses...">

            <label for="course_section">Choose a course and section:</label>
            <div class="scroll-box">
                <select name="course_section" id="course_section">
                    <?php foreach ($courses as $course): ?>
                        <option value="<?php echo (int) $course['section_id']; ?>">
                            <?php echo e($course['C_Code'] . " - " . $course['C_Name'] . " - Section " . $course['section'] . " - " . $course['F_initial'] . " (Seats: " . $course['seat'] . ")"); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button type="submit" name="add_course">Add Course</button>
        </form>

        <h2>Current Courses</h2>
        <table>
            <tr>
                <th>Course Code</th>
                <th>Course Name</th>
                <th>Section</th>
                <th>Action</th>
            </tr>
            <?php foreach ($enrollments as $enrollment): ?>
            <tr>
                <td><?php echo e($enrollment['C_Code']); ?></td>
                <td><?php echo e($enrollment['C_Name']); ?></td>
                <td><?php echo e($enrollment['Section']); ?></td>
                <td>
                    <?php if ($enrollment['Grade'] !== null): ?>
                        Grade: <?php echo e($enrollment['Grade']); ?>
                    <?php else: ?>
                    <form action="remove.php" method="post">
                        <input type="hidden" name="student_id" value="<?php echo e($user_id); ?>">
                        <input type="hidden" name="C_Code" value="<?php echo e($enrollment['C_Code']); ?>">
                        <input type="hidden" name="section" value="<?php echo e($enrollment['Section']); ?>">
                        <button type="submit" name="remove_course">Remove</button>
                    </form>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </table>
        <?php endif; ?>
    </div>
    <?php if ($student && $search_results === null) { render_schedule($conn, $user_id); } ?>
    <script>
        function filterDropdown() {
            var input, filter, select, options, txtValue;
            input = document.getElementById("search_course");
            filter = input.value.toUpperCase();
            select = document.getElementById("course_section");
            options = select.getElementsByTagName("option");

            for (let i = 0; i < options.length; i++) {
                txtValue = options[i].textContent || options[i].innerText;
                if (txtValue.toUpperCase().indexOf(filter) > -1) {
                    options[i].style.display = "";
                } else {
                    options[i].style.display = "none";
                }
            }
        }

    </script>
</body>
</html>
