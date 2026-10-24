<?php
include 'DBconnect.php';  // Ensure this path is correct

session_start();

if (!isset($_SESSION['User_ID'])) {
    header("Location: login.php");
    exit;
}

$u_id = $_SESSION['User_ID'];
$message = $_SESSION['message'] ?? '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['search'])) {
        $search_term = $_POST['search_term'];
        $search_query = "SELECT * FROM student WHERE Student_ID LIKE CONCAT('%', ?, '%')";
        $stmt = $conn->prepare($search_query);
        $stmt->bind_param("s", $search_term);
        $stmt->execute();
        $result = $stmt->get_result();
        $user_id = $result->fetch_all(MYSQLI_ASSOC);
    }
}
// Fetch available courses with sections and faculty initials
$fetch_courses_query = "SELECT c.C_Code, c.C_Name, r.Section_ID, r.seat, r.Sections, f.F_Initial FROM course c JOIN room r ON c.C_Code = r.C_Code LEFT JOIN faculty f ON f.F_Initial = r.F_Initial";
$stmt = $conn->prepare($fetch_courses_query);
$stmt->execute();
$courses = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Fetch current enrollments
$fetch_enrollments_query = "SELECT c.C_Code, c.C_Name, e.Section FROM enrollment e JOIN course c ON e.C_Code = c.C_Code WHERE e.Student_ID = ?";
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
        <h1>Advising Panel</h1>
        <?php if (!empty($message)): ?>
        <p><?php echo $message; ?></p>
        <?php endif; 
        //and check if seats empty, pre req check
        ?>
        <form method="post">
            <label for="search_term">Search User ID or Name:</label>
            <input type="text" id="search_term" name="search_term" required>
            <button type="submit" name="search">Search</button>
        </form>
        <form action="add.php" method="post">
            <label for="search_course">Search Course:</label>
            <input type="text" id="search_course" onkeyup="filterDropdown()" placeholder="Search for courses...">

            <label for="course_section">Choose a course and section:</label>
            <div class="scroll-box">
                <select name="course_section" id="course_section">
                    <?php foreach ($courses as $course): ?>
                        <option value="<?php echo $course['Section_ID']; ?>">
                            <?php echo $course['C_Code'] . " - " . $course['C_Name'] . " - Section " . $course['Sections'] . " - " . $course['F_Initial'] . " (Seats: " . $course['seat'] . ")"; ?>
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
                <td><?php echo $enrollment['C_Code']; ?></td>
                <td><?php echo $enrollment['C_Name']; ?></td>
                <td><?php echo $enrollment['Section']; ?></td>
                <td>
                    <form action="remove.php" method="post">
                        <input type="hidden" name="C_Code" value="<?php echo $enrollment['C_Code']; ?>">
                        <input type="hidden" name="section" value="<?php echo $enrollment['Section']; ?>">
                        <button type="submit" name="remove_course">Remove</button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
        </table>
    </div>
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

<?php
// Fetch schedule for enrolled courses
$schedule_query = "SELECT r.C_Code, r.day, r.s_time, r.e_time, r.room_code FROM room r JOIN enrollment e ON e.C_Code = r.C_Code AND e.section = r.sections WHERE e.Student_ID = ?;";
$schedule_stmt = $conn->prepare($schedule_query);
$schedule_stmt->bind_param("s", $user_id);
$schedule_stmt->execute();
$schedule_result = $schedule_stmt->get_result();
$schedule = [];
while ($row = $schedule_result->fetch_assoc()) {
    $schedule[$row['day']][] = $row;
}
$schedule_stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Weekly Schedule</title>
    <style>
        body {
            font-family: 'Arial', sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 0;
            color: #333;
        }

        .schedule-table {
            width: 80%;
            margin: 20px auto;
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th, td {
            border: 1px solid #ccc;
            padding: 8px;
            text-align: center;
        }

        th {
            background-color: #007bff;
            color: white;
        }

        td {
            background-color: #fff;
        }
    </style>
</head>
<body>
    <div class="schedule-table">
        <h2>Weekly Schedule</h2>
        <table>
            <tr>
                <th>Time / Day</th>
                <th>Sunday</th>
                <th>Monday</th>
                <th>Tuesday</th>
                <th>Wednesday</th>
                <th>Thursday</th>
                <th>Friday</th>
                <th>Saturday</th>
            </tr>
            <?php
            $time_slots = ['08:00:00-09:20:00', '09:30:00-10:50:00', '11:00:00-12:20:00', '12:30:00-1:50:00', '2:00:00-3:20:00', '3:30:00-4:50:00'];
            foreach ($time_slots as $time) {
                echo "<tr><td>$time</td>";
                foreach (['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'] as $day) {
                    echo "<td>";
                    if (isset($schedule[$day])) {
                        foreach ($schedule[$day] as $class) {
                            if ($time === $class['s_time'] . '-' . $class['e_time']) {
                                echo $class['C_Code']."<br>";
                                echo "Room: " . $class['room_code'] . "<br>";
                            }
                        }
                    }
                    echo "</td>";
                }
                echo "</tr>";
            }
            ?>
        </table>
    </div>
</body>
</html>
