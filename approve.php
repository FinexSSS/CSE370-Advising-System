<?php
include 'DBconnect.php';  // Ensure this path is correct

session_start();

if (!isset($_SESSION['User_ID'])) {
    header("Location: login.php");
    exit;
}

$message = "";
$users = [];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['search'])) {
        $search_term = $_POST['search_term'];
        $search_query = "SELECT * FROM student WHERE Student_ID LIKE CONCAT('%', ?, '%')";
        $stmt = $conn->prepare($search_query);
        $stmt->bind_param("s", $search_term);
        $stmt->execute();
        $result = $stmt->get_result();
        $users = $result->fetch_all(MYSQLI_ASSOC);
    } elseif (isset($_POST['approve'])) {
        $user_id = $_POST['student_id'];
        $update_query = "UPDATE student SET approval = 1 WHERE Student_ID = ?";
        $stmt = $conn->prepare($update_query);
        $stmt->bind_param("s", $user_id);
        if ($stmt->execute()) {
            $message = "Approval status updated successfully.";
        } else {
            $message = "Failed to update approval status: " . $stmt->error;
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
        <h1>Approve Users</h1>
        <p class="message"><?php echo $message; ?></p>
        <form method="post">
            <label for="search_term">Search User ID or Name:</label>
            <input type="text" id="search_term" name="search_term" required>
            <button type="submit" name="search">Search</button>
        </form>

        <?php if (!empty($users)): ?>
            <table>
                <tr>
                    <th>User ID</th>
                    <th>CGPA</th>
                    <th>Action</th>
                </tr>
                <?php foreach ($users as $user): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($user['Student_ID']); ?></td>
                        <td><?php echo $user['CGPA']; ?></td>
                        <td>
                            <form method="post">
                                <input type="hidden" name="student_id" value="<?php echo $user['Student_ID']; ?>">
                                <button type="submit" name="approve">Approve</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </table>
        <?php endif; ?>
    </div>
</body>
</html>
