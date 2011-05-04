<?php

include('../lib/common.php');

$arrClassFiles = glob(APP_HOME . 'lib/*.php');

# This script will automatically detect all classes that require database tables
# and ensure that those tables are created and up to date in terms of table definition

foreach($arrClassFiles as $chrClassFile) {
	
	Progress::update("Analyzing " . basename($chrClassFile) . "...");
	$chrContents = file_get_contents($chrClassFile);
	
	$chrPattern = "/class (.*) extends BaseData/";
	preg_match_all($chrPattern, $chrContents, $arrResults);
	
	if(is_array($arrResults[1])){
		
		foreach($arrResults[1] as $chrDataClass){
			Progress::update("Found data class: $chrDataClass");
			$arrDataClasses[] = $chrDataClass;
		}
		
	}
	
}


foreach($arrDataClasses as $chrClass){
	
	Progress::update("Installing $chrClass to DB");
	$objClass = new $chrClass();
	$objClass->install();
	
}

Progress::heading("Installation Complete!");
Progress::update("Your database has been installed successfully");
