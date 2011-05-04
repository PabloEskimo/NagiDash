<?php
/**
 * This class provides error handling facilities
 *
 * @package Error
 * @author Paul Maddox <paul.maddox@gmail.com>
 * @copyright Paul Maddox 4 Jan 2010
 */
class Error extends Base {

	const ERROR_WARNING = 1;
	const ERROR_CRITICAL = 2;
	const ERROR_FATAL = 3;

	protected $chrMessage;
	protected $intLevel;
	protected $arrTrace;

	/*##########################################################################*//**
	 * Class Constructor
	 * @param $chrMessage = 'Unknown Error'
	 * @return true;
	 */
	function __construct( $chrMessage = 'Unknown Error', $intLevel) {

		@error_log($chrMessage, 0);

		$this->setMessage($chrMessage);
		$this->setLevel($intLevel);
		
		switch($intLevel){

			case self::ERROR_WARNING:
				Audit::message('Warning: ' . $this->getMessage());
			break;

			case self::ERROR_CRITICAL:
				Audit::message('Error: ' . $this->getMessage());
			break;

			case self::ERROR_FATAL:

				$objPage = new Page();
				$objPage->setPageTitle("fatal.<span>error</span>");
				$objPage->setPageSubtitle("A fatal error has occurred");

				if(is_object($chrMessage)){

					$arrTrace = end($chrMessage->getTrace());
					$chrError = $chrMessage->getMessage();

				} else {
					$arrTrace = end(debug_backtrace());
					$chrError = $chrMessage;
				}

				if(isset($arrTrace['class'])){
					$chrFunction = "{$arrTrace['class']}::{$arrTrace['function']}();";
				} else {
					$chrFunction = "{$arrTrace['function']}();";
				}

				Audit::message("Fatal: $chrError");

				$chrPattern = '/(Column not found|Base table or view not found)/';
				if(preg_match($chrPattern, $this->getMessage())){
					$chrError = "It looks like the database doesn't contain all of the tables/fields necessary for operation.<br />";
					$chrError .= "Please click <a href='/install'>HERE</a> to install the latest version of the database";
				}
				
				$chrBody = "

					<div class=\"box\">
						<div class=\"box_inner\">
							<div class=\"captioned_image\">
								<img alt=\"Error\" src=\"/images/error.png\" />
								<div></div>
							</div>
							<h2>$chrError</h2>
							<p>
								<i>The error occured in the following location:</i> <br />
								<b>File: </b>" . basename($arrTrace['file']) . "  (line {$arrTrace['line']})<br />
								<b>Function: </b>$chrFunction
							</p>
						</div>
					</div>
					<h3>Error Summary</h3>
					<p>
						Looks like something pretty serious has gone wrong and it hasn't been possible to recover<br />
						A site administrator has been automatically notified of the problem and will investigate soon<br />
					</p>
					";


				$objPage->setBody($chrBody);
				$objPage->draw(false);
				#display(debug_backtrace());


				exit;


			break;

		}

		return true;

	} # end method
	
	/**
	 * Creates a fatal error
	 * @param $chrMessage
	 * @return true
	 */
	public static function fatal( $chrMessage ) {
		return new Error($chrMessage, Error::ERROR_FATAL);
	} # end method
	
	/**
	 * Creates a critical error
	 * @param $chrMessage
	 * @return true
	 */
	public static function critical( $chrMessage ) {
		return new Error($chrMessage, Error::ERROR_CRITICAL);
	} # end method
	
	/**
	 * Creates a warning error
	 * @param $chrMessage
	 * @return true
	 */
	public static function warning( $chrMessage ) {
		return new Error($chrMessage, Error::ERROR_WARNING);
	} # end method
	
	
	/**
	 * Emails an error report
	 * @param $chrTo
	 * @return true
	 */
	public function email( $chrTo ) {

		switch($this->getLevel()){
			
			case self::ERROR_WARNING:
				$chrLevel = 'Warning';
			break;

			case self::ERROR_CRITICAL:
				$chrLevel = 'Critical';
			break;
			
			case self::ERROR_FATAL:
				$chrLevel = 'Fatal';
			break;
			
			default:
			
		}
		
		$chrMessage = $this->getMessage();
		
		if(is_object($chrMessage)){
			$arrTrace = end($chrMessage->getTrace());
			$chrError = $chrMessage->getMessage();

		} else {
			$arrTrace = end(debug_backtrace());
			$chrError = $chrMessage;
		}

		if(isset($arrTrace['class'])){
			$chrFunction = "{$arrTrace['class']}::{$arrTrace['function']}();";
		} else {
			$chrFunction = "{$arrTrace['function']}();";
		}

		$chrBody = 
"Hey there!

A " . strtolower($chrLevel) . " error has occurred.

Date: " . date('H:i:s d/m/Y') . "
User: " . Session::getName(). "
Error: $chrError
File: " . basename($arrTrace['file']) . " (line {$arrTrace['line']})
Function: $chrFunction

Thanks!

ClusterManager
";
		
		$objEmail = new Email();
		$objEmail->addTo($chrTo);
		$objEmail->setSubject("{$chrLevel}: $chrError");
		$objEmail->setBody($chrBody);
		$objEmail->send();
			
		return true;
			
	} # end method
	
	/**
	 * This function overrides the standard exception handler in PHP
	 * @param $objException
	 * @return true
	 */
	public static function exception_handler( Exception $objException ) {
	
		if ( 0 == error_reporting () ) {
	        // Error reporting is currently turned off or suppressed with @
	        return;
	    }
		
		Error::fatal($objException);
		return true;

	} # end method
	
	/**
	 * This function overrides the standard error handler in PHP
	 * @param $intError, $chrError 
	 * @return true
	 */
	public static function error_handler( $intError, $chrError) {
		
		if ( 0 == error_reporting () ) {
	        // Error reporting is currently turned off or suppressed with @
	        return;
	    }		
		
		switch ($intError) {
			case E_STRICT;
			case	 E_NOTICE;
				# Ignore strict errors
			break;

    	    default:
        		Error::fatal($chrError);
		}
		
	}
		
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
}
