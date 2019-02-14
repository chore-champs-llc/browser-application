<?php

	include_once "data/patterns/mysql_connect.php";
	session_start();

	// Verify that a valid session is in progress
	if (isset($_SESSION["id"])) {
		$sql = "SELECT * FROM session WHERE sessionid = '" . $_SESSION['id'] . "';";
		$result = mysqli_query($conn, $sql);
		$rows = mysqli_num_rows($result);
		if ($rows > 0) {
			// Session verified
		} else {
			header("Location: index.php?err=err_exp_ses");
			exit;
		};
	} else {
		header("Location: index.php?err=err_inv_ses");
		exit;
	};

	// Temporarily redirect until development is complete
	header("Location: dashboard.php?err=err_inc");
	exit;

?>

<!DOCTYPE html>
<html>
<head>
	<title>Rewards - Chore Champs</title>
	<link rel="stylesheet" type="text/css" href="styles/family.css" />
	<link rel="shortcut icon" href="images/favicon.png" />
</head>
<body>

</body>
</html>