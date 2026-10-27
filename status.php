<?php
require_once 'auth.php';
require_applicant();

$temp_id = $_SESSION['Temp_ID'];
$status_message = '';
$action_link = null; // [page, button text]

// Fetch the current status from the 'new' table
$query = "SELECT status FROM new WHERE Temp_ID = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("s", $temp_id);
$stmt->execute();
$result = $stmt->get_result();
$status = (int) ($result->fetch_assoc()['status'] ?? 0);

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
        // Admission creates a student account with the same email and password.
        $stmt = $conn->prepare("SELECT 1 FROM users WHERE User_ID = ?");
        $stmt->bind_param("s", $temp_id);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            $status_message .= " Log in again with the same email and password to open your student dashboard.";
            $action_link = ['logout.php', 'Log In as a Student'];
        } else {
            $status_message .= " Your student account is being set up.";
        }
        break;
    case 4:
        $status_message = "Results published: Unfortunately, you got rejected.";
        $action_link = ['apply.php', 'Apply Again'];
        break;
    case 5:
        $status_message = "Results published: You are on the wait list. This page will update if a seat opens for you.";
        break;
    default:
        $status_message = "Your application is being processed.";
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Status Page</title>
    <link href="style.css" rel="stylesheet">
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
        <?php nav_links(); ?>
        <h1>Status Update</h1>
        <p><?php echo e($status_message); ?></p>
        <?php if ($action_link): ?>
            <button onclick="window.location.href='<?php echo e($action_link[0]); ?>';"><?php echo e($action_link[1]); ?></button>
        <?php endif; ?>
    </div>
</body>
</html>
