<?php
require_once 'auth.php';
require_role([ROLE_FACULTY]);

$message = "";
$users = [];
$search_term = trim($_POST['search_term'] ?? '');

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['approve'])) {
        $user_id = (string) ($_POST['student_id'] ?? '');
        $update_query = "UPDATE student SET approval = 1 WHERE Student_ID = ?";
        $stmt = $conn->prepare($update_query);
        $stmt->bind_param("s", $user_id);
        $stmt->execute();
        $message = $stmt->affected_rows > 0 ? "Approval status updated successfully." : "That student is already approved.";
    }

    // Show the search results again after approving, so the change is visible.
    if ($search_term !== '') {
        $search_query = "SELECT s.Student_ID, u.name, s.CGPA, s.approval FROM student s JOIN users u ON u.User_ID = s.Student_ID WHERE s.Student_ID LIKE CONCAT('%', ?, '%') OR u.name LIKE CONCAT('%', ?, '%') ORDER BY s.Student_ID";
        $stmt = $conn->prepare($search_query);
        $stmt->bind_param("ss", $search_term, $search_term);
        $stmt->execute();
        $users = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        if (empty($users)) {
            $message = "No students found.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Approve Users</title>
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
        input[type="text"], select {
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
        <h1>Approve Students for Advising</h1>
        <p class="message"><?php echo e($message); ?></p>
        <form method="post">
            <label for="search_term">Search Student ID or Name:</label>
            <input type="text" id="search_term" name="search_term" value="<?php echo e($search_term); ?>" required>
            <button type="submit" name="search">Search</button>
        </form>

        <?php if (!empty($users)): ?>
            <table>
                <tr>
                    <th>Student ID</th>
                    <th>Name</th>
                    <th>CGPA</th>
                    <th>Action</th>
                </tr>
                <?php foreach ($users as $user): ?>
                    <tr>
                        <td><?php echo e($user['Student_ID']); ?></td>
                        <td><?php echo e($user['name']); ?></td>
                        <td><?php echo e($user['CGPA']); ?></td>
                        <td>
                            <?php if ((int) $user['approval'] === 1): ?>
                                Approved
                            <?php else: ?>
                            <form method="post">
                                <input type="hidden" name="student_id" value="<?php echo e($user['Student_ID']); ?>">
                                <input type="hidden" name="search_term" value="<?php echo e($search_term); ?>">
                                <button type="submit" name="approve">Approve</button>
                            </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </table>
        <?php endif; ?>
    </div>
</body>
</html>
