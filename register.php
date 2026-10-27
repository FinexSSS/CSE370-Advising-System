<?php
require_once 'auth.php';

// A random 10-character temporary ID for a new applicant.
function RandomString() {
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $randstring = '';
    for ($i = 0; $i < 10; $i++) {
        $randstring .= $characters[random_int(0, strlen($characters) - 1)];
    }
    return $randstring;
}

$error = '';
$success = '';

if (isset($_POST['regibtn'])) {
    $roleid = 0; // Applicants have no role until they are admitted
    $name = trim($_POST['fname'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $conpassword = $_POST['conpassword'] ?? '';
    $phone = trim($_POST['phone'] ?? '');

    // Validate password strength
    $uppercase = preg_match('@[A-Z]@', $password);
    $lowercase = preg_match('@[a-z]@', $password);
    $number    = preg_match('@[0-9]@', $password);
    $specialChars = preg_match('@[^\w]@', $password);

    // The email must not already belong to an applicant or a user.
    $stmt = $conn->prepare("SELECT 1 FROM new WHERE Email = ? UNION SELECT 1 FROM users WHERE Email = ?");
    $stmt->bind_param("ss", $email, $email);
    $stmt->execute();
    $email_taken = $stmt->get_result()->num_rows > 0;

    if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter your name and a valid email address.";
    } elseif ($email_taken) {
        $error = "An account with this email already exists. Please log in instead.";
    } elseif (!$uppercase || !$lowercase || !$number || !$specialChars || strlen($password) < 8) {
        $error = "Password should be at least 8 characters in length and should include at least one upper case letter, one number, and one special character.";
    } elseif ($password !== $conpassword) {
        $error = "Password and confirm password do not match.";
    } elseif (!preg_match('/^\+?[0-9 -]{7,15}$/', $phone)) {
        $error = "Please enter a valid phone number.";
    } else {
        // Pick a temporary ID that is not in use yet
        $stmt = $conn->prepare("SELECT 1 FROM new WHERE Temp_ID = ?");
        do {
            $stdid = RandomString();
            $stmt->bind_param("s", $stdid);
            $stmt->execute();
        } while ($stmt->get_result()->num_rows > 0);

        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("INSERT INTO new (Temp_ID, Role_ID, Email, PASSWORD, name, phone, status) VALUES (?, ?, ?, ?, ?, ?, 0)");
        $stmt->bind_param("sissss", $stdid, $roleid, $email, $hash, $name, $phone);
        if ($stmt->execute()) {
            $success = "Registered successfully. You can now log in and submit your application.";
        } else {
            $error = "Registration failed. Please try again.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registration Page</title>
    <link href="style.css" rel="stylesheet">
</head>
<body>
    <header id="header">
        <div class="header-bar">
            <h1>ADVISING</h1>
            <nav class="header-links">
                <a href="cse_details.php">Courses</a>
                <a href="home.php">Log In</a>
            </nav>
        </div>
    </header>

    <section class="card">
        <h2>Student Registration</h2>
        <?php if ($error): ?><p class="notice error"><?php echo e($error); ?></p><?php endif; ?>
        <?php if ($success): ?><p class="notice success"><?php echo e($success); ?> <a href="home.php">Log in</a></p><?php endif; ?>
        <form method="POST">
            <div class="form-group">
                <label for="fname">Name</label>
                <input type="text" id="fname" class="form-control" name="fname" value="<?php echo e($_POST['fname'] ?? ''); ?>" required>
            </div>
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" class="form-control" name="email" value="<?php echo e($_POST['email'] ?? ''); ?>" required>
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" class="form-control" name="password" pattern="(?=.*\d)(?=.*[a-z])(?=.*[A-Z])(?=.*[^\w]).{8,}" title="At least 8 characters with an uppercase letter, a lowercase letter, a number and a special character" required>
            </div>
            <div class="form-group">
                <label for="conpassword">Confirm Password</label>
                <input type="password" id="conpassword" class="form-control" name="conpassword" required>
            </div>
            <div class="form-group">
                <label for="phone">Phone</label>
                <input type="tel" id="phone" class="form-control" name="phone" value="<?php echo e($_POST['phone'] ?? ''); ?>" required>
            </div>
            <input type="submit" value="Register" class="btn" name="regibtn">
        </form>
    </section>
</body>
</html>
