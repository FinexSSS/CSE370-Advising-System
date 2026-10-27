<?php
require_once 'auth.php';
require_once 'schedule.php';
require_role([ROLE_STUDENT]);

$user_id = $_SESSION['User_ID'];
// Result of the last add or drop, shown once
$message = $_SESSION['message'] ?? '';
unset($_SESSION['message']);

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
        <p>Adding a course checks for a free seat, its prerequisites and clashes with your other classes.</p>
        <form action="add.php" method="post">
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
                        <input type="hidden" name="C_Code" value="<?php echo e($enrollment['C_Code']); ?>">
                        <input type="hidden" name="section" value="<?php echo e($enrollment['Section']); ?>">
                        <button type="submit" name="remove_course">Remove</button>
                    </form>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </table>
    </div>
    <?php render_schedule($conn, $user_id); ?>
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
