<?php
require_once 'auth.php';

// Already logged in: go straight to the right page.
if (isset($_SESSION['Role_ID'])) {
    header("Location: " . dashboard_for($_SESSION['Role_ID']));
    exit;
}
if (isset($_SESSION['Temp_ID'])) {
    header("Location: new.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BRACU Advising</title>
    <link href="style.css" rel="stylesheet">
</head>
<body>
    <header id="header">
        <div class="header-bar">
            <h1>ADVISING</h1>
            <nav class="header-links">
                <a href="cse_details.php">Courses</a>
                <a href="register.php">Apply</a>
            </nav>
        </div>
    </header>

    <section class="card" id="login-section">
        <h2>Log In</h2>
        <?php if (isset($_GET['error'])): ?>
            <p class="notice error">Wrong email or password.</p>
        <?php endif; ?>
        <form action="login.php" method="post">
            <div class="form-group">
                <label for="email">Email:</label>
                <input type="email" id="email" name="email" class="form-control" required>
            </div>
            <div class="form-group">
                <label for="password">Password:</label>
                <input type="password" id="password" name="password" class="form-control" required>
            </div>
            <button type="submit" class="btn">Login</button>
        </form>
        <p class="muted">New applicant? <a href="register.php">Create an account</a></p>
    </section>
</body>
</html>
