<?php
include 'DBconnect.php';  // Ensure this path is correct

session_start();

if (!isset($_SESSION['User_ID'])) {  // Simple authentication check
    header("Location: login.php");
    exit;
}

$message = "";

// Fetch unadmitted students
$fetch_query = "SELECT * FROM new WHERE Status = 1";  // Assuming 0 means 'not admitted'
$stmt = $conn->prepare($fetch_query);
$stmt->execute();
$applicants = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['admit'])) {
        $temp_id = $_POST['temp_id'];
        $email = $_POST['email'];
        $password = $_POST['password'];
        $name = $_POST['name'];
        $status = 3;  // Status 1 indicates 'admitted'
        $role_id = 1;  // Assuming role_id for students is '1'

        // Begin transaction
        $conn->begin_transaction();
        try {
            $user_insert = "INSERT INTO users (User_ID, Role_ID, Email, PASSWORD, name) VALUES (?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($user_insert);
            $stmt->bind_param("sisss", $temp_id, $role_id, $email, $password, $name);
            $stmt->execute();
            $stmt->close();

            $student_insert = "INSERT INTO student (Student_ID, Department_ID) VALUES (?, ?)";
            $stmt = $conn->prepare($student_insert);
            $department_id = 'CSE';  // Default department, can be dynamic
            $stmt->bind_param("ss", $temp_id, $department_id);
            $stmt->execute();
            $stmt->close();

            $update_status = "UPDATE new SET Status = ? WHERE Temp_ID = ?";
            $stmt = $conn->prepare($update_status);
            $stmt->bind_param("is", $status, $temp_id);
            $stmt->execute();
            $stmt->close();

            $conn->commit();
            $message = "Student admitted successfully.";
        } catch (Exception $e) {
            $conn->rollback();
            $message = "Error admitting student: " . $e->getMessage();
        }
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admit Student</title>
    <style>
        body {
            font-family: 'Arial', sans-serif;
            background: #f4f4f4;
            padding: 20px;
            margin: 0;
        }
        .container, .form-container {
            background: white;
            max-width: 600px;
            margin: 20px auto;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th, td {
            padding: 10px;
            border-bottom: 1px solid #ccc;
            text-align: left;
        }
        input, select {
            padding: 10px;
            margin-top: 5px;
            border-radius: 4px;
            width: 100%;
            box-sizing: border-box;
        }
        input[type="submit"] {
            background-color: #4CAF50;
            color: white;
            cursor: pointer;
            border: none;
        }
        input[type="submit"]:hover {
            background-color: #45a049;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Admit New Student</h1>
        <p><?php echo $message; ?></p>
        <table>
            <tr>
                <th>Temp ID</th>
                <th>Email</th>
                <th>Name</th>
                <th>Action</th>
            </tr>
            <?php foreach ($applicants as $applicant): ?>
            <tr>
                <td><?php echo $applicant['Temp_ID']; ?></td>
                <td><?php echo $applicant['Email']; ?></td>
                <td><?php echo $applicant['name']; ?></td>
                <td>
                    <form method="post">
                        <input type="hidden" name="temp_id" value="<?php echo $applicant['Temp_ID']; ?>">
                        <input type="hidden" name="email" value="<?php echo $applicant['Email']; ?>">
                        <input type="hidden" name="password" value="<?php echo $applicant['PASSWORD']; ?>">
                        <input type="hidden" name="name" value="<?php echo $applicant['name']; ?>">
                        <input type="submit" name="admit" value="Admit">
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
        </table>
    </div>
</body>
</html>
