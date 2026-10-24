<?php
session_start();

// Redirect if not logged in or if the Temp_ID is not set
if (!isset($_SESSION['Temp_ID'])) {
    header("Location: login.php");
    exit;
}

include('DBconnect.php');

$temp_id = $_SESSION['Temp_ID'];
$status_message = '';
$action_button = '';

// Fetch the current status from the 'new' table
$query = "SELECT status FROM new WHERE Temp_ID = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("s", $temp_id);
$stmt->execute();
$result = $stmt->get_result();
$status = $result->fetch_assoc()['status'] ?? 0;

switch ($status) {
    case 0:
        header("Location: apply.php");
        exit;
    case 1:
        $status_message = "Application submitted, awaiting payment confirmation.";
        break;
    case 2:
        $status_message = "Payment confirmed. Your exam date and time will be announced soon.";
        break;
    case 3:
        $status_message = "Results published: Congratulations, you got accepted!";
        $action_button = '<button onclick="window.location.href=\'enroll.php\';">Enroll Now</button>';
        break;
    case 4:
        $status_message = "Results published: Unfortunately, you got rejected.";
        $action_button = '<button onclick="window.location.href=\'apply.php\';">Apply Again</button>';
        break;
    case 5:
        $status_message = "Results published: You are on the wait list.";
        $action_button = '<button onclick="window.location.href=\'wait_list.php\';">View Details</button>';
        break;
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Status Page</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f8f9fa;
            margin: 0;
            padding: 20px;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
        }

        .status-message {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 4px 16px rgba(0,0,0,0.1);
            width: 100%;
            max-width: 500px;
            text-align: center;
        }

        button {
            padding: 10px 20px;
            margin-top: 20px;
            background-color: #007bff;
            color: white;
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
    <div class="status-message">
        <h1>Status Update</h1>
        <p><?php echo $status_message; ?></p>
        <?php echo $action_button; ?>
    </div>
</body>
</html>
