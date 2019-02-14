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
			} else {
				$errhtml = "<div id='errorbanner'>Error handling failed</div>";
			};
		};
	};

	// Check if session exists
	if (isset($_SESSION["id"])) {
		// echo("sessionid: " . $_SESSION['id']);
		$sql = "SELECT * FROM session WHERE sessionid = '" . $_SESSION["id"] . "';";
		$result = mysqli_query($conn, $sql);
		$rows = mysqli_num_rows($result);
		// Redirect to dashboard if session exists
		if ($rows > 0) {
			header("Location: dashboard.php");
			exit;
		};
	};
?>

<!DOCTYPE html>
<html>
<head>
	<title id="title">Login - Chore Champs</title>
	<link rel="stylesheet" type="text/css" href="styles/index.css" />
	<link rel="shortcut icon" href="images/favicon.png" />
</head>
<body id="body">
	<div id="wrapper">
		<?php echo($errhtml); ?>
		<main>
			<div class="formcontainer">
				<form id="loginform" class="form" method="post" action="confirmlogin.php">
					<header>
						<img src="images/logo.png" />
						<div id="headertext"><h2>Login</h2></div>
					</header>
					<label for="username">Username</label>
					<input type="text" class="usernameinput" placeholder="Enter your username..." id="username" name="username" required /><br>
					<label for="password">Password</label>
					<input type="password" class="passwordinput" placeholder="Enter your password..." id="password" name="password" required /><br>
					<div class="options">
						<input type="submit" class="submitinput" value="Login" />
						<a class="toggleformoption" onclick="toggleForm('signupform')">Create An Account</a>
					</div>
				</form>

				<form id="signupform" class="form" method="post" action="confirmsignup.php" style="display: none;">
					<header>
						<img src="images/logo.png" />
						<div id="headertext"><h2>Create An Account</h2></div>
					</header>
					<label for="type">Account Type</label>
				    <select id="type" name="type" required>
				      <option value="chorechamp">Chore Champ</option>
				      <option value="parent">Parent</option>
				    </select>
				    <label for="signupusername">Username</label>
				    <input type="text" class="signupchild" placeholder="Choose a unique username..." id="signupusername" name="username" required />
				    <label for="firstname">First Name</label>
					<input type="text" class="signupchildleft" placeholder="Enter your first name..." id="firstname" name="firstname" required />
					<label for="lastname">Last Name</label>
					<input type="text" class="signupchildright" placeholder="Enter your last name..." id="lastname" name="lastname" required />
					<label for="signuppassword">Password</label>
					<input type="password" pattern="^\S{6,}$" onchange="this.setCustomValidity(this.validity.patternMismatch ? 'Must have at least 6 characters' : ''); if(this.checkValidity()) form.confirmpassword.pattern = this.value;" placeholder="Create your password..." id="signuppassword" name="password" required />
					<label for="confirmpassword">Confirm Password</label>
					<input type="password" pattern="^\S{6,}$" onchange="this.setCustomValidity(this.validity.patternMismatch ? 'Please enter the same Password as above' : '');" placeholder="Re-enter your password..." id="confirmpassword" name="confirmpassword" required />
					<div class="options">
						<input type="submit" class="submitinput" value="Sign Up" />
						<a class="toggleformoption" onclick="toggleForm('loginform')">Already Have an Account?</a>
					</div>
				</form>
			</div>
		</main>
	</div>
	<script src="scripts/main.js"></script>
</body>
</html>