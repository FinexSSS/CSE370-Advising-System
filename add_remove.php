<?php
include 'DBconnect.php';

session_start();

if (!isset($_SESSION['User_ID'])) {
    header("Location: login.php");
    exit;
}

$message = "";

// Fetch courses and faculty assignments
$fetch_courses_query = "SELECT c.C_Code, c.C_Name, r.Section_ID, r.seat, r.Sections, f.F_Initial FROM course c JOIN room r ON c.C_Code = r.C_Code LEFT JOIN faculty f ON f.F_Initial = r.F_Initial";
$stmt = $conn->prepare($fetch_courses_query);
$stmt->execute();
$courses = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['remove_course'])) {
        $c_code = $_POST['c_code'];
        $query = "DELETE FROM course WHERE C_Code = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("s", $c_code);
        if ($stmt->execute()) {
            $message = "Course removed successfully.";
        } else {
            $message = "Error removing course: " . $stmt->error;
        }
        $stmt->close();
    } elseif (isset($_POST['assign_faculty'])) {
        $faculty_id = $_POST['faculty_id'];
        $section_id = $_POST['section_id'];
        $query = "UPDATE room SET F_Initial = ? WHERE Section_ID = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("si", $faculty_id, $section_id);
        if ($stmt->execute()) {
            $message = "Faculty assigned successfully.";
        } else {
            $message = "Error assigning faculty: " . $stmt->error;
        }
        $stmt->close();
    } elseif (isset($_POST['add_course']))  { // Assuming a single form submission for add/update
        $c_code = $_POST['c_code'];
        $c_name = $_POST['c_name'];
        $credit = $_POST['credit'];
        $prereq = $_POST['prereq'];
        $desc = $_POST['desc'];
        $lab = isset($_POST['lab']) ? 1 : 0; // Checkbox for lab
        $seats = $_POST['seats'];
        $type = $_POST['type'];
        $sections = $_POST['sections'];

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
        if ($stmt) {
            $stmt->bind_param("ssdssiiis", $c_code, $c_name, $credit, $prereq, $desc, $lab, $seats, $type, $sections);
            if ($stmt->execute()) {
                $message = "Course added/updated successfully.";
            } else {
                $message = "Error adding/updating course: " . $stmt->error;
            }
            $stmt->close();
        } else {
            $message = "Error preparing the statement: " . $conn->error;
        }
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Courses</title>
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
            max-height: 150px;
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
    <h1>Remove Courses</h1>
    <p><?php echo $message; ?></p>
    <div class="scroll-box">
        <?php foreach ($courses as $course): ?>
        <div class="list-item">
            <?php echo $course['C_Code'] . " - " . $course['C_Name'] . " - " . $course['Sections'] . " - " . $course['F_Initial']; ?>
            <form method="post" style="display: inline;">
                <input type="hidden" name="c_code" value="<?php echo $course['C_Code']; ?>">
                <input type="submit" name="remove_course" value="Remove">
            </form>
        </div>
        <?php endforeach; ?>
    </div>
    <form method="post">
        <h2>Add/Update a Course</h2>
        Course Code: <input type="text" name="c_code" required><br>
        Course Name: <input type="text" name="c_name" required><br>
        Credit: <input type="number" name="credit" required><br>
        Prerequisite: <input type="text" name="prereq"><br>
        Description: <input type="text" name="desc"><br>
        Lab: <input type="checkbox" name="lab" value="1"><br>
        Seats: <input type="number" name="seats" required><br>
        Type: <input type="text" name="type"><br>
        Sections: <input type="number" name="sections" required><br>
        <input type="submit" name="add_course" value="Add/Update Course">
    </form>
    <form method="post">
        <h2>Assign Faculty to Section</h2>
        Faculty ID: <input type="text" name="faculty_id" required><br>
        Section ID: <input type="text" name="section_id" required><br>
        <input type="submit" name="assign_faculty" value="Assign Faculty">
    </form>
</body>
</html>
