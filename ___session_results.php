<?php

session_start();
if (isset($_GET['url'])) {
	if (isset($_SESSION["resultViewed"])) {
		$_SESSION["resultViewed"] = $_SESSION["resultViewed"] + 1;    	
	}
	else {
		$_SESSION["resultViewed"] = 1;	
	}
}

?>