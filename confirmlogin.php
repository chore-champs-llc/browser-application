<?php

	include_once "data/patterns/mysql_connect.php";
	date_default_timezone_set("America/New_York");

	// Initialize variables
	$username = mysqli_real_escape_string($conn, $_POST["username"]);
	$password = mysqli_real_escape_string($conn, $_POST["password"]);

	// Make db query and check that account info is correct
	if ($username != null && $password != null) {
		$sql = "SELECT * FROM user WHERE username = '$username';";
		$result = mysqli_query($conn, $sql);
		$rows = mysqli_num_rows($result);
		if ($rows > 0) {
			while ($row = mysqli_fetch_assoc($result)) {
				if (password_verify($password, $row['password'])) {
					// Create session and set session variables
					session_start();
					$sessionid = session_create_id();
					$date = date("Y-m-d H:i:s");
					$_SESSION["id"] = $sessionid;
					$_SESSION["uid"] = $row['userid'];
					$_SESSION["name"] = $row['firstname'];
					$_SESSION["type"] = $row['type'];
					// Get familyid
					$sql = "SELECT familyid FROM familymap WHERE userid = '{$_SESSION["uid"]}';";
					$result = mysqli_query($conn, $sql);
					$rows = mysqli_num_rows($result);
					if ($rows > 0) {
						while ($familyMapRow = mysqli_fetch_assoc($result)) {
							$_SESSION["familyid"] = $familyMapRow["familyid"];
						};
					} else {
						$_SESSION["familyid"] = null;
					};
					// Make a session entry
					$sql = "INSERT INTO session (sessionid, date) VALUES ('$sessionid', '$date');";
					if (mysqli_query($conn, $sql)) {
						header("Location: dashboard.php");
						exit;
					} else {
						header("Location: index.php?err=err_unk");
						exit;
					};
				} else {
					header("Location: index.php?err=err_inv_usr");
					exit;
				};
			};
		} else {
			header("Location: index.php?err=err_inv_usr");
			exit;
		};
	} else {
		header("Location: index.php?err=err_unk");
		exit;
	};

?>