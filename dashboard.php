<?php

	include_once "data/patterns/mysql_connect.php";
	session_start();

	// Check for error flags
	$url = $_SERVER["REQUEST_URI"];
	$parts = parse_url($url);
	$errhtml = "";
	if (isset($parts["query"])) {
		parse_str($parts["query"], $query);
		if (isset($query["err"])) {
			$errparam = $query["err"];
			if ($errparam == "err_exp_ses") {
				$errhtml = "<div id='errorbanner'>Your session has expired</div>";
			} else if ($errparam == "err_inv_ses") {
				$errhtml = "<div id='errorbanner'>An invalid session was detected</div>";
			} else if ($errparam == "err_unk") {
				$errhtml = "<div id='errorbanner'>An unknown error has occurred</div>";
			} else if ($errparam == "err_usr_exi") {
				$errhtml = "<div id='errorbanner'>That username is already in use</div>";
			} else if ($errparam == "err_inv_usr") {
				$errhtml = "<div id='errorbanner'>Login attempt failed, please try again</div>";
			} else if ($errparam == "err_no_fam") {
				$errhtml = "<div id='errorbanner'>You must join a family before accessing that page</div>";
			} else if ($errparam == "err_no_acc") {
				$errhtml = "<div id='errorbanner'>Access to page was denied</div>";
			} else if ($errparam == "err_inv_code") {
				$errhtml = "<div id='errorbanner'>Invalid code, please try again</div>";
			} else if ($errparam == "err_inv_req") {
				$errhtml = "<div id='errorbanner'>An invalid request was made to the server</div>";
			} else if ($errparam == "err_not_ver") {
				$errhtml = "<div id='errorbanner'>You have not yet been verified as a parent</div>";
			} else if ($errparam == "err_inc") {
				$errhtml = "<div id='errorbanner'>That page is not available at this time</div>";
			} else {
				$errhtml = "<div id='errorbanner'>Error handling failed</div>";
			};
		};
	};

	// Initialize variables
	$code = "";
	$sectionAHtml = "";
	$familyActionHtml = "";
	$familyCard = "";
	$choreCard = "";
	$rewardCard = "";

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

	// Set innerHTML based on account type
	if ($_SESSION["type"] == "parent") {
		$familyCard = "<h3 class='card-title'>Manage Family</h3>
					   <p>Organize your family structure, add new members, edit permissions, and create groups.</p>";
		$choreCard = "<h3 class='card-title'>Assign Chores</h3>
					  <p>Create new chores, reassign existing chores, and set point values for individual tasks.</p>";
		$rewardCard = "<h3 class='card-title'>Manage Rewards</h3>
					   <p>Add or delete reward options, change point costs for existing rewards, browse sponsored rewards, and view redeemed rewards.</p>";
		if ($_SESSION["familyid"] != null) {
			$familyActionHtml = "onclick=\"redirect('family.php')\"";
			$sql = "SELECT code FROM family WHERE familyid = '{$_SESSION["familyid"]}';";
			$result = mysqli_query($conn, $sql);
			$rows = mysqli_num_rows($result);
			if ($rows > 0) {
				while ($row = mysqli_fetch_assoc($result)) {
					$code = $row["code"];
				};
			} else {
				header("Location: index.php?err=err_unk");
				exit;
			};
			$sectionAHtml = "<span style='color:#F20505; padding-top: 0;'>My Family Code: <b>{$code}</b></span>";
		} else {
			$familyActionHtml = "onclick=\"redirect('dashboard.php#section-a')\"";
			$sql = "SELECT lastname FROM user WHERE userid = '{$_SESSION["uid"]}';";
			$result = mysqli_query($conn, $sql);
			$rows = mysqli_num_rows($result);
			$familyName = "";
			if ($rows > 0) {
				while ($row = mysqli_fetch_assoc($result)) {
					$familyName = "The " . $row["lastname"] . " Family";
				};
			} else {
				header("Location: index.php?err=err_unk");
			};
			$sectionAHtml = "<form method='post' action='family.php'>
								<input type='hidden' name='create' value='family' />
								<input type='hidden' name='name' value='$familyName' />
								<input class='submit-input' type='submit' value='Create a Family' />
				 			 </form>
				 			 OR<br>
				 			 <form method='post' action='family.php'>
				 			 	<input id='code-input' type='text' placeholder='Enter Family Code' name='code' maxlength='5' /><br>
								<input class='submit-input' type='submit' value='Join Existing Family' />
				 			 </form>";
		};
	} else if ($_SESSION["type"] == "chorechamp") {
		if ($_SESSION["familyid"] != null) {
			$familyActionHtml = "onclick=\"redirect('family.php')\"";
			$familyCard = "<h3 class='card-title'>View Family</h3>
					       <p>View your family structure and groups.</p>";
			$choreCard = "<h3 class='card-title'>View Chores</h3>
					      <p>View your chores, the amount of points you will receive, and how much time you have to complete them.</p>";
			$rewardCard = "<h3 class='card-title'>View Rewards</h3>
					       <p>Redeem your Chore Champ points for rewards.</p>";
		} else {
			$sectionAHtml = "<form method='post' action='family.php'>
								<input id='code-input' type='text' placeholder='Enter Family Code' name='code' maxlength='5' /><br>
								<input class='submit-input' type='submit' value='Join' />
				 			 </form>";
			$familyActionHtml = "onclick=\"redirect('dashboard.php#section-a')\"";
			$familyCard = "<h3 class='card-title'>Join Your Family</h3>
					   	   <p>Join your family using the unique family code.</p>";
			$choreCard = "<h3 class='card-title'>View Chores</h3>
					      <p>You must join a family before viewing your chores.</p>";
			$rewardCard = "<h3 class='card-title'>View Rewards</h3>
					       <p>You must join a family before viewing your rewards.</p>";
		};
	} else {
		header("Location: index.php?err=err_unk");
	};

?>

<!DOCTYPE html>
<html>
<head>
	<title>Dashboard - Chore Champs</title>
	<link rel="stylesheet" type="text/css" href="styles/mobile/dashboard.css" />
	<link rel="shortcut icon" href="images/favicon.png" />
</head>
<body>
	<?php echo $errhtml; ?>
	<header id="showcase" class="grid">
		<div class="bg-image"></div>
		<div class="content-wrap">
			<h1>My Dashboard</h1>
			<p>Welcome back, <?php echo ($_SESSION['name']); ?></p>
			<a href="#section-b" class="btn" onclick="redirect('calendar.php')">View Calendar</a>
		</div>
	</header>
	<main id="main">
		<!-- Section A -->
		<section id="section-a" class="grid">
			<div class="content-wrap">
				<h2 class="content-title"><?php echo $sectionAHtml ?></h2>
				<div class="content-text"></div>
			</div>
		</section>
		<!-- Section B -->
		<section id="section-b" class="grid">
			<ul>
				<li <?php echo $familyActionHtml; ?>>
					<img src="images/family.jpeg" alt="" />
					<div class="card-content">
						<?php echo $familyCard; ?>
					</div>
				</li>
				<li onclick="redirect('chores.php')">
					<img src="images/kitchenchore.jpeg" alt="" />
					<div class="card-content">
						<?php echo $choreCard; ?>
					</div>
				</li>
				<li onclick="redirect('rewards.php')">
					<img src="images/reward.jpeg" alt="" />
					<div class="card-content">
						<?php echo $rewardCard; ?>
					</div>
				</li>
			</ul>
		</section>
		<!-- Section C -->
		<section id="section-c" class="grid">
			<div class="content-wrap">
				<h2 class="content-title"></h2>
				<div class="content-text">
					<p></p>
				</div>
			</div>
		</section>
		<!-- Section D -->
		<section id="section-d" class="grid">
			<div class="box">
				<h2 class="content-title">Support</h2>
				<p>Having trouble? <a href="mailto:support@chorechamps.net">Email a support agent for assistance.</a></p>
			</div>
			<div class="box">
				<h2 class="content-title">Sponsorships</h2>
				<p>Interested in partnering with Chore Champs? <a href="mailto:businessrelations@chorechamps.net">Email our business relations team.</a></p>
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