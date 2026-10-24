<?php
// Establish database connection (ensure your connection details are correct)
include 'DBconnect.php';

$search_result = '';
$courses = [];
$searched = false;

// Handle search
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['search_code'])) {
    $search_code = $_POST['search_code'];
    $searched = true;
    $stmt = $conn->prepare("SELECT * FROM course WHERE C_Code = ?");
    $stmt->bind_param("s", $search_code);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $search_result = $result->fetch_assoc();
    } else {
        $search_result = null;
    }
}

// Get all courses if no specific search or search failed
if (!$searched || $search_result === null) {
    $result = $conn->query("SELECT * FROM course");
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $courses[] = $row;
        }
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CSE Course Details</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f0f2f5;
            margin: 0;
            padding: 20px;
        }

        .container {
            width: 80%;
            margin: 0 auto;
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        th, td {
            padding: 8px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }

        th {
            background-color: #007BFF;
            color: white;
        }

        input[type="text"], button {
            padding: 10px;
            margin-top: 20px;
            border-radius: 5px;
            border: 1px solid #ccc;
        }

        button {
            background-color: #007BFF;
            color: white;
            cursor: pointer;
        }

        button:hover {
            background-color: #0056b3;
        }

        .no-results {
            text-align: center;
            margin-top: 20px;
            font-size: 18px;
            color: red;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>CSE Course Details</h1>
        <form method="post">
            <input type="text" name="search_code" placeholder="Enter Course Code">
            <button type="submit">Search</button>
        </form>

        <?php
        if ($searched) {
            if ($search_result) {
                echo "<table><tr><th>Course Code</th><th>Name</th><th>Credits</th><th>Description</th></tr>";
                echo "<tr><td>{$search_result['C_Code']}</td><td>{$search_result['C_Name']}</td><td>{$search_result['Credit']}</td><td>{$search_result['C_Desc']}</td></tr>";
                echo "</table>";
            } else {
                echo "<p class='no-results'>No course found with that code.</p>";
            }
        } else {
            echo "<table><tr><th>Course Code</th><th>Name</th><th>Credits</th><th>Description</th></tr>";
            foreach ($courses as $course) {
                echo "<tr><td>{$course['C_Code']}</td><td>{$course['C_Name']}</td><td>{$course['Credit']}</td><td>{$course['C_Desc']}</td></tr>";
            }
            echo "</table>";
        }
        ?>
    </div>
</body>
</html>
