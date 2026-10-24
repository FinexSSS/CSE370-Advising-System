<?php
include 'DBconnect.php'; // Ensure this path is correct

session_start();

if (!isset($_SESSION['User_ID'])) {
    header("Location: login.php");
    exit;
}

$message = "";
$enrollments = [];

// Handle the search and update operation
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['search'])) {
        $search_term = $_POST['search_term'];
        $search_query = "SELECT e.Student_ID, e.C_Code, e.Section, e.Grade FROM enrollment e WHERE e.Student_ID LIKE CONCAT('%', ?, '%')";
        $stmt = $conn->prepare($search_query);
        $stmt->bind_param("s", $search_term);
        $stmt->execute();
        $result = $stmt->get_result();
        $enrollments = $result->fetch_all(MYSQLI_ASSOC);
    } elseif (isset($_POST['update_grade'])) {
        $student_id = $_POST['student_id'];
        $c_code = $_POST['c_code'];
        $section = $_POST['section'];
        $grade = $_POST['grade'];
        $update_query = "UPDATE enrollment SET Grade = ? WHERE Student_ID = ? AND C_Code = ? AND Section = ?";
        $stmt = $conn->prepare($update_query);
        $stmt->bind_param("sssi", $grade, $student_id, $c_code, $section);
        if ($stmt->execute()) {
            $message = "Grade updated successfully.";
        } else {
            $message = "Failed to update grade: " . $stmt->error;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update Grades</title>
    <style>
        body {
            font-family: 'Arial', sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 20px;
        }
        .container {
            background-color: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
            max-width: 800px;
            margin: auto;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        th {
            background-color: #007bff;
            color: white;
        }
        td {
            background-color: #fff;
        }
        input[type="text"], select, input[type="number"] {
            width: calc(100% - 22px);
            padding: 10px;
            margin-top: 5px;
            border-radius: 5px;
            border: 1px solid #ccc;
        }
        button {
            background-color: #28a745;
            color: white;
            padding: 10px 15px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            margin-top: 10px;
        }
        button:hover {
            background-color: #218838;
        }
        form {
            margin-top: 20px;
        }
        label {
            font-weight: bold;
        }
        .message {
            color: #d9534f;
            margin-bottom: 15px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Update Grades</h1>
        <p class="message"><?php echo $message; ?></p>
        <form method="post">
            <label for="search_term">Search Student ID:</label>
            <input type="text" id="search_term" name="search_term" required>
            <button type="submit" name="search">Search</button>
        </form>

        <?php if (!empty($enrollments)): ?>
            <table>
                <tr>
                    <th>Student ID</th>
                    <th>Course Code</th>
                    <th>Section</th>
                    <th>Current Grade</th>
                    <th>New Grade</th>
                    <th>Action</th>
                </tr>
                <?php foreach ($enrollments as $enrollment): ?>
                    <tr>
                        <td><?php echo $enrollment['Student_ID']; ?></td>
                        <td><?php echo $enrollment['C_Code']; ?></td>
                        <td><?php echo $enrollment['Section']; ?></td>
                        <td><?php echo $enrollment['Grade']; ?></td>
                        <td>
                            <form method="post">
                                <input type="hidden" name="student_id" value="<?php echo htmlspecialchars($enrollment['Student_ID']); ?>">
                                <input type="hidden" name="c_code" value="<?php echo htmlspecialchars($enrollment['C_Code']); ?>">
                                <input type="hidden" name="section" value="<?php echo $enrollment['Section']; ?>">
                                <input type="text" name="grade" required placeholder="Enter new grade">
                                <button type="submit" name="update_grade">Update Grade</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </table>
        <?php endif; ?>
    </div>
</body>
</html>
