<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BRACU Advising</title>
    <link href="css/bootstrap.min.css" rel="stylesheet">
    <link href="css/font-awesome.min.css" rel="stylesheet">
    <link href="main.css" rel="stylesheet"> 
    <style>
                body {
            font-family: Arial, sans-serif;
            background: #f0f2f5;
            margin: 0;
            padding: 20px;
            position: relative; /* Added position relative */
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

        #header {
            position: relative; /* Added position relative */
            z-index: 2; /* Ensure the header is above other elements */
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
        <div class="container">
            <div class="row">
                <div class="col-md-2 header-title">
                    <h1>ADVISING</h1>
                </div>
                <div class="col-md-10 header-links">
                    <a href="courses.php">Courses</a>
                    <a href="faculty.php" style="margin-left: 20px">Faculty</a>
                    <a href="announce.php" style="margin-left: 20px">Announcements</a>
                </div>
            </div>
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



