<?php
require_once 'auth.php';
require_once 'schedule.php';
require_role([ROLE_STUDENT]);

$user_id = $_SESSION['User_ID'];

// Fetch personal data
$personal_query = "SELECT u.User_ID, u.name, u.Email, s.Credit, s.CGPA, s.approval FROM users u LEFT JOIN student s ON s.Student_ID = u.User_ID WHERE u.User_ID = ?";
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

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard</title>
    <link href="style.css" rel="stylesheet">
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
    <?php nav_links(); ?>
    <header>
        <h1>Student Dashboard</h1>
    </header>
    <section>
        <h2>Personal Data</h2>
        <p>Name: <?php echo e($personalResult['name']); ?></p>
        <p>Student ID: <?php echo e($personalResult['User_ID']); ?></p>
        <p>Email: <?php echo e($personalResult['Email']); ?></p>
        <p>Credits completed: <?php echo e($personalResult['Credit'] ?? 0); ?></p>
        <p>CGPA: <?php echo $personalResult['CGPA'] === null ? 'Not available yet' : e($personalResult['CGPA']); ?></p>
        <p>Advising approval: <?php echo (int) $personalResult['approval'] === 1 ? 'Approved' : 'Pending'; ?></p>
    </section>
    <section>
    <div class="courses-container">
        <div class="course-box">
            <h2>Core Courses</h2>
            <ul>
                <?php foreach ($sortedCourses['Core'] as $course): ?>
                    <li><?php echo e($course['C_Code'] . ": " . $course['C_Name'] . " (" . $course['Credit'] . " credits)"); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
        <div class="course-box">
            <h2>General Education Courses</h2>
            <ul>
                <?php foreach ($sortedCourses['Gen Ed'] as $course): ?>
                    <li><?php echo e($course['C_Code'] . ": " . $course['C_Name'] . " (" . $course['Credit'] . " credits)"); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
        <div class="course-box">
            <h2>Elective Courses</h2>
            <ul>
                <?php foreach ($sortedCourses['Elective'] as $course): ?>
                    <li><?php echo e($course['C_Code'] . ": " . $course['C_Name'] . " (" . $course['Credit'] . " credits)"); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
    </section>
    <?php render_schedule($conn, $user_id); ?>
    <section>
        <button onclick="window.location.href='advising_panel.php';">Go to Advising Panel</button>
    </section>
</body>
</html>
