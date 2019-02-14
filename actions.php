<?php

	// TODO: Change 'create' queries to POST, not GET

	include_once "data/patterns/mysql_connect.php";
	session_start();

	// Initialize variables
	$sql = "";
	$isVerifiedParent = false;
	$groupid = "";
	$taskid = "";

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

	// Get request variables
	$url = $_SERVER["REQUEST_URI"];
	$parts = parse_url($url);
	if (isset($parts["query"])) {

		// Check for errors in request
		parse_str($parts["query"], $query);
		if (!isset($query["location"])) {
			header("Location: dashboard.php?err=err_inv_req");
			exit;
		}
		if (!isset($query["action"])) {
			header("Location: dashboard.php?err=err_inv_req");
			exit;
		};
		if (!isset($query["resource"])) {
			header("Location: dashboard.php?err=err_inv_req");
			exit;
		};
		if (!isset($query["id"]) && $query["action"] != "create") {
			header("Location: dashboard.php?err=err_inv_req");
			exit;
		};
		if (!isset($query["idtype"]) && $query["action"] != "create") {
			header("Location: dashboard.php?err=err_inv_req");
			exit;
		}

		// Check requesting user type
		$sql = "SELECT role FROM familymap WHERE userid={$_SESSION["uid"]};";
		$result = mysqli_query($conn, $sql);
		$rows = mysqli_num_rows($result);
		if ($rows > 0) {
			while ($row = mysqli_fetch_assoc($result)) {
				if ($row["role"] == "Parent") {
					$isVerifiedParent = true;
				};
			};
		} else {
			header("Location: dashboard.php?err=err_unk");
			exit;
		};

		// Verify that requesting user has necessary permissions
		if ($query["idtype"] == "familyid") {
			if ($_SESSION["familyid"] != $query["id"] || $_SESSION["type"] != "parent") {
				header("Location: dashboard.php?err=err_no_acc");
				exit;
			};
		} else if ($query["idtype"] == "userid") {
			$familyArray = array();
			$sql = "SELECT userid FROM familymap WHERE familyid={$_SESSION["familyid"]};";
			$result = mysqli_query($conn, $sql);
			$rows = mysqli_num_rows($result);
			if ($rows > 0) {
				while ($row = mysqli_fetch_assoc($result)) {
					$memberId = $row["userid"];
					array_push($familyArray, $memberId);
				};
			} else {
				header("Location: dashboard.php?err=err_inv_req");
				exit;
			};
			if (!$isVerifiedParent) {
				header("Location: dashboard.php?err=err_no_acc");
				exit;
			};
			if ($_SESSION["type"] == "parent") {
				if ($_SESSION["uid"] != $query["id"]) {
					$familyUserMatch = false;
					foreach ($familyArray AS $memberId) {
						if ($memberId == $query["id"]) {
							$familyUserMatch = true;
						};
					};
					if (!$familyUserMatch) {
						header("Location: dashboard.php?err=err_inv_req");
						exit;
					};
				};
			} else {
				header("Location: dashboard.php?err=err_inv_req");
				exit;
			};
		} else if ($query["idtype"] == "groupid") {
			// Group creation credential validations are currently handled before this page
		} else if ($query["idtype"] == "taskid") {
			// Handle task verification here
		} else {
			header("Location: dashboard.php?err=err_inv_req");
			exit;
		};

		// Manage all CRUD requests
		if ($query["action"] == "create") {

			// Create
			if (!isset($query["params"])) {
				header("Location: dashboard.php?err=err_inv_req");
				exit;
			};
			if (!isset($query["values"])) {
				header("Location: dashboard.php?err=err_inv_req");
				exit;
			};
			$sql = "INSERT INTO {$query["resource"]} ({$query["params"]}) VALUES ({$query["values"]});";

		} else if ($query["action"] == "read") {

			// Read
			if (!isset($query["fields"])) {
				header("Location: dashboard.php?err=err_inv_req");
				exit;
			};
			$sql = "SELECT {$query["fields"]} FROM {$query["resource"]} WHERE id={$query["id"]};";

		} else if ($query["action"] == "update") {

			// Update
			if (!isset($query["targetparam"])) {
				header("Location: dashboard.php?err=err_inv_req");
				exit;
			};
			if (!isset($query["targetvalue"])) {
				header("Location: dashboard.php?err=err_inv_req");
				exit;
			};
			$sql = "UPDATE {$query["resource"]} SET {$query["targetparam"]}='{$query["targetvalue"]}' WHERE {$query["idtype"]}={$query["id"]};";

		} else if ($query["action"] == "delete") {

			// Delete

			// Remove all familymap entries containing the family which is being deleted
			if ($query["resource"] == "family") {
				$_SESSION["familyid"] = null;
				$sql = "DELETE FROM familymap WHERE familyid={$query["id"]};";
				if (!mysqli_query($conn, $sql)) {
					header("Location: dashboard.php?err=err=err_unk");
					exit;
				};
			};
			// Remove all usergroupmap entries containing the usergroup which is being deleted
			if ($query["resource"] == "usergroup") {
				$sql = "DELETE FROM usergroupmap WHERE groupid={$query["id"]};";
				if (!mysqli_query($conn, $sql)) {
					header("Location: {$query["location"]}?err=err_unk");
					exit;
				};
			};
			// Remove all taskmap entries containing the task which is being deleted
			if ($query["resource"] == "task") {
				$sql = "DELETE FROM taskmap WHERE taskid={$query["id"]};";
				if (!mysqli_query($conn, $sql)) {
					header("Location: {$query["location"]}?err=err_unk");
					exit;
				};
			};
			// User cannot remove themselves from family at this time
			if ($query["resource"] == "familymap" && $query["id"] == $_SESSION["uid"]) {
				header("Location: dashboard.php?err=err_inv_req");
				exit;
			};
			if ($query["resource"] == "familymap") {
				// Remove user from all groups which they are currently associated with
				$sql = "DELETE FROM usergroupmap WHERE userid={$query["id"]};";
				if (!mysqli_query($conn, $sql)) {
					// If deletion of user-group mapping failed, redirect
					header("Location: {$query["location"]}?err=err_unk");
					exit;
				};
				// Delete any empty groups which result from removing a user from the family
				$sql = "SELECT groupid FROM usergroup WHERE familyid={$_SESSION["familyid"]};";
				$result = mysqli_query($conn, $sql);
				$rows = mysqli_num_rows($result);
				if ($rows > 0) {
					// At least one group exists under requesting user's family
					while ($groupRow = mysqli_fetch_assoc($result)) {
						// Get all groupid entries from usergroupmap table
						$sql = "SELECT groupid FROM usergroupmap WHERE groupid={$groupRow["groupid"]};";
						$mapResult = mysqli_query($conn, $sql);
						$rows = mysqli_num_rows($mapResult);
						if ($rows > 0) {
							// At least one entry was found in the usergroupmap, good to continue
						} else {
							// No usergroupmap entries found despite groups existing, delete the usergroup
							$sql = "DELETE FROM usergroup WHERE groupid={$groupRow["groupid"]};";
							if (!mysqli_query($conn, $sql)) {
								header("Location: {$query["location"]}?err=err_unk");
								exit;
							};
						};
					};
				};
			};
			$sql = "DELETE FROM {$query["resource"]} WHERE {$query["idtype"]}={$query["id"]};";

		} else {

			// If non-CRUD actions/commands are added in the future, handle them here
			header("Location: dashboard.php?err=err_inv_req");
			exit;

		};

		// Execute action
		if ($query["action"] == "read") {
			// Handles read requests
			$result = mysqli_query($conn, $sql);
			$rows = mysqli_num_rows($result);
			if ($rows > 0) {
				while ($row = mysqli_fetch_assoc($result)) {
					// Insert functionality for read here
					header("Location: {$query["location"]}");
					exit;
				};
			} else {
				header("Location: {$query["location"]}");
				exit;
			};
		} else {
			// Handles all requests aside from reads
			if (mysqli_query($conn, $sql)) {
				header("Location: {$query["location"]}");
				exit;
			} else {
				header("Location: {$query["location"]}?err=err_inv_cmd");
				exit;
			};
		};

	} else {

		// Generic request parameters
		$location = mysqli_real_escape_string($conn, $_POST["location"]);
		$action = mysqli_real_escape_string($conn, $_POST["action"]);
		$resource = mysqli_real_escape_string($conn, $_POST["resource"]);

		// Currently assuming that all post requests are creates
		if ($location != null && $action != null && $resource != null) {

			// Create group request
			if ($resource == "usergroup") {
				// Parameters specific to usergroup creates
				$name = mysqli_real_escape_string($conn, $_POST["name"]);
				$members = $_POST["members"];
				if ($name == null || $members == null) {
					header("Location: {$location}?err=err_inv_req");
					exit;
				};
				// Ensure that no groups with the same name under the same family currently exist
				$sql = "SELECT * FROM usergroup WHERE familyid={$_SESSION["familyid"]} AND name='{$name}';";
				$result = mysqli_query($conn, $sql);
				$rows = mysqli_num_rows($result);
				if ($rows != 0) {
					header("Location: {$location}?err=err_grp_exi");
					exit;
				};
				// Insert new group into usergroup table
				$sql = "INSERT INTO usergroup (familyid, name) VALUES ('{$_SESSION["familyid"]}', '{$name}');";
				if (mysqli_query($conn, $sql)) {
					// Get id of created group
					$sql = "SELECT groupid FROM usergroup WHERE familyid='{$_SESSION["familyid"]}' AND name='{$name}';";
					$result = mysqli_query($conn, $sql);
					$rows = mysqli_num_rows($result);
					if ($rows > 0) {
						// New usergroup row successfully found
						while ($row = mysqli_fetch_assoc($result)) {
							$groupid = $row["groupid"];
						};
					} else {
						// Could not locate the new usergroup row
						header("Location: {$location}?err=err_unk");
						exit;
					};
					// Map selected dependents to new group
					foreach ($members AS $userid) {
						$sql = "INSERT INTO usergroupmap (groupid, userid) VALUES ('{$groupid}', '{$userid}');";
						if (!mysqli_query($conn, $sql)) {
							header("Location: {$location}?err=err_inv_req");
							exit;
						};
					};
					// Group creation and mapping successful, redirect to target location
					header("Location: {$location}");
					exit;
				} else {
					// Failed to create new group
					header("Location: {$location}?err=err_inv_req");
					exit;
				};
			// Create task request
			} else if ($resource == "task") {
				// Parameters specific to task creates
				$title = mysqli_real_escape_string($conn, $_POST["title"]);
				$rewardPoints = mysqli_real_escape_string($conn, $_POST["rewardpoints"]);
				$deadline = mysqli_real_escape_string($conn, $_POST["deadline"]);
				$description = mysqli_real_escape_string($conn, $_POST["description"]);
				$dependentAssignees = null;
				$groupAssignees = null;
				if (isset($_POST["dependentassignees"])) {
					$dependentAssignees = $_POST["dependentassignees"];
				};
				if (isset($_POST["groupassignees"])) {
					$groupAssignees = $_POST["groupassignees"];
				};
				if (sizeOf($dependentAssignees) == 0 && sizeOf($groupAssignees) == 0) {
					// No assignees selected, return with error
					header("Location: {$location}?err=err_sel_dep");
					exit;
				};
				// Convert deadline parameter to mysqli datetime data type
				$deadline = substr_replace($deadline, " ", 10, 1);
				$deadline .= ":00";
				// Ensure that no tasks with the same title under the same family currently exist
				$sql = "SELECT * FROM task WHERE familyid='{$_SESSION["familyid"]}' AND title='{$title}';";
				$result = mysqli_query($conn, $sql);
				$rows = mysqli_num_rows($result);
				if ($rows != 0) {
					// A task with the same title already exists
					header("Location: {$location}?err=err_tsk_exi");
					exit;
				};
				// Insert entry into task table
				$sql = "INSERT INTO task (familyid, title, description, rewardpoints, deadline) VALUES ('{$_SESSION["familyid"]}', '$title', '$description', '$rewardPoints', '$deadline');";
				if (mysqli_query($conn, $sql)) {
					// Get the taskid of created task entry
					$sql = "SELECT taskid FROM task WHERE familyid='{$_SESSION["familyid"]}' AND title='$title';";
					$result = mysqli_query($conn, $sql);
					$rows = mysqli_num_rows($result);
					if ($rows > 0) {
						while ($row = mysqli_fetch_assoc($result)) {
							$taskid = $row["taskid"];
						};
					} else {
						// Failure to find inserted document
						header("Location: {$location}?err=err_unk");
						exit;
					};
					// Map selected dependents to the created task
					foreach ($dependentAssignees AS $userid) {
						$sql = "INSERT INTO taskmap (taskid, userid) VALUES ('$taskid', '$userid');";
						if (!mysqli_query($conn, $sql)) {
							// Failed to insert taskmap entry
							header("Location: {$location}?err=err_unk");
							exit;
						};
					};
					// Map selected groups to the created task
					foreach ($groupAssignees AS $groupid) {
						$sql = "INSERT INTO taskmap (taskid, groupid) VALUES ('$taskid', '$groupid');";
						if (!mysqli_query($conn, $sql)) {
							// Failed to insert taskmap entry
							header("Location: {$location}?err=err_unk");
							exit;
						};
					};
					header("Location: {$location}");
					exit;
				} else {
					// Create task failed
					header("Location: {$location}?err=err_unk");
					exit;
				};
			} else {
				// Invalid resource for post request
				header("Location: {$location}?err=err_inv_req");
				exit;
			};

		} else {
			// Request is missing post data
			header("Location: dashboard.php?err=err_inv_req");
			exit;
		};

	};

?>