<?php

	session_start();
	include_once "data/patterns/mysql_connect.php";

	if (isset($_SESSION["id"])) {
		$sql = "DELETE FROM session WHERE sessionid = '" . $_SESSION['id'] . "';";
		if (mysqli_query($conn, $sql)) {
			session_destroy();
			header("Location: index.php");
		} else {
			session_destroy();
			header("Location: index.php?err=err_unk");
		};
	} else {
		session_destroy();
		header("Location: index.php?err=err_unk");
	};

	exit;
?>
