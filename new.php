<?php
session_start();

// Redirect to login page if 'Temp_ID' is not set in session
if (!isset($_SESSION['Temp_ID'])) {
    header("Location: home.php");
    exit;
}

include('DBconnect.php');

$temp_id = $_SESSION['Temp_ID'];

// Fetch user details if needed (optional)
$query = "SELECT name FROM new WHERE Temp_ID = '$temp_id'";
$result = mysqli_query($conn, $query);
$user = mysqli_fetch_assoc($result);

// Welcome message
$welcomeMsg = "Welcome to BRACU University CSE Department";

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome Page</title>
    <style>
        body {
            font-family: 'Arial', sans-serif;
            background-color: #f3f3f3;
            color: #333;
            margin: 0;
            padding: 20px;
        }
        header {
            background-color: #005691; /* Dark blue: adjust to match BRACU colors */
            color: white;
            padding: 20px;
            border-radius: 5px;
            text-align: center;
        }
        header h1 {
            margin: 0;
            font-size: 24px;
        }
        header h2 {
            margin-top: 5px;
            font-size: 18px;
        }
        section {
            background-color: white;
            padding: 20px;
            margin-top: 20px;
            border-radius: 5px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        section h3 {
            color: #005691; /* Echoing the header color for consistency */
        }
        ul {
            list-style-type: none;
            padding: 0;
        }
        ul li {
            padding: 8px;
            border-bottom: 1px solid #ccc;
        }
        ul li:last-child {
            border-bottom: none;
        }
        a {
            color: #007BFF;
            text-decoration: none;
        }
        a:hover {
            text-decoration: underline;
        }
        /* Adjust button styling */
        .btn {
            background-color: #007BFF;
            color: white;
            padding: 10px 20px;
            text-align: center;
            display: inline-block;
            margin: 10px 0;
            border-radius: 5px;
            transition: background-color 0.3s;
        }
        .btn:hover {
            background-color: #0056b3;
        }
        /* Responsive adjustments */
        @media (max-width: 600px) {
            body {
                padding: 10px;
            }
            header, section {
                padding: 15px;
            }
        }
    </style>
</head>
<body>
    <header>
        <h1><?php echo $welcomeMsg; ?></h1>
        <h2>Hello, <?php echo $user['name'] ?? 'Applicant'; ?>!</h2>
    </header>
    <section>
        <p>The Computer Science and Engineering (CSE) program requires a total of 136 credits to graduate. Here's what you need to know as you start your journey with us:</p>
        <ul>
            <li>Total credits required: 136</li>
            <li>Participation in extracurricular activities is encouraged to enhance your learning experience.</li>
        </ul>
    </section>
    <section>
        <h3>Quick Links</h3>
        <ul>
            <li><a href="apply.php">Apply Now</a></li>
            <li><a href="status.php">Check Application Status</a></li>
            <li><a href="cse_details.php">Learn More About CSE Program</a></li>
        </ul>
    </section>
</body>
</html>
