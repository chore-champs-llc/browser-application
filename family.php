<?php

	include_once "data/patterns/mysql_connect.php";
	session_start();
	$familyid = "";
	$familyName = "";
	$familyCode = "";
	$errhtml = "";
	$isParent = false;
	$actionsHtml = "";
	class familyMember {
		public $name = null;
		public $id = null;
		public $role = null;
	};
	$familyMembers = array();
	class group {
		public $name = null;
		public $id = null;
		public $memberList = null;
	};
	$groups = array();

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
			} else if ($errparam == "err_no_dep") {
				$errhtml = "<div id='errorbanner'>Cannot create groups without dependents</div>";
			} else if ($errparam == "err_grp_exi") {
				$errhtml = "<div id='errorbanner'>A group with that name already exists</div>";
			} else if ($errparam == "err_unk") {
				$errhtml = "<div id='errorbanner'>An unknown error has occurred</div>";
			} else if ($errparam == "err_tsk_exi") {
				$errhtml = "<div id='errorbanner'>A chore with that title already exists</div>";
			} else {
				$errhtml = "<div id='errorbanner'>Error handling failed</div>";
			};
		};
	};

	// Check if request to create family/group is sent
	if (isset($_POST["create"])) {
		if (isset($_POST["name"])) {
			$create = mysqli_real_escape_string($conn, $_POST["create"]);
			$name = mysqli_real_escape_string($conn, $_POST["name"]);
			if ($create == "family") {
				// Generate random family code
				$characters = "ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
				$code = '';
				$max = strlen($characters) - 1;
				for ($i = 0; $i < 5; $i++) {
			    	$code .= $characters[mt_rand(0, $max)];
				};
				// Verify that family code isn't already in use
				$sql = "SELECT * FROM family WHERE code = '$code';";
				$result = mysqli_query($conn, $sql);
				$rows = mysqli_num_rows($result);
				if ($rows > 0) {
					header("Location: dashboard.php?err=err_unk");
					exit;
				} else {
					if (!isset($_SESSION["familyid"])) {
						// Insert new family document
						$sql = "INSERT INTO family (code, name) VALUES ('$code', '$name');";
						if (!mysqli_query($conn, $sql)) {
							header("Location: dashboard.php?err=err_unk");
							exit;
						} else {
							$sql = "SELECT familyid FROM family WHERE code = '$code';";
							$result = mysqli_query($conn, $sql);
							$rows = mysqli_num_rows($result);
							if ($rows > 0) {
								while ($row = mysqli_fetch_assoc($result)) {
									$_SESSION["familyid"] = $row["familyid"];
								};
								// Insert user into family map
								$sql = "INSERT INTO familymap (familyid, userid, role) VALUES ('{$_SESSION["familyid"]}', '{$_SESSION["uid"]}', 'Parent');";
								if (!mysqli_query($conn, $sql)) {
									header("Location: dashboard.php?err=err_unk");
									exit;
								};
							} else {
								header("Location: dashboard.php?err=err_unk");
								exit;
							};
						};
					} else {
						header("Location: dashboard.php?err=err_inv_req");
						exit;
					};
				};
			} else if ($create == "group") {
				// TODO: Add group creation functionality
			} else {
				header("Location: dashboard.php?err=err_unk");
				exit;
			};
		} else {
			header("Location: dashboard.php?err=err_inv_req");
			exit;
		};
	}

	// Check if code to join family is sent
	if (isset($_POST["code"])) {
		$code = mysqli_real_escape_string($conn, $_POST["code"]);
		$sql = "SELECT * FROM family WHERE code = '$code';";
		$result = mysqli_query($conn, $sql);
		$rows = mysqli_num_rows($result);
		if ($rows > 0) {
			while ($row = mysqli_fetch_assoc($result)) {
				$_SESSION["familyid"] = $row["familyid"];
				$familyName = $row["name"];
			};
			// Check for requesting user type
			$type = "";
			if ($_SESSION["type"] == "chorechamp") {
				$type = "Chore Champ";
			} else if ($_SESSION["type"] == "parent") {
				$type = "Unverified Parent";
			} else {
				header("Location: dashboard.php?err=err_unk");
			}
			// Insert user into family map
			$sql = "INSERT INTO familymap (familyid, userid, role) VALUES ('{$_SESSION["familyid"]}', '{$_SESSION["uid"]}', '$type');";
			if (!mysqli_query($conn, $sql)) {
				header("Location: dashboard.php?err=err_unk");
				exit;
			};
		} else {
			header("Location: dashboard.php?err=err_inv_code");
			exit;
		};
	};

	// Determine access privileges of user and retrieve family members
	$sql = "SELECT * FROM familymap WHERE userid = '{$_SESSION["uid"]}';";
	$result = mysqli_query($conn, $sql);
	$rows = mysqli_num_rows($result);
	if ($rows > 0) {
		while ($row = mysqli_fetch_assoc($result)) {
			$familyid = $row["familyid"];
			$isParent = ($row["role"] == "Parent");
			// Get family name and code
			$sql = "SELECT name, code FROM family WHERE familyid = '{$row["familyid"]}';";
			$result = mysqli_query($conn, $sql);
			$rows = mysqli_num_rows($result);
			if ($rows > 0) {
				while ($familyRow = mysqli_fetch_assoc($result)) {
					$familyName = $familyRow["name"];
					$familyCode = $familyRow["code"];
				};
			} else {
				header("Location: dashboard.php?err=err_unk");
				exit;
			};
		};
	} else {
		header("Location: dashboard.php?err=err_unk");
		exit;
	};

	// Retrieve all groups within the family
	$sql = "SELECT * FROM usergroup WHERE familyid='$familyid';";
	$result = mysqli_query($conn, $sql);
	$rows = mysqli_num_rows($result);
	if ($rows > 0) {
		while ($row = mysqli_fetch_assoc($result)) {
			$group = new group();
			$group->name = $row["name"];
			$group->id = $row["groupid"];
			// Get a list of all members within the group
			$sql = "SELECT user.firstname, user.lastname FROM usergroupmap LEFT JOIN user ON user.userid=usergroupmap.userid WHERE groupid='{$row["groupid"]}';";
			$memberResult = mysqli_query($conn, $sql);
			$rows = mysqli_num_rows($result);
			if ($rows > 0) {
				while ($memberRow = mysqli_fetch_assoc($memberResult)) {
					if ($group->memberList == null) {
						$group->memberList = "{$memberRow["firstname"]} {$memberRow["lastname"]}";
					} else {
						$group->memberList .= ", {$memberRow["firstname"]} {$memberRow["lastname"]}";
					};
				};
			} else {
				header("Location: dashboard.php?err=err_unk");
				exit;
			};
			array_push($groups, $group);
		};
	};

	// Retrieve each family member's name and role
	$sql = "SELECT * FROM familymap WHERE familyid='$familyid';";
	$result = mysqli_query($conn, $sql);
	$rows = mysqli_num_rows($result);
	if ($rows > 0) {
		while ($row = mysqli_fetch_assoc($result)) {
			// Create new familyMember object
			$familyMember = new familyMember();
			// Add user's role and id to object
			$familyMember->role = $row["role"];
			$familyMember->id = $row["userid"];
			// Get user's name
			$sql = "SELECT firstname, lastname FROM user WHERE userid = '{$row["userid"]}';";
			$userResult = mysqli_query($conn, $sql);
			$rows = mysqli_num_rows($result);
			if ($rows > 0) {
				while ($userRow = mysqli_fetch_assoc($userResult)) {
					// Add user's name to object
					$familyMember->name = $userRow["firstname"] . " " . $userRow["lastname"];
				};
			} else {
				// Handle 'user not found' error
				header("Location: dashboard.php?err=err_unk");
				exit;
			};
			// Push familyMember to familyMembers array
			array_push($familyMembers, $familyMember);
		};
	} else {
		header("Location: dashboard.php?err=err_unk");
		exit;
	};

	if ($isParent) {
		$actionsHtml = "<ul>
				<li onclick=\"redirect('groupform.php')\">
					<div class='card-content'>
						<h3 class='card-title'>Create Group</h3>
					</div>
				</li>
				<li onclick=\"redirect('actions.php?location=dashboard.php&action=delete&resource=family&idtype=familyid&id={$_SESSION["familyid"]}')\">
					<div class='card-content'>
						<h3 class='card-title'>Delete Family</h3>
					</div>
				</li>
			</ul>";
	} else {
		$actionsHtml = "";
	};

?>

<!DOCTYPE html>
<html>
<head>
	<title><?php echo $familyName ?> - Chore Champs</title>
	<link rel="stylesheet" type="text/css" href="styles/family.css" />
	<link rel="shortcut icon" href="images/favicon.png" />
</head>
<body>
	<?php echo $errhtml; ?>
	<header id="showcase" class="grid">
		<div class="bg-image"></div>
		<div class="content-wrap">
			<h1><?php echo $familyName ?></h1>
			<p><?php echo ($isParent) ? "My Family Code: $familyCode" : ""; ?></p>
			<a href="#section-b" class="btn" onclick="redirect('dashboard.php')">Return to Dashboard</a>
		</div>
	</header>
	<main id="main">
		<!-- Section A (current family organization display area) -->
		<section id="section-a" class="grid">
			<div class="content-wrap">
				<h2 class="content-title">Family Members</h2>
				<div class="content-text">
					<ul>
						<?php
							foreach($familyMembers as $familyMember) {
								if ($isParent) {
									if ($familyMember->role == "Unverified Parent") {
										echo("<li><b>{$familyMember->name}</b> | {$familyMember->role} | <a class='verify' href='actions.php?location=family.php&action=update&resource=familymap&idtype=userid&id={$familyMember->id}&targetparam=role&targetvalue=Parent'>Verify</a> | <a class='remove' href='actions.php?location=family.php&action=delete&resource=familymap&idtype=userid&id={$familyMember->id}'>Remove</a></li>");
									} else {
										if ($familyMember->id == $_SESSION["uid"]) {
											echo("<li><b>{$familyMember->name}</b> | {$familyMember->role}</li>");
										} else {
											echo("<li><b>{$familyMember->name}</b> | {$familyMember->role} | <a class='remove' href='actions.php?location=family.php&action=delete&resource=familymap&idtype=userid&id={$familyMember->id}'>Remove</a></li>");
										};
									};
								} else {
									echo("<li><b>{$familyMember->name}</b> | {$familyMember->role}</li>");
								};
							};
						?>
					</ul>
				</div>
				<h2 class="content-title">Groups</h2>
				<div class="content-text">
					<?php echo (sizeOf($groups) == 0 ? "Your family doesn't currently have any groups." : ""); ?>
					<ul>
						<?php
							foreach ($groups AS $group) {
								if ($isParent) {
									echo("<li><b>{$group->name}:</b> {$group->memberList} | <a class='remove' href='actions.php?location=family.php&action=delete&resource=usergroup&idtype=groupid&id={$group->id}'>Remove</a></li>");
								} else {
									echo("<li><b>{$group->name}:</b> {$group->memberList}</li>");
								};
							};
						?>
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