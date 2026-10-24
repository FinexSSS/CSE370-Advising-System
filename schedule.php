<?php
include 'DBconnect.php';  // Ensure this path is correct


$user_id = $_SESSION['User_ID'];
$message = $_SESSION['message'] ?? '';

// Fetch schedule for enrolled courses
$schedule_query = "SELECT r.C_Code, r.day, r.s_time, r.e_time, r.room_code FROM room r JOIN enrollment e ON e.C_Code = r.C_Code AND e.section = r.sections WHERE e.Student_ID = ?;";
$schedule_stmt = $conn->prepare($schedule_query);
$schedule_stmt->bind_param("s", $user_id);
$schedule_stmt->execute();
$schedule_result = $schedule_stmt->get_result();
$schedule = [];
while ($row = $schedule_result->fetch_assoc()) {
    $schedule[$row['day']][] = $row;
}
$schedule_stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Weekly Schedule</title>
    <style>
        body {
            font-family: 'Arial', sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 0;
            color: #333;
        }

        .schedule-table {
            width: 80%;
            margin: 20px auto;
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th, td {
            border: 1px solid #ccc;
            padding: 8px;
            text-align: center;
        }

        th {
            background-color: #007bff;
            color: white;
        }

        td {
            background-color: #fff;
        }
    </style>
</head>
<body>
    <div class="schedule-table">
        <h2>Weekly Schedule</h2>
        <table>
            <tr>
                <th>Time / Day</th>
                <th>Sunday</th>
                <th>Monday</th>
                <th>Tuesday</th>
                <th>Wednesday</th>
                <th>Thursday</th>
                <th>Friday</th>
                <th>Saturday</th>
            </tr>
            <?php
            $time_slots = ['08:00:00-09:20:00', '09:30:00-10:50:00', '11:00:00-12:20:00', '12:30:00-1:50:00', '2:00:00-3:20:00', '3:30:00-4:50:00'];
            foreach ($time_slots as $time) {
                echo "<tr><td>$time</td>";
                foreach (['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'] as $day) {
                    echo "<td>";
                    if (isset($schedule[$day])) {
                        foreach ($schedule[$day] as $class) {
                            if ($time === $class['s_time'] . '-' . $class['e_time']) {
                                echo $class['C_Code']."<br>";
                                echo "Room: " . $class['room_code'] . "<br>";
                            }
                        }
                    }
                    echo "</td>";
                }
                echo "</tr>";
            }
            ?>
        </table>
    </div>
</body>
</html>

