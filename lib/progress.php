<?php
/**
 * This class displays HTTP progress pages using Page class
 *
 * @package Audit
 * @author Paul Maddox <paul.maddox@gmail.com>
 * @copyright Paul Maddox 4 Jan 2010
 */
final class Progress extends Base {


	/** @var object */
	public static $objInstance;

	/** @var string */
	protected $chrHeading;
	protected $chrStatus;

	/*##########################################################################*//**
	 * Gets the static progress instance
	 * @param 
	 * @return $objInstance;
	 */
	public static function getInstance(  ) {

		if(!isset(self::$objInstance)){
			self::$objInstance = new Progress();
		}

		return self::$objInstance;
	
	} # end method
	
	
	/*##########################################################################*//**
	 * Updates the status text
	 * @param $chrMessage
	 * @return true
	 */
	public static function update($chrMessage){

		$objInstance = self::getInstance();
		$objInstance->setStatus($chrMessage);
		
		Page::javascript("

			objStatus = document.getElementById('status_text');
			if(!objStatus){
				objStatus = parent.document.getElementById('status_text');
			}

			objStatus.innerHTML = '$chrMessage';

		");
		return true;

	} # end function

	/*##########################################################################*//**
	 * Sets the progress heading
	 * @param $chrHeading
	 * @return true
	 */
	public static function heading($chrHeading){

		$objInstance = self::getInstance();
		$objInstance->setHeading($chrHeading);
		
		Page::javascript("

			objHeading = document.getElementById('status_heading');
			if(!objHeading){
				objHeading = parent.document.getElementById('status_heading');
			}

			objHeading.innerHTML = '$chrHeading';

		");

		return true;

	} # end function

	/*##########################################################################*//**
	 * Starts a countdown timer
	 * @param $intSeconds
	 * @return true
	 */
	public static function countdown($intSeconds){

		if($intSeconds === false){
			Page::javascript("
				
				if(objCountdown){
					objCountdown.stop();
					objCountdown = false;
				}
				
				document.getElementById('countdown').style.display = 'none';

				");
			
		} else {
			Page::javascript("
				
				if(objCountdown){
					objCountdown.stop();
					objCountdown = false;
				}
				
				//var objCountdown = new cdtime(\"status_timer\", \"$intSeconds\");
				//objCountdown.displaycountdown(\"minutes\", formatresults);
				
				var objCountdown = new Countdown('status_timer', $intSeconds);
				objCountdown.start();
				
				document.getElementById('countdown').style.display = 'block';

				");
			
		}

		return true;

	} # end functionn


	/*##########################################################################*//**
	 * Class constructor
	 * @param
	 * @return true
	 */
	function __construct(){

		ignore_user_abort(TRUE);

		if(strlen($this->getHeading()) < 1){
			$this->setHeading('Processing');
		}
		
		if(strlen($this->getStatus()) < 1){
			$this->getStatus('Preparing operations...');
		}
		
		
		$chrHTML = "
		<br />
		<br />
		<table border=\"0\" width=\"100%\" cellspacing=\"15\">
			<tr>
				<td align=\"center\" valign=\"middle\">
					<img src=\"/images/busy.gif\"><br />
					<br />
					<h2><span id=\"status_heading\">{$this->getHeading()}</span></h2>
					<span id=\"status_text\">{$this->getStatus()}</span><br /><br />
					<!--<div id=\"countdown\"><i><font color=grey>Timout: <span id=\"status_timer\">unknown</span></font></i></div>-->
					<br />
				</td>
			</tr>
		</table>

		<br />
		";


		$objPage = new Page();
		$objPage->setBody($chrHTML);
		$objPage->draw(false);

		#self::update("Performing preperations");

		return true;

	} # end function

	/*##########################################################################*//**
	 * This is run when the operation is finished, even if the user has closed the page this function will email them to notify completion
	 * @param
	 * @return true
	 */
	function __destruct(){

	

	} # end function



} # end class


?>
