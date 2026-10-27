<?php
require_once 'auth.php';
require_applicant();

const MAX_UPLOAD_BYTES = 5 * 1024 * 1024;

// Allowed document types, checked against the file's real content, not just its name.
const ALLOWED_UPLOADS = [
    'pdf' => ['application/pdf'],
    'docx' => ['application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'application/zip'],
    'jpg' => ['image/jpeg'],
    'jpeg' => ['image/jpeg'],
    'png' => ['image/png'],
];

$temp_id = $_SESSION['Temp_ID'];
$message = '';
$submitted = false;

// New applicants (status 0) and rejected applicants (status 4) may apply; everyone
// else already has an application in progress.
$stmt = $conn->prepare("SELECT status FROM new WHERE Temp_ID = ?");
$stmt->bind_param("s", $temp_id);
$stmt->execute();
$status = (int) ($stmt->get_result()->fetch_assoc()['status'] ?? 0);
if ($status !== 0 && $status !== 4) {
    header("Location: status.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $grades = ['A', 'B', 'C', 'D'];
    $choices_valid = in_array($_POST['education_level_ssc'] ?? '', ['SSC', 'O Level'], true)
        && in_array($_POST['education_level_hsc'] ?? '', ['HSC', 'A Level'], true);
    foreach (['maths_grade_ssc', 'english_grade_ssc', 'physics_grade_ssc', 'maths_grade_hsc', 'physics_grade_hsc'] as $field) {
        $choices_valid = $choices_valid && in_array($_POST[$field] ?? '', $grades, true);
    }
    $text_fields = ['phone', 'parent_name', 'parent_phone', 'parent_occupation'];
    foreach ($text_fields as $field) {
        $choices_valid = $choices_valid && trim($_POST[$field] ?? '') !== '';
    }

    $file = $_FILES['documents'] ?? null;
    $ext = $file ? strtolower(pathinfo($file['name'], PATHINFO_EXTENSION)) : '';

    if (!$choices_valid) {
        $message = "Please fill in every field.";
    } elseif (!$file || $file['error'] === UPLOAD_ERR_NO_FILE) {
        $message = "Please attach your documents.";
    } elseif ($file['error'] === UPLOAD_ERR_INI_SIZE || $file['error'] === UPLOAD_ERR_FORM_SIZE || $file['size'] > MAX_UPLOAD_BYTES) {
        $message = "The file is too large. The limit is 5 MB.";
    } elseif ($file['error'] !== UPLOAD_ERR_OK) {
        $message = "The upload failed. Please try again.";
    } elseif (!isset(ALLOWED_UPLOADS[$ext])
        || !in_array((new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']), ALLOWED_UPLOADS[$ext], true)) {
        $message = "File type not allowed, please choose a PDF, DOCX, JPEG, JPG, or PNG file.";
    } else {
        // Store under a random name so uploads cannot overwrite each other or
        // be guessed; documents/.htaccess keeps the folder off the web.
        $dir = __DIR__ . '/documents';
        if (!is_dir($dir)) {
            mkdir($dir, 0750, true);
        }
        $stored_name = bin2hex(random_bytes(16)) . '.' . $ext;

        if (!move_uploaded_file($file['tmp_name'], "$dir/$stored_name")) {
            $message = "Could not save the file. Please try again.";
        } else {
            $conn->begin_transaction();
            try {
                $sql = "INSERT INTO applications (Temp_ID, Education_Level_SSC, Maths_Grade_SSC, English_Grade_SSC, Physics_Grade_SSC, Education_Level_HSC, Maths_Grade_HSC, Physics_Grade_HSC, Phone, Parent_Name, Parent_Phone, Parent_Occupation, Document_Path) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                $stmt = $conn->prepare($sql);
                $phone = trim($_POST['phone']);
                $parent_name = trim($_POST['parent_name']);
                $parent_phone = trim($_POST['parent_phone']);
                $parent_occupation = trim($_POST['parent_occupation']);
                $stmt->bind_param("sssssssssssss", $temp_id, $_POST['education_level_ssc'], $_POST['maths_grade_ssc'], $_POST['english_grade_ssc'], $_POST['physics_grade_ssc'], $_POST['education_level_hsc'], $_POST['maths_grade_hsc'], $_POST['physics_grade_hsc'], $phone, $parent_name, $parent_phone, $parent_occupation, $stored_name);
                $stmt->execute();

                $update_stmt = $conn->prepare("UPDATE new SET status = 1 WHERE Temp_ID = ?");
                $update_stmt->bind_param("s", $temp_id);
                $update_stmt->execute();

                $conn->commit();
                $submitted = true;
                $message = "Application submitted successfully!";
            } catch (mysqli_sql_exception $e) {
                $conn->rollback();
                unlink("$dir/$stored_name");
                error_log("Application insert failed: " . $e->getMessage());
                $message = "Failed to submit application.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Application Form</title>
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
</head>
<body>
    <h1>Application Form</h1>
    <p><?php echo e($message); ?></p>

    <?php if ($submitted): ?>
        <div class="payment-slip">
            <h2>Payment Slip</h2>
            <p>Please pay BDT 1500 to the following bank account and save your transaction ID for future reference.</p>
            <p>Bank: BRAC Bank</p>
            <p>Account Number: 123456789</p>
            <p>Account Name: BRACU Admissions</p>
            <p><a href="status.php">Check your application status</a></p>
        </div>
    <?php else: ?>
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

        <label for="documents">Certificates and transcripts (PDF, DOCX, JPG or PNG, up to 5 MB):</label>
        <input type="file" id="documents" name="documents" accept=".pdf,.docx,.jpg,.jpeg,.png" required>

        <input type="submit" value="Submit">
    </form>
    <?php endif; ?>
</body>
</html>
