<?php
require_once 'auth.php';
require_role([ROLE_FACULTY]);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BRACU Advising</title>
    <link href="style.css" rel="stylesheet">
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f0f2f5;
            margin: 0;
        }

        .container {
            width: 80%;
            margin: 20px auto;
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            position: relative; /* Added position relative */
            z-index: 1; /* Ensure the container is above other elements */
        }

        h1 {
            text-align: center;
            color: #333;
        }

        ul {
            list-style-type: none;
            padding: 0;
        }

        li {
            padding: 8px;
            margin-top: 10px;
        }

        a {
            text-decoration: none;
            color: #007BFF;
            font-weight: bold;
        }

        a:hover {
            text-decoration: underline;
        }

        button {
            background-color: #007BFF;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
        }

        button:hover {
            background-color: #0056b3;
        }


    </style>

</head>
<body>
    <header id="header">
        <div class="header-bar">
            <h1>ADVISING</h1>
            <nav class="header-links">
                <a href="cse_details.php">Courses</a>
                <a href="faculty.php">Faculty</a>
                <a href="logout.php">Log Out</a>
            </nav>
        </div>
    </header>
    <div class="container">
        <h1>Faculty Dashboard</h1>
        <ul>
            <li><button onclick="location.href='approve.php'">Approve</button></li>
            <li><button onclick="location.href='admin_advising_panel.php'">Student Advising</button></li>
            <li><button onclick="location.href='add_grade.php'">Add Grade</button></li>
        </ul>
    </div>
</body>
</html>
