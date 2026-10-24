<?php
// Start the session
session_start();

// first of all, we need to connect to the database
require_once('DBconnect.php');

// we need to check if the input in the form textfields are not empty
if(isset($_POST['email']) && isset($_POST['password'])){
	// write the query to check if this username and password exists in our database
	$u = $_POST['email'];
	$p = $_POST['password'];
	$sql = "SELECT * FROM users WHERE email = '$u' AND password = '$p'";
	
	//Execute the query 
	$result = mysqli_query($conn, $sql);
	
	//check if it returns an empty set
	if(mysqli_num_rows($result) != 0 ){
		echo "LET HIM ENTER";
		// Fetch the role from the database
		$row = mysqli_fetch_assoc($result);
		
		$User_ID = $row['User_ID'];
		// Store the user's role in the session variable
		$_SESSION['User_ID'] = $User_ID;

		$role = $row['Role_ID'];
		if($role == 1){
			header("Location: student.php");
			exit(); // Ensure no further code execution after redirect
		}
		elseif($role == 2){
			header("Location: faculty.php");
			exit(); // Ensure no further code execution after redirect
		}
		elseif($role == 3){
			header("Location: admin.php");
			exit(); // Ensure no further code execution after redirect
		}

	}
	else{
		$sql = "SELECT * FROM new WHERE email = '$u' AND password = '$p'";
		$result = mysqli_query($conn, $sql);
		if(mysqli_num_rows($result) != 0 ){
			echo "LET HIM ENTER";
			// Fetch the role from the database
			$row = mysqli_fetch_assoc($result);
			
			$Temp_ID = $row['Temp_ID'];
			// Store the user's role in the session variable
			$_SESSION['Temp_ID'] = $Temp_ID;
			header("Location: new.php");
			exit();
		}
		echo "Username or Password is wrong";
		header("Location: admin.php");
		exit(); // Ensure no further code execution after redirect
	}
	
}
?>
