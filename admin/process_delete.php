<?php

include_once("../includes/db_connect.php");

// Erase without confirmation user
if ( isset($_POST["id"]) ) {
	$id = $_POST["id"];
	$error = 0;

	if ( $stmt = $db->prepare("DELETE FROM aplan_arbeitstage WHERE user_id = ?") ) {
		$stmt->bind_param("i", $id);
		$stmt->execute();
		$stmt->close();
	} else {
		echo "<p>An error occured... " . $db->error . "</p>";
		$error = 1;
	}

	if ( $stmt = $db->prepare("DELETE FROM aplan_daydescriptions WHERE user_id = ?") ) {
		$stmt->bind_param("i", $id);
		$stmt->execute();
		$stmt->close();
	} else {
		echo "<p>An error occured... " . $db->error . "</p>";
		$error = 2;
	}



	if ( $stmt = $db->prepare("DELETE FROM aplan_kilometers WHERE user_id = ?") ) {
		$stmt->bind_param("i", $id);
		$stmt->execute();
		$stmt->close();
	} else {
		echo "<p>An error occured... " . $db->error . "</p>";
		$error = 3;
	}



	if ( $stmt = $db->prepare("DELETE FROM aplan_timefromto WHERE user_id = ?") ) {
		$stmt->bind_param("i", $id);
		$stmt->execute();
		$stmt->close();
	} else {
		echo "<p>An error occured... " . $db->error . "</p>";
		$error = 4;
	}



	if ( $stmt = $db->prepare("DELETE FROM aplan_users WHERE id = ?") ) {
		$stmt->bind_param("i", $id);
		$stmt->execute();
		$stmt->close();
	} else {
		echo "<p>An error occured... " . $db->error . "</p>";
		$error = 5;
	}



	if ( $stmt = $db->prepare("DELETE FROM aplan_daydescriptions WHERE user_id = ?") ) {
		$stmt->bind_param("i", $id);
		$stmt->execute();
		$stmt->close();
	} else {
		echo "<p>An error occured... " . $db->error . "</p>";
		$error = 6;
	}



	if ( $stmt = $db->prepare("DELETE FROM aplan_workfields WHERE user = ?") ) {
		$stmt->bind_param("i", $id);
		$stmt->execute();
		$stmt->close();
	} else {
		echo "<p>An error occured... " . $db->error . "</p>";
		$error = 7;
	}
	
	if ( $stmt = $db->prepare("DELETE FROM aplan_workhours WHERE user = ?") ) {
		$stmt->bind_param("i", $id);
		$stmt->execute();
		$stmt->close();
	} else {
		echo "<p>An error occured... " . $db->error . "</p>";
		$error = 8;
	}
	
	if ( $stmt = $db->prepare("DELETE FROM aplan_userwatchlist WHERE iduserwatch = ?") ) {
		$stmt->bind_param("i", $id);
		$stmt->execute();
		$stmt->close();
	} else {
		echo "<p>An error occured... " . $db->error . "</p>";
		$error = 9;
	}

	if ($error == 0 ) {
		echo "<p>All queries have been executed successfully!</p>";
		//header("Location index.php");
	}
}

echo "<a href=\"users_admin.php\"> Benutzerliste </a>";


