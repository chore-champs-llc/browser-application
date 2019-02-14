<?php

	include_once "data/patterns/mysql_connect.php";
	date_default_timezone_set("America/New_York");
	session_start();

	// Initialize variables
	$optionsHtml = "";
	$date = date("Y-m-d H:i");
	$day = (int)substr($date, 8, -6);
	$day += 1;
	$modifiedDate = substr_replace($date, "{$day}T00:00", 8, 8);
	$dependents = array();
	$groups = array();
	$dependentOptionsHtml = "";
	$groupOptionsHtml = "";
	class dependent {
		public $name = null;
		public $id = null;
	};
	class group {
		public $name = null;
		public $id = null;
	};

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

	// Get all dependents under parent user's family
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
	};

	// Get all groups under parent user's family
	$sql = "SELECT usergroup.groupid, usergroup.name FROM family LEFT JOIN usergroup ON usergroup.familyid=family.familyid WHERE family.familyid={$_SESSION["familyid"]};";
	$result = mysqli_query($conn, $sql);
	$rows = mysqli_num_rows($result);
	if ($rows > 0) {
		while ($row = mysqli_fetch_assoc($result)) {
			$group = new group();
			$group->name = $row["name"];
			$group->id = $row["groupid"];
			array_push($groups, $group);
		};
	};

	// Redirects to chores page if there is an invalid request
	if (sizeOf($dependents) == 0 && sizeOf($groups) == 0) {
		header("Location: chores.php?err=err_no_dep");
		exit;
	};

	// Set value of options (dependent assignees) input
	$dependentsLen = sizeOf($dependents);
	$dependentOptionsHtml = "<select id='dependentassignees' name='dependentassignees[]' size='$dependentsLen' multiple>";
	foreach ($dependents AS $dependent) {
		$dependentOptionsHtml .= "<option value='{$dependent->id}'>{$dependent->name}</option>";
	};
	$dependentOptionsHtml .= "</select>";

	// Set value of options (group assignees) input
	$groupsLen = sizeOf($groups);
	$groupOptionsHtml = "<select id='groupassignees' name='groupassignees[]' size='$groupsLen' multiple>";
	foreach ($groups AS $group) {
		$groupOptionsHtml .= "<option value='{$group->id}'>{$group->name}</option>";
	};
	$groupOptionsHtml .= "</select>";

?>

<!DOCTYPE html>
<html>
<head>
	<title>Chore Assignment Form - Chore Champs</title>
	<link rel="stylesheet" type="text/css" href="styles/form.css" />
	<link rel="shortcut icon" href="images/favicon.png" />
</head>
<body>
	<div id="wrapper">
		<main>
			<div class="formcontainer">
				<form method="post" action="actions.php">
					<input type="hidden" name="location" value="chores.php" />
					<input type="hidden" name="action" value="create" />
					<input type="hidden" name="resource" value="task" />

					<label for="title">Chore Title</label>
					<input type="text" placeholder='ex. "Clean the dishes"' id="title" name="title" required />

					<label for="rewardspoint">Reward Points</label>
					<input type="number" id="rewardpoints" name="rewardpoints" value="100" min="1" max="10000" required />

					<label for="deadline">Deadline</label>
					<input type="datetime-local" id="deadline" name="deadline" required value=<?php echo $modifiedDate; ?> />

					<label for="description">Description</label>
					<textarea placeholder="Enter a short description of what to do..." id="description" name="description"></textarea>

					<label for="dependentassignees">Select Dependent Assignees</label>
					<?php echo $dependentOptionsHtml; ?>

					<label for="groupassignees">Select Group Assignees</label>
					<?php echo $groupOptionsHtml; ?>

					<input type="submit" value="Assign Chore" />
				</form>
			</div>
		</main>
	</div>
	<script id="jquery" type="text/javascript" src="http://code.jquery.com/jquery-1.7.1.min.js"></script>
	<script src="scripts/main.js"></script>
</body>
</html>