<?php

header( "Content-Type: text/html; charset=UTF-8");
header( "Expires: Mon, 20 Dec 1998 01:00:00 GMT" );
header( "Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT" );
header( "Cache-Control: no-cache, must-revalidate" );
header( "Pragma: no-cache" );

define('APP_HOME', preg_replace('|/public/.*\.php|', '/', $_SERVER['SCRIPT_FILENAME']));

include_once('random_functions.php');

# Configure the class autoloader
spl_autoload_register('autoload');

# Override the default exception & error handlers
set_exception_handler(array('Error', 'exception_handler'));
set_error_handler(array('Error', 'error_handler'));

# Sort out the timezone to stop PHP warnings
if(strlen(ini_get('date.timezone')) < 1){
	# No timezone set in PHP's configuration file
	ini_set('date.timezone', 'Europe/London');
}



# Install the job scheduler and related jobs
if(DB::tableExists('Job')){
	Job::install_bootloader();
	# Job::create('MyClass::my_function_i_want_running();', '00 * * * *');
}

# See if authentication is required
if(Config::get('authentication:required')){
	Session::validate();
}

# Class with global destructor!
$objDestructor = new Destructor();
