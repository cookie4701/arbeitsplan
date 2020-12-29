<?php

require '../moderator.php';

if ( isset($_POST["userid"] ) {

	if ( isModerator($_POST["userid"] ) {
		header('200 OK');
	} else {
		header('404 not found');
	}	
} else {
	header('404 not found');
}
