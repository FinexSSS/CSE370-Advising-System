<?php
require_once 'auth.php';
require_role([ROLE_ADMIN]);

// Application status codes, stored in new.status
const STATUS_APPLIED = 1;
const STATUS_PAID = 2;
const STATUS_ACCEPTED = 3;
const STATUS_REJECTED = 4;
const STATUS_WAIT_LIST = 5;

const STATUS_NAMES = [
    STATUS_APPLIED => 'Applied, awaiting payment',
    STATUS_PAID => 'Payment confirmed',
    STATUS_WAIT_LIST => 'Wait list',
];

// Which statuses each action may move an applicant from, and the status it sets.
const ACTIONS = [
    'confirm_payment' => [[STATUS_APPLIED], STATUS_PAID],
    'admit' => [[STATUS_PAID, STATUS_WAIT_LIST], STATUS_ACCEPTED],
    'wait_list' => [[STATUS_PAID], STATUS_WAIT_LIST],
    'reject' => [[STATUS_APPLIED, STATUS_PAID, STATUS_WAIT_LIST], STATUS_REJECTED],
];

$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $temp_id = (string) ($_POST['temp_id'] ?? '');
    $action = (string) ($_POST['action'] ?? '');

    // Begin transaction
    $conn->begin_transaction();
    try {
        // The applicant's details come from the database, never from the form.
        $stmt = $conn->prepare("SELECT Email, PASSWORD, name, status FROM new WHERE Temp_ID = ? FOR UPDATE");
        $stmt->bind_param("s", $temp_id);
        $stmt->execute();
        $applicant = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$applicant || !isset(ACTIONS[$action]) || !in_array((int) $applicant['status'], ACTIONS[$action][0], true)) {
            throw new RuntimeException("That action is not available for this applicant.");
        }
        $new_status = ACTIONS[$action][1];

        if ($new_status === STATUS_ACCEPTED) {
            $stmt = $conn->prepare("SELECT 1 FROM users WHERE User_ID = ? OR Email = ?");
            $stmt->bind_param("ss", $temp_id, $applicant['Email']);
            $stmt->execute();
            if ($stmt->get_result()->num_rows > 0) {
                throw new RuntimeException("A user with this ID or email already exists.");
            }

            // The student logs in with the same email and password they applied with.
            $role_id = ROLE_STUDENT;
            $user_insert = "INSERT INTO users (User_ID, Role_ID, Email, PASSWORD, name) VALUES (?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($user_insert);
            $stmt->bind_param("sisss", $temp_id, $role_id, $applicant['Email'], $applicant['PASSWORD'], $applicant['name']);
            $stmt->execute();
            $stmt->close();

            $student_insert = "INSERT INTO student (Student_ID, Credit, Department_ID, approval) VALUES (?, 0, ?, 0)";
            $stmt = $conn->prepare($student_insert);
            $department_id = 'CSE';  // Default department, can be dynamic
            $stmt->bind_param("ss", $temp_id, $department_id);
            $stmt->execute();
            $stmt->close();
        }

        $update_status = "UPDATE new SET status = ? WHERE Temp_ID = ?";
        $stmt = $conn->prepare($update_status);
        $stmt->bind_param("is", $new_status, $temp_id);
        $stmt->execute();
        $stmt->close();

        $conn->commit();
        $message = [
            STATUS_PAID => "Payment confirmed.",
            STATUS_ACCEPTED => "Student admitted successfully. Their student ID is $temp_id.",
            STATUS_WAIT_LIST => "Applicant moved to the wait list.",
            STATUS_REJECTED => "Application rejected.",
        ][$new_status];
    } catch (mysqli_sql_exception $e) {
        // Caught first: it is also a RuntimeException, and its text is not for visitors.
        $conn->rollback();
        error_log("Admission update failed: " . $e->getMessage());
        $message = "Could not update the applicant. Please try again.";
    } catch (RuntimeException $e) {
        $conn->rollback();
        $message = $e->getMessage();
    }
}

// Applicants with an application in progress, with their latest application if any
$fetch_query = "SELECT n.Temp_ID, n.Email, n.name, n.status, a.Education_Level_SSC, a.Maths_Grade_SSC, a.English_Grade_SSC, a.Physics_Grade_SSC, a.Education_Level_HSC, a.Maths_Grade_HSC, a.Physics_Grade_HSC, a.Document_Path
    FROM new n
    LEFT JOIN applications a ON a.Application_ID = (SELECT MAX(Application_ID) FROM applications WHERE Temp_ID = n.Temp_ID)
    WHERE n.status IN (1, 2, 5)
    ORDER BY n.status, n.name";
$applicants = $conn->query($fetch_query)->fetch_all(MYSQLI_ASSOC);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admit Student</title>
    <link href="style.css" rel="stylesheet">
    <style>
        body {
            font-family: 'Arial', sans-serif;
            background: #f4f4f4;
            padding: 20px;
            margin: 0;
        }
        .container, .form-container {
            background: white;
            max-width: 900px;
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
            vertical-align: top;
        }
        form {
            display: inline;
        }
        button {
            padding: 6px 10px;
            margin: 2px 0;
            border: none;
            border-radius: 4px;
            color: white;
            cursor: pointer;
            background-color: #4CAF50;
        }
        button:hover {
            background-color: #45a049;
        }
        button.secondary {
            background-color: #6c757d;
        }
        button.danger {
            background-color: #d9534f;
        }
        .grades {
            font-size: 13px;
            color: #555;
        }
    </style>
</head>
<body>
    <div class="container">
        <?php nav_links(); ?>
        <h1>Admit New Students</h1>
        <p><?php echo e($message); ?></p>
        <?php if (empty($applicants)): ?>
            <p>No applications are waiting for a decision.</p>
        <?php else: ?>
        <table>
            <tr>
                <th>Temp ID</th>
                <th>Applicant</th>
                <th>Application</th>
                <th>Status</th>
                <th>Action</th>
            </tr>
            <?php foreach ($applicants as $applicant): ?>
            <?php $status = (int) $applicant['status']; ?>
            <tr>
                <td><?php echo e($applicant['Temp_ID']); ?></td>
                <td><?php echo e($applicant['name']); ?><br><span class="grades"><?php echo e($applicant['Email']); ?></span></td>
                <td class="grades">
                    <?php if ($applicant['Education_Level_SSC'] !== null): ?>
                        <?php echo e($applicant['Education_Level_SSC']); ?>: Maths <?php echo e($applicant['Maths_Grade_SSC']); ?>, English <?php echo e($applicant['English_Grade_SSC']); ?>, Physics <?php echo e($applicant['Physics_Grade_SSC']); ?><br>
                        <?php echo e($applicant['Education_Level_HSC']); ?>: Maths <?php echo e($applicant['Maths_Grade_HSC']); ?>, Physics <?php echo e($applicant['Physics_Grade_HSC']); ?><br>
                        <?php if ($applicant['Document_Path'] && is_file(__DIR__ . '/documents/' . basename($applicant['Document_Path']))): ?>
                            <a href="document.php?temp_id=<?php echo urlencode($applicant['Temp_ID']); ?>">View documents</a>
                        <?php endif; ?>
                    <?php else: ?>
                        No application form on file
                    <?php endif; ?>
                </td>
                <td><?php echo e(STATUS_NAMES[$status]); ?></td>
                <td>
                    <?php foreach (['confirm_payment' => ['Confirm Payment', ''], 'admit' => ['Admit', ''], 'wait_list' => ['Wait List', 'secondary'], 'reject' => ['Reject', 'danger']] as $action => [$label, $class]): ?>
                        <?php if (in_array($status, ACTIONS[$action][0], true)): ?>
                            <form method="post">
                                <input type="hidden" name="temp_id" value="<?php echo e($applicant['Temp_ID']); ?>">
                                <button type="submit" name="action" value="<?php echo $action; ?>" class="<?php echo $class; ?>"><?php echo $label; ?></button>
                            </form>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </table>
        <?php endif; ?>
    </div>
</body>
</html>
