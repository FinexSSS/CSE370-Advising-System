<?php
session_start();

// Redirect if not logged in or if the Temp_ID is not set
if (!isset($_SESSION['Temp_ID'])) {
    header("Location: login.php");
    exit;
}

include('DBconnect.php');

$temp_id = $_SESSION['Temp_ID'];
$message = '';

// Check if the user has already submitted an application
$application_check_query = "SELECT * FROM applications WHERE Temp_ID = ?";
$stmt = $conn->prepare($application_check_query);
$stmt->bind_param("s", $temp_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows > 0) {
    header("Location: status.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Assuming all the $_POST['field'] variables are set and sanitized
    // Handle file upload and insertion logic as previously described

    $file_name = $_FILES['documents']['name'];
    $file_tmp = $_FILES['documents']['tmp_name'];
    $file_type = $_FILES['documents']['type'];
    $file_ext = strtolower(end(explode('.', $_FILES['documents']['name'])));
    $extensions = array("pdf", "docx", "jpeg", "jpg", "png");

    if (!in_array($file_ext, $extensions)) {
        $message = "Extension not allowed, please choose a PDF, DOCX, JPEG, JPG, or PNG file.";
    } else {
        move_uploaded_file($file_tmp, "documents/" . $file_name);
        $sql = "INSERT INTO applications (Temp_ID, Education_Level_SSC, Maths_Grade_SSC, English_Grade_SSC, Physics_Grade_SSC, Education_Level_HSC, Maths_Grade_HSC, Physics_Grade_HSC, Phone, Parent_Name, Parent_Phone, Parent_Occupation, Document_Path) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sssssssssssss", $temp_id, $_POST['education_level_ssc'], $_POST['maths_grade_ssc'], $_POST['english_grade_ssc'], $_POST['physics_grade_ssc'], $_POST['education_level_hsc'], $_POST['maths_grade_hsc'], $_POST['physics_grade_hsc'], $_POST['phone'], $_POST['parent_name'], $_POST['parent_phone'], $_POST['parent_occupation'], $file_name);
        
        if ($stmt->execute()) {
            $update_sql = "UPDATE new SET status = 1 WHERE Temp_ID = ?";
            $update_stmt = $conn->prepare($update_sql);
            $update_stmt->bind_param("s", $temp_id);
            $update_stmt->execute();
            $message = "Application submitted successfully!";
        } else {
            $message = "Failed to submit application.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta viewport="width=device-width, initial-scale=1.0">
    <title>Application Form</title>
</head>
    <h1>Application Form</h1>
    <style>
        body {
            font-family: 'Arial', sans-serif;
            background: #f8f9fa;
            margin: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            flex-direction: column; /* Adjusts layout direction */
            padding: 10px;
            min-height: 100vh; /* Ensures full viewport height */
        }

        form {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 4px 16px rgba(0,0,0,0.1);
            width: 100%;
            max-width: 450px; /* Optimized width for form readability */
        }

        h1 {
            color: #333;
            text-align: center;
            margin-bottom: 20px;
        }

        label {
            font-weight: bold;
            margin-top: 10px;
            display: block; /* Ensures labels appear above inputs */
        }

        input[type="text"], input[type="file"], select {
            width: 100%;
            padding: 8px;
            margin-top: 5px;
            margin-bottom: 20px;
            border: 1px solid #ccc;
            border-radius: 4px;
            box-sizing: border-box;
        }

        input[type="submit"] {
            width: 100%;
            padding: 10px;
            border: none;
            background-color: #007bff;
            color: white;
            font-size: 16px;
            cursor: pointer;
            border-radius: 4px;
            transition: background-color 0.3s;
        }

        input[type="submit"]:hover {
            background-color: #0056b3;
        }

        .payment-slip {
            background-color: #e9ecef;
            padding: 15px;
            border-radius: 8px;
            margin-top: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            text-align: center;
        }

        .payment-slip p {
            margin: 10px 0;
            line-height: 1.5;
        }

        /* Responsive adjustments for different screen sizes */
        @media (max-width: 768px) {
            form {
                margin: 20px; /* Adds margin around the form on smaller devices */
            }
        }

        @media (max-width: 480px) {
            form {
                padding: 15px; /* Reduces padding in very small screens */
                max-width: 100%; /* Allows form to expand to full width */
            }
            h1 {
                font-size: 20px; /* Reduces font size for smaller devices */
            }
        }
    </style>
    <p><?php echo $message; ?></p>
    <form method="post" enctype="multipart/form-data">
        <label for="education_level_ssc">Select your SSC or O Level:</label>
        <select id="education_level_ssc" name="education_level_ssc">
            <option value="SSC">SSC</option>
            <option value="O Level">O Level</option>
        </select><br>
        Maths Grade:
        <select name="maths_grade_ssc">
            <option>A</option>
            <option>B</option>
            <option>C</option>
            <option>D</option>
        </select><br>
        English Grade:
        <select name="english_grade_ssc">
            <option>A</option>
            <option>B</option>
            <option>C</option>
            <option>D</option>
        </select><br>
        Physics Grade:
        <select name="physics_grade_ssc">
            <option>A</option>
            <option>B</option>
            <option>C</option>
            <option>D</option>
        </select><br>

        <label for="education_level_hsc">Select your HSC or A Level:</label>
        <select id="education_level_hsc" name="education_level_hsc">
            <option value="HSC">HSC</option>
            <option value="A Level">A Level</option>
        </select><br>
        Maths Grade:
        <select name="maths_grade_hsc">
            <option>A</option>
            <option>B</option>
            <option>C</option>
            <option>D</option>
        </select><br>
        Physics Grade:
        <select name="physics_grade_hsc">
            <option>A</option>
            <option>B</option>
            <option>C</option>
            <option>D</option>
        </select><br>

        Phone Number: <input type="text" name="phone" required><br>
        Parent's Name: <input type="text" name="parent_name" required><br>
        Parent's Phone: <input type="text" name="parent_phone" required><br>
        Parent's Occupation: <input type="text" name="parent_occupation" required><br>
        <input type="submit" value="Submit">
    </form>

    <?php
        if ($message === "Application submitted successfully!") {
            echo "<div><h2>Payment Slip</h2>";
            echo "<p>Please pay BDT 1500 to the following bank account and save your transaction ID for future reference.</p>";
            echo "<p>Bank: BRAC Bank</p>";
            echo "<p>Account Number: 123456789</p>";
            echo "<p>Account Name: BRACU Admissions</p></div>";
        }
    ?>
</body>
</html>
