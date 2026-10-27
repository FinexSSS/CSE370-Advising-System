<?php
require_once 'auth.php';
require_role([ROLE_FACULTY]);

const GRADES = ['A+', 'A', 'A-', 'B+', 'B', 'B-', 'C+', 'C', 'C-', 'D+', 'D', 'D-', 'F'];

$message = "";
$enrollments = [];
$search_term = trim($_POST['search_term'] ?? '');

// Handle the search and update operation
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['update_grade'])) {
        $student_id = (string) ($_POST['student_id'] ?? '');
        $c_code = (string) ($_POST['c_code'] ?? '');
        $section = (int) ($_POST['section'] ?? 0);
        $grade = (string) ($_POST['grade'] ?? '');
        if (!in_array($grade, GRADES, true)) {
            $message = "Please choose a valid grade.";
        } else {
            $update_query = "UPDATE enrollment SET Grade = ? WHERE Student_ID = ? AND C_Code = ? AND Section = ?";
            $stmt = $conn->prepare($update_query);
            $stmt->bind_param("sssi", $grade, $student_id, $c_code, $section);
            $stmt->execute();
            $message = $stmt->affected_rows > 0 ? "Grade updated successfully." : "No change: the grade was already $grade.";
        }
    }

    // Show the search results again after an update, so the new grade is visible.
    if ($search_term !== '') {
        $search_query = "SELECT e.Student_ID, e.C_Code, e.Section, e.Grade FROM enrollment e WHERE e.Student_ID LIKE CONCAT('%', ?, '%') ORDER BY e.Student_ID, e.C_Code";
        $stmt = $conn->prepare($search_query);
        $stmt->bind_param("s", $search_term);
        $stmt->execute();
        $enrollments = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        if (empty($enrollments) && $message === "") {
            $message = "No enrollments found for that student ID.";
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
    <link href="style.css" rel="stylesheet">
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
        td form {
            margin-top: 0;
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
        <?php nav_links(); ?>
        <h1>Update Grades</h1>
        <p class="message"><?php echo e($message); ?></p>
        <form method="post">
            <label for="search_term">Search Student ID:</label>
            <input type="text" id="search_term" name="search_term" value="<?php echo e($search_term); ?>" required>
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
                </tr>
                <?php foreach ($enrollments as $enrollment): ?>
                    <tr>
                        <td><?php echo e($enrollment['Student_ID']); ?></td>
                        <td><?php echo e($enrollment['C_Code']); ?></td>
                        <td><?php echo e($enrollment['Section']); ?></td>
                        <td><?php echo e($enrollment['Grade'] ?? 'None'); ?></td>
                        <td>
                            <form method="post">
                                <input type="hidden" name="student_id" value="<?php echo e($enrollment['Student_ID']); ?>">
                                <input type="hidden" name="c_code" value="<?php echo e($enrollment['C_Code']); ?>">
                                <input type="hidden" name="section" value="<?php echo e($enrollment['Section']); ?>">
                                <input type="hidden" name="search_term" value="<?php echo e($search_term); ?>">
                                <select name="grade" required>
                                    <?php foreach (GRADES as $grade): ?>
                                        <option<?php echo $grade === $enrollment['Grade'] ? ' selected' : ''; ?>><?php echo $grade; ?></option>
                                    <?php endforeach; ?>
                                </select>
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
