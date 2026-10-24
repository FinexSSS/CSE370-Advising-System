<?php
include 'DBconnect.php';  // Make sure this path is correct

session_start();

// Check if the user is logged in and a student ID is available
if (!isset($_SESSION['User_ID'])) {
    // Redirect to login page if not logged in
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['User_ID'];

// Fetch personal data
$personal_query = "SELECT User_ID, name, Email FROM users WHERE User_ID = ?";
$stmt = $conn->prepare($personal_query);
$stmt->bind_param("s", $user_id);
$stmt->execute();
$personalResult = $stmt->get_result()->fetch_assoc();

// Fetch list of courses
$courses_query = "SELECT c.C_Code, c.C_Name, c.Credit, c.Type FROM course c;";
$stmt = $conn->prepare($courses_query);
$stmt->execute();
$courseResult = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$sortedCourses = ['Core' => [], 'Gen Ed' => [], 'Elective' => []];

// Sort courses into categories
foreach ($courseResult as $course) {
    if ($course['Type'] == 'C') {
        $sortedCourses['Core'][] = $course;
    } elseif ($course['Type'] == 'G') {
        $sortedCourses['Gen Ed'][] = $course;
    } elseif ($course['Type'] == 'E') {
        $sortedCourses['Elective'][] = $course;
    }
}

// Fetch class schedule
$schedule_query = "SELECT 
e.Student_ID,
c.C_Code,
c.C_Name,
e.Section,
r.room_code,
r.Day,
r.s_time AS start_time,
r.e_time AS end_time
FROM enrollment e
JOIN course c ON e.C_Code = c.C_Code
JOIN room r ON e.C_Code = r.C_Code AND e.Section = r.Sections
WHERE e.Student_ID = ?;  -- Replace '?' with the specific User_ID you're querying for
";
$stmt = $conn->prepare($schedule_query);
$stmt->bind_param("s", $user_id);
$stmt->execute();
$scheduleResult = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
            background-color: #f4f4f4;
        }
        header, section {
            background-color: white;
            padding: 20px;
            margin-bottom: 10px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .courses-container {
            display: flex;
            justify-content: space-between;
            gap: 10px;
            flex-wrap: wrap;
        }
        .course-box {
            flex: 1;
            min-width: 30%;
            background-color: #fff;
            padding: 10px;
            border-radius: 5px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.2);
            margin-bottom: 10px;
        }
        ul {
            list-style-type: none;
            padding: 0;
        }
        li {
            padding: 8px;
            border-bottom: 1px solid #eee;
        }
        li:last-child {
            border-bottom: none;
        }
        button {
            padding: 10px 20px;
            background-color: #007BFF;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }
        button:hover {
            background-color: #0056b3;
        }
    </style>
</head>
<body>
    <header>
        <h1>Student Dashboard</h1>
    </header>
    <section>
        <h2>Personal Data</h2>
        <p>Name: <?php echo $personalResult['name']; ?></p>
        <p>Email: <?php echo $personalResult['Email']; ?></p>
    </section>
    <section>
    <div class="courses-container">
        <div class="course-box">
            <h2>Core Courses</h2>
            <ul>
                <?php foreach ($sortedCourses['Core'] as $course): ?>
                    <li><?php echo $course['C_Code'] . ": " . $course['C_Name'] . " (" . $course['Credit'] . " credits)"; ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
        <div class="course-box">
            <h2>General Education Courses</h2>
            <ul>
                <?php foreach ($sortedCourses['Gen Ed'] as $course): ?>
                    <li><?php echo $course['C_Code'] . ": " . $course['C_Name'] . " (" . $course['Credit'] . " credits)"; ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
        <div class="course-box">
            <h2>Elective Courses</h2>
            <ul>
                <?php foreach ($sortedCourses['Elective'] as $course): ?>
                    <li><?php echo $course['C_Code'] . ": " . $course['C_Name'] . " (" . $course['Credit'] . " credits)"; ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
    </section>
    <div>
        <?php
            include 'schedule.php';
        ?>
    </div>
    <section>
        <button onclick="window.location.href='advising_panel.php';">Go to Advising Panel</button>
    </section>
</body>
</html>
