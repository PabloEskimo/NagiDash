<?php

include('../lib/common.php');

switch($_REQUEST['action']){
	
	case 'wipe':
		
		Audit::message("User logged out");
		Session::wipe(Session::getUsername());
		Page::location('/');
		
	break;

	case 'process':
		
		# First see if a user exists
		$arrUsers = User::find_by_username($_REQUEST['chrUsername']);
		
		if(sizeof($arrUsers) < 1){
			# Couldn't find a matching username, but maybe they meant email address?
			
			$arrUsers = User::find_by_email($_REQUEST['chrUsername']);
			
			if(sizeof($arrUsers) < 1){
				Audit::message("Attempted login by unknown user: {$_REQUEST['chrUsername']}");
				showLogin("The username or password entered is invalid");
				exit;
			}
		
		}
			
		$objUser = new User($arrUsers[0]);
		
		if($objUser->authenticate($_REQUEST['chrPassword'])){

			Session::create($objUser->getUsername());
			
	        $objProgress = new Progress();
			Progress::heading("Processing Log in");
			Progress::update("Verifying authentication credentials...");
			sleep(1);
			Progress::update("Creating session...");
			sleep(1);
			
			Audit::message("User logged in");
			
			if(strlen($_REQUEST['f']) > 0){
				Page::location(urldecode($_REQUEST['f']));
			} else {
				Page::location('/');
			}
			
		} else {
			Audit::message("Invalid password for user: {$_REQUEST['chrUsername']}");
			showLogin("The username or password entered is invalid");
			exit;
		}
		
		
		exit;
	break;
	
	default:
		showLogin();	
	
}


/*##########################################################################*//**
 * Displays the login page
 * @param $chrMessage = ''
 * @return true
 */
function showLogin( $chrMessage = '' ) {

	if(strlen($chrMessage) > 0){
		$chrMessage = "<p class=\"error\">$chrMessage</p>\n";
	} else {
		$chrMessage = "<p class=\"info\">Access to the page you are trying to access requires a valid login</p>\n";
	}
		
	$chrHTML = "
	<br />
	$chrMessage
	<div class=\"form\">
		<form id=\"frmLogin\" action=\"/login/process\" method=\"post\">
			<div class=\"fm-req\">
				<label for=\"chrUsername\">Username:</label>
				<input type=\"text\" id=\"chrUsername\" name=\"chrUsername\" value=\"{$_REQUEST['chrUsername']}\" />
			</div>
			<div class=\"fm-req\">
				<label for=\"chrPassword\">Password:</label>
				<input type=\"password\" id=\"chrPassword\" name=\"chrPassword\" value=\"\" />
			</div>
			<div id=\"fm-submit\" class=\"fm-req\"> 
	      		<input name=\"Submit\" value=\"Submit\" type=\"submit\" /> 
    		</div> 
    		<input type=\"hidden\" name=\"f\" value=\"{$_REQUEST['f']}\">
    	</form>
	</div>
	<!--
	<h3>forgotten password?</h3>
	<p>
		Having trouble logging in? You can request a new password for your account by clicking <a href=\"/login/forgotten\">here.</a>
	</p>
	-->
    ";
	
	
	$objPage = new Page();
	$objPage->setPageTitle("login.<span>required</span>");
	#$objPage->setPageSubtitle("Access to the page you are trying to access requires a valid login");
	$objPage->setBody($chrHTML);
	$objPage->draw();
	
	
	return true;

}
