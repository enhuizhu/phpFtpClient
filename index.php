<?php
	error_reporting(E_ALL | E_STRICT);
	ini_set('display_errors', 1);
	// phpinfo();
	
	// die();
	
	require "vendor/autoload.php";
	
	date_default_timezone_set("Europe/London");
	
	$filePath = $_GET["filePath"];

	if (!$filePath) {
		throw new Exception("you have to provide files path", 1);
	}
	
	$ftp = new FTP($filePath);

	$result = $ftp->listFiles("/");

	echo "result are:";

	var_dump($result);
	// print_r($result);