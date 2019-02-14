<?php

	include_once "data/patterns/mysql_connect.php";
	session_start();

	// Initialize variables
	$titleHtml = "{$_SESSION["name"]}'s Chores - Chore Champs";
	$actionsHtml = "";
	$pageHeader = "Chore List";
	$sectionAHeader = "My Chores";
	$sectionAHtml = "";
	$isParent = false;
	$chores = array();
	class chore {
		public $id = null;
		public $title = null;
		public $description = null;
		public $dependentAssignees = array();
		public $groupAssignees = array();
		public $rewardpoints = null;
		public $deadline = null;
	};
	class assignee {
		public $name = null;
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

	// Check for error flags
	$url = $_SERVER["REQUEST_URI"];
	$parts = parse_url($url);
	$errhtml = "";
	if (isset($parts["query"])) {
		parse_str($parts["query"], $query);
		if (isset($query["err"])) {
			$errparam = $query["err"];
			if ($errparam == "err_inv_cmd") {
				$errhtml = "<div id='errorbanner'>Command request failed, please try again</div>";
			} else if ($errparam == "err_sel_dep") {
				$errhtml = "<div id='errorbanner'>You must select at least one assignee for each chore</div>";
			} else {
				$errhtml = "<div id='errorbanner'>Error handling failed</div>";
			};
		};
	};

	// Determine access privileges of user and retrieve family members
	$sql = "SELECT role FROM familymap WHERE userid = '{$_SESSION["uid"]}';";
	$result = mysqli_query($conn, $sql);
	$rows = mysqli_num_rows($result);
	if ($rows > 0) {
		while ($row = mysqli_fetch_assoc($result)) {
			$isParent = ($row["role"] == "Parent");
		};
	} else {
		header("Location: dashboard.php?err=err_no_fam");
		exit;
	};

	// Retrieve all tasks assigned within the family and the user/group assigned to them
	$sql = "SELECT * FROM task WHERE familyid='{$_SESSION["familyid"]}';";
	$result = mysqli_query($conn, $sql);
	$rows = mysqli_num_rows($result);
	if ($rows > 0) {
		while ($row = mysqli_fetch_assoc($result)) {
			$chore = new chore();
			$chore->id = $row["taskid"];
			$chore->title = $row["title"];
			$chore->description = $row["description"];
			$chore->rewardpoints = $row["rewardpoints"];
			$chore->deadline = $row["deadline"];

			// Get group and dependent assignees
			$dependentAssignees = array();
			$groupAssignees = array();
			$sql = "SELECT taskmap.groupid, taskmap.userid, user.firstname, user.lastname, usergroup.name FROM taskmap LEFT JOIN user ON user.userid=taskmap.userid LEFT JOIN usergroup ON usergroup.groupid=taskmap.groupid WHERE taskid='{$row["taskid"]}';";
			$mapResult = mysqli_query($conn, $sql);
			$rows = mysqli_num_rows($mapResult);
			if ($rows > 0) {
				while ($mapRow = mysqli_fetch_assoc($mapResult)) {
					if ($mapRow["userid"] != 0 && $mapRow["groupid"] != 0) {
						// Error: A single map entry should not contain a group and user
						header("Location: dashboard.php?err=err_unk");
						exit;
					};
					if ($mapRow["userid"] != 0) {
						// Task is mapped to a user
						$dependentAssignee = new assignee();
						$dependentAssignee->name = $mapRow["firstname"] . " " . $mapRow["lastname"];
						array_push($dependentAssignees, $dependentAssignee);
					} else if ($mapRow["groupid"] != 0) {
						// Task is mapped to a group
						$groupAssignee = new assignee();
						$groupAssignee->name = $mapRow["name"];
						array_push($groupAssignees, $groupAssignee);
					};
				};
			} else {
				// Error: task found with no assignees
				header("Location: dashboard.php?err=err_unk");
				exit;
			};

			$chore->dependentAssignees = $dependentAssignees;
			$chore->groupAssignees = $groupAssignees;
			array_push($chores, $chore);
		};
	} else {
		// No chores are currently assigned
	};

	// Edits element contents for parents (control panel)
	if ($isParent) {
		$titleHtml = "Chores Control Panel - Chore Champs";
		$actionsHtml = "<ul>
				<li onclick=\"redirect('choreform.php')\">
					<div class='card-content'>
						<h3 class='card-title'>Assign Chores</h3>
					</div>
				</li>
			</ul>";
		$sectionAHeader = "Listed Chores";
	};

	// Set innerHTML of the chore list
	if (sizeOf($chores) > 0) {
		foreach ($chores AS $chore) {

			// Make all assignees (individuals and groups) into lists separated by commas
			$dependentAssigneesString = " | <u>Individual Assignees:</u> ";
			$groupAssigneesString = " | <u>Group Assignees:</u> ";
			if (sizeOf($chore->dependentAssignees) > 0) {
				foreach ($chore->dependentAssignees AS $dependentAssignee) {
					if ($dependentAssigneesString == " | <u>Individual Assignees:</u> ") {
						$dependentAssigneesString .= "{$dependentAssignee->name}";
					} else {
						$dependentAssigneesString .= ", {$dependentAssignee->name}";
					};
				};
			};
			if (sizeOf($chore->groupAssignees) > 0) {
				foreach ($chore->groupAssignees AS $groupAssignee) {
					if ($groupAssigneesString == " | <u>Group Assignees:</u> ") {
						$groupAssigneesString .= "{$groupAssignee->name}";
					} else {
						$groupAssigneesString .= ", {$groupAssignee->name}";
					};
				};
			};

			if ($dependentAssigneesString == " | <u>Individual Assignees:</u> ") {
				$dependentAssigneesString = "";
			};
			if ($groupAssigneesString == " | <u>Group Assignees:</u> ") {
				$groupAssigneesString = "";
			};

			// Add a <li> element for the current chore
			$sectionAHtml .= "<li><b>{$chore->title}</b> | {$chore->rewardpoints} reward points | Deadline: {$chore->deadline}{$dependentAssigneesString}{$groupAssigneesString}";

			if ($isParent) {
				// TODO: Add points to user/members of group when 'Mark as Complete' is selected
				$sectionAHtml .= " | <a class='remove' href='actions.php?location=chores.php&action=delete&resource=task&idtype=taskid&id={$chore->id}'>Delete</a> | <a class='verify' href='actions.php?location=chores.php&action=delete&resource=task&idtype=taskid&id={$chore->id}'>Mark as Complete</a></li><br>";
			} else {
				$sectionAHtml .= "</li><br>";
			};

		};
	} else {
		// No chores currently assigned, display default text
		$sectionAHtml = "There are no chores to be completed at this time.";
	};

?>

<!DOCTYPE html>
<html>
<head>
	<title><?php echo $titleHtml ?></title>
	<link rel="stylesheet" type="text/css" href="styles/family.css" />
	<link rel="shortcut icon" href="images/favicon.png" />
</head>
<body>
	<?php echo $errhtml; ?>
	<header id="showcase" class="grid">
		<div class="bg-image"></div>
		<div class="content-wrap">
			<h1>Chore List</h1>
			<p></p>
			<a href="#section-b" class="btn" onclick="redirect('dashboard.php')">Return to Dashboard</a>
		</div>
	</header>
	<main id="main">
		<!-- Section A (current family organization display area) -->
		<section id="section-a" class="grid">
			<div class="content-wrap">
				<h2 class="content-title"><?php echo $sectionAHeader ?></h2>
				<div class="content-text">
					<ul>
						<?php echo $sectionAHtml ?>
					</ul>
				</div>
			</div>
		</section>
		<!-- Section B (administrative actions area) -->
		<section id="section-b" class="grid"><?php echo $actionsHtml ?></section>
		<!-- Section C (buffer area) -->
		<section id="section-c" class="grid">
			<div class="content-wrap">
				<h2 class="content-title"></h2>
				<div class="content-text">
					<p></p>
				</div>
			</div>
		</section>
	</main>
	<!-- Footer -->
	<footer id="main-footer" class="grid">
		<div>Copyright © 2019 Chore Champs LLC</div>
		<div><a href="https://chorechamps.net" target="_blank">Visit our site</a><a href="https://chorechamps.net/?page_id=48" target="_blank">Support hours</a><a href="signout.php">Sign out</a></div>
	</footer>
	<script src="scripts/main.js"></script>
</body>
</html>