<?php
include('DBconnect.php');

function RandomString() {
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $randstring = '';
    for ($i = 0; $i < 10; $i++) {
        $randstring .= $characters[rand(0, strlen($characters) - 1)];
    }
    return $randstring;
}

if(isset($_POST['regibtn'])) {
    $roleid = 0; // Assuming role ID for students
    $name = $_POST['fname']; // corrected to 'fname' as per the form
    $email = $_POST['email'];
    $password = $_POST['password'];
    $conpassword = $_POST['conpassword'];
    $phone = $_POST['phone'];
    $stdid = RandomString();
    $query = "SELECT Temp_ID FROM new WHERE Temp_ID = '$stdid'";
    $data = mysqli_query($conn, $query);
    while(mysqli_num_rows($data) > 0){
        $stdid = RandomString();
        $query = "SELECT Temp_ID FROM new WHERE Temp_ID = '$stdid'";
        $data = mysqli_query($conn, $query);
    }

    // Validate password strength
    $uppercase = preg_match('@[A-Z]@', $password);
    $lowercase = preg_match('@[a-z]@', $password);
    $number    = preg_match('@[0-9]@', $password);
    $specialChars = preg_match('@[^\w]@', $password);

    if(!$uppercase || !$lowercase || !$number || !$specialChars || strlen($password) < 8) {
        echo "Password should be at least 8 characters in length and should include at least one upper case letter, one number, and one special character.";
    } elseif ($password != $conpassword) {
        echo "Password and confirm password do not match.";
    } else {
        $query = "INSERT INTO new (Temp_ID, role_id, email, password, name, phone) VALUES ('$stdid', '$roleid', '$email', '$password', '$name', '$phone')";
        $data = mysqli_query($conn, $query);
        if($data) {
            echo "Registered Successfully.";
        } else {
            echo "Registration Failed.";
        }
    }
}
?>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/main.css">
    <title>Registration Page</title>
</head>
<body>
    <div class='registration form'>
        Student Registration
    </div>
    <div>
    <form action="#" method="POST">
        <div class='form'>
            <div class='input-field'>
                <label>Name</label>
                <input type='text' class='input' name='fname' required>
            </div>
            <div class='input-field'>
                <label>Email</label>
                <input type='email' class='input' name='email' required>
            </div>
            <div class='input-field'>
                <label>Password</label>
                <input type='password' class='input' name='password' pattern="(?=.*\d)(?=.*[a-z])(?=.*[A-Z])(?=.*[^\w]).{8,}" title="Must contain at least one number and one uppercase and lowercase letter, and at least 8 or more characters" required>
            </div>
            <div class='input-field'>
                <label>Confirm Password</label>
                <input type='password' class='input' name='conpassword' required>
            </div>
            <div class='input-field'>
                <label>Phone</label>
                <input type='text' class='input' name='phone' required>
            </div>
            <div class='input-field'>
                <input type='submit' value='Register' class='btn' name='regibtn'>
            </div>
        </div>
    </form>
    </div>
</body>
</html>
