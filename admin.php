<?php
require_once 'auth.php';
require_role([ROLE_ADMIN]);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <link href="style.css" rel="stylesheet">
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f0f2f5;
            margin: 0;
            padding: 20px;
        }

        .container {
            width: 80%;
            margin: 20px auto;
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
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
    <div class="container">
        <?php nav_links(); ?>
        <h1>Admin Dashboard</h1>
        <ul>
            <li><button onclick="location.href='admit.php'">Admit Students</button></li>
            <li><button onclick="location.href='add_faculty.php'">Add Faculty</button></li>
            <li><button onclick="location.href='add_remove.php'">Manage Courses and Sections</button></li>
            <li><button onclick="location.href='admin_advising_panel.php'">Student Advising</button></li>
            <li><button onclick="location.href='cse_details.php'">Course Catalogue</button></li>
        </ul>
    </div>
</body>
</html>
