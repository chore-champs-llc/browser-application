<?php

	include_once "data/patterns/mysql_connect.php";
	date_default_timezone_set("America/New_York");

	// Initialize variables
	$type = mysqli_real_escape_string($conn, $_POST["type"]);
	$username = mysqli_real_escape_string($conn, $_POST["username"]);
	$firstname = mysqli_real_escape_string($conn, $_POST["firstname"]);
	$lastname = mysqli_real_escape_string($conn, $_POST["lastname"]);
	$password = mysqli_real_escape_string($conn, $_POST["password"]);
	$confirmpassword = mysqli_real_escape_string($conn, $_POST["confirmpassword"]);
	$hashedpass = password_hash($password, PASSWORD_DEFAULT);

	// Verify that entered passwords match eachother
	if ($password == $confirmpassword) {
		// Check that user doesn't already exist
		$sql = "SELECT * FROM user WHERE username = '$username';";
		$result = mysqli_query($conn, $sql);
		$rows = mysqli_num_rows($result);
		if ($rows > 0) {
			header("Location: index.php?err=err_usr_exi");
		} else {
			// Create user document
			$sql = "INSERT INTO user (type, firstname, lastname, username, password) VALUES ('$type', '$firstname', '$lastname', '$username', '$hashedpass');";
			if (mysqli_query($conn, $sql)) {
				$sql = "SELECT userid FROM user WHERE username = '$username';";
				$result = mysqli_query($conn, $sql);
				$rows = mysqli_num_rows($result);
				if ($rows > 0) {
					$uid = null;
					while ($row = mysqli_fetch_assoc($result)) {
						$uid = $row["userid"];
					};
					// Start session and set session variables
					session_start();
					$sessionid = session_create_id();
					$date = date("Y-m-d H:i:s");
					$_SESSION["id"] = $sessionid;
					$_SESSION["uid"] = $uid;
					$_SESSION["name"] = $firstname;
					$_SESSION["type"] = $type;
					$_SESSION["familyid"] = null;
					// Make a session entry
					$sql = "INSERT INTO session (sessionid, date) VALUES ('$sessionid', '$date');";
					if (mysqli_query($conn, $sql)) {
						header("Location: dashboard.php");
					} else {
						header("Location: index.php?err=err_unk");
					};
				} else {
					header("Location: index.php?err=err_unk");
					exit;
				};
			} else {
				header("Location: index.php?err=err_unk");
			};
		};
	} else {
		header("Location: index.php?err=err_unk");
	};

?>