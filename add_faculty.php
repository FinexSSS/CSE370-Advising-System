<?php
require_once 'auth.php';
require_role([ROLE_ADMIN]);

$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['add_faculty'])) {
        $faculty_id = trim($_POST['faculty_id'] ?? '');
        $initial = strtoupper(trim($_POST['initial'] ?? ''));
        $offered = trim($_POST['offered'] ?? '');
        $taken = trim($_POST['taken'] ?? '');

        if ($faculty_id === '' || strlen($faculty_id) > 20 || !preg_match('/^[A-Z]{2,5}$/', $initial)) {
            $message = "Enter a faculty ID of up to 20 characters and initials of 2 to 5 letters.";
        } else {
            $query = "INSERT INTO faculty (Faculty_ID, F_Initial, C_Offered, C_Taken) VALUES (?, ?, ?, ?)";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("ssss", $faculty_id, $initial, $offered, $taken);
            try {
                $stmt->execute();
                $message = "Faculty added successfully.";
            } catch (mysqli_sql_exception $e) {
                // 1062: the initials (the table's key) are already taken
                $message = $e->getCode() === 1062 ? "A faculty member with the initials $initial already exists." : "Error adding faculty.";
                if ($e->getCode() !== 1062) {
                    error_log("Adding faculty failed: " . $e->getMessage());
                }
            }
            $stmt->close();
        }
    } elseif (isset($_POST['remove_faculty'])) {
        $initial = (string) ($_POST['initial_remove'] ?? '');
        $query = "DELETE FROM faculty WHERE F_Initial = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("s", $initial);
        $stmt->execute();
        $message = $stmt->affected_rows > 0 ? "Faculty removed successfully." : "That faculty member was not found.";
        $stmt->close();
    }
}

// Fetch faculty for the dropdown
$faculty_query = "SELECT Faculty_ID, F_Initial FROM faculty ORDER BY F_Initial";
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
    <link href="style.css" rel="stylesheet">
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
            box-sizing: border-box;
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
        <?php nav_links(); ?>
        <h1>Add/Remove Faculty</h1>
        <p class="message"><?php echo e($message); ?></p>
        <form method="post">
            Faculty ID: <input type="text" name="faculty_id" maxlength="20" required><br>
            Initials: <input type="text" name="initial" maxlength="5" required><br>
            Courses Offered: <input type="text" name="offered"><br>
            Courses Taken: <input type="text" name="taken"><br>
            <input type="submit" name="add_faculty" value="Add Faculty">
        </form>
        <form method="post">
            <label for="initial_remove">Select Faculty to Remove:</label>
            <select name="initial_remove" id="initial_remove">
                <?php foreach ($faculty_list as $faculty): ?>
                    <option value="<?php echo e($faculty['F_Initial']); ?>"><?php echo e($faculty['F_Initial'] . " (" . $faculty['Faculty_ID'] . ")"); ?></option>
                <?php endforeach; ?>
            </select>
            <input type="submit" name="remove_faculty" value="Remove Faculty">
        </form>
    </div>
</body>
</html>
