<?php
	phpinfo();
	
	die();
	
	require "vendor/autoload.php";
	
	date_default_timezone_set("Europe/London");
	
	$filePath = $_GET["filePath"];

	if (!$filePath) {
		throw new Exception("you have to provide files path", 1);
	}
	
	$ftp = new FTP($filePath);