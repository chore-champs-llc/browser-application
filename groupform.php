<?php

	include_once "data/patterns/mysql_connect.php";
	session_start();

	// Initialize variables
	$userid = null;
	$optionsHtml = "";
	class dependent {
		public $name = null;
		public $id = null;
	};
	$dependents = array();

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

	// Verify that requesting user is a parent
	$sql = "SELECT role FROM familymap WHERE familyid={$_SESSION["familyid"]} AND userid={$_SESSION["uid"]};";
	$result = mysqli_query($conn, $sql);
	$rows = mysqli_num_rows($result);
	if ($rows > 0) {
		while ($row = mysqli_fetch_assoc($result)) {
			if ($row["role"] != "Parent") {
				header("Location: dashboard.php?err=err_no_acc");
				exit;
			};
		};
	} else {
		header("Location: family.php?err=err_inv_req");
		exit;
	};

	// Get all dependents under parent user
	$sql = "SELECT user.userid, user.type, user.firstname, user.lastname FROM familymap LEFT JOIN user ON user.userid=familymap.userid WHERE familyid={$_SESSION["familyid"]};";
	$result = mysqli_query($conn, $sql);
	$rows = mysqli_num_rows($result);
	if ($rows > 0) {
		while ($row = mysqli_fetch_assoc($result)) {
			if ($row["type"] == "chorechamp") {
				$dependent = new dependent();
				$dependent->name = $row["firstname"] . " " . $row["lastname"];
				$dependent->id = $row["userid"];
				array_push($dependents, $dependent);
			};
		};
		// Set value of options input
		$dependentsLen = sizeOf($dependents);
		if ($dependentsLen == 0) {
			header("Location: family.php?err=err_no_dep");
		};
		$optionsHtml = "<select id='members' name='members[]' size='$dependentsLen' multiple required>";
		foreach ($dependents AS $dependent) {
			$optionsHtml .= "<option value='{$dependent->id}'>{$dependent->name}</option>";
		};
		$optionsHtml .= "</select>";
	} else {
		header("Location: family.php?err=err_no_dep");
		exit;
	};

?>

<!DOCTYPE html>
<html>
<head>
	<title>Group Creation Form - Chore Champs</title>
	<link rel="stylesheet" type="text/css" href="styles/form.css" />
	<link rel="shortcut icon" href="images/favicon.png" />
</head>
<body>
	<div id="wrapper">
		<main>
			<div class="formcontainer">
				<form method="post" action="actions.php">
					<input type="hidden" name="location" value="family.php" />
					<input type="hidden" name="action" value="create" />
					<input type="hidden" name="resource" value="usergroup" />
					<label for="name">Group Name</label>
					<input type="text" placeholder="Give the group a name..." id="name" name="name" required />
					<label for="members">Group Members</label>
					<?php echo $optionsHtml; ?>
					<input type="submit" value="Create Group" />
				</form>
			</div>
		</main>
	</div>
	<script id="jquery" type="text/javascript" src="http://code.jquery.com/jquery-1.7.1.min.js"></script>
	<script src="scripts/main.js"></script>
</body>
</html>