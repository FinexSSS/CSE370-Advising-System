<?php
include 'DBconnect.php';

session_start();

if (!isset($_SESSION['User_ID'])) {
    header("Location: login.php");
    exit;
}

$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['add_faculty'])) {
        $faculty_id = $_POST['faculty_id'];
        $initial = $_POST['initial'];
        $offered = $_POST['offered'];
        $taken = $_POST['taken'];

        $query = "INSERT INTO faculty (Faculty_ID, F_Initial, C_Offered, C_Taken) VALUES (?, ?, ?, ?)";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("ssss", $faculty_id, $initial, $offered, $taken);
        if ($stmt->execute()) {
            $message = "Faculty added successfully.";
        } else {
            $message = "Error adding faculty: " . $stmt->error;
        }
        $stmt->close();
    } elseif (isset($_POST['remove_faculty'])) {
        $faculty_id = $_POST['faculty_id_remove'];
        $query = "DELETE FROM faculty WHERE Faculty_ID = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("s", $faculty_id);
        if ($stmt->execute()) {
            $message = "Faculty removed successfully.";
        } else {
            $message = "Error removing faculty: " . $stmt->error;
        }
        $stmt->close();
    }
}

// Fetch faculty for the dropdown
$faculty_query = "SELECT Faculty_ID, F_Initial FROM faculty";
$stmt = $conn->prepare($faculty_query);
$stmt->execute();
$faculty_list = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add/Remove Faculty</title>
    <style>
        body {
            font-family: 'Arial', sans-serif;
            background: #f4f4f4;
            padding: 20px;
            margin: 0;
        }
        .container {
            background: white;
            max-width: 600px;
            margin: 20px auto;
            padding: 20px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            border-radius: 5px;
        }
        h1 {
            color: #333;
        }
        form {
            margin-top: 20px;
        }
        input[type="text"], input[type="submit"], select {
            width: 100%;
            padding: 10px;
            margin: 10px 0;
            border-radius: 4px;
            border: 1px solid #ccc;
        }
        input[type="submit"] {
            background-color: #5C67F2;
            color: white;
            font-size: 16px;
            cursor: pointer;
            border: none;
        }
        input[type="submit"]:hover {
            background-color: #3a40ec;
        }
        .message {
            color: #d63031;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Add/Remove Faculty</h1>
        <p class="message"><?php echo $message; ?></p>
        <form method="post">
            Faculty ID: <input type="text" name="faculty_id" required><br>
            Initials: <input type="text" name="initial" required><br>
            Courses Offered: <input type="text" name="offered"><br>
            Courses Taken: <input type="text" name="taken"><br>
            <input type="submit" name="add_faculty" value="Add Faculty">
        </form>
        <form method="post">
            <label for="faculty_id_remove">Select Faculty to Remove:</label>
            <select name="faculty_id_remove" id="faculty_id_remove">
                <?php foreach ($faculty_list as $faculty): ?>
                    <option value="<?php echo $faculty['Faculty_ID']; ?>"><?php echo $faculty['F_Initial']; ?></option>
                <?php endforeach; ?>
            </select>
            <input type="submit" name="remove_faculty" value="Remove Faculty">
        </form>
    </div>
</body>
</html>
