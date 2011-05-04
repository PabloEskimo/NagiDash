<?php
/**
 * This class extends the Calendar class to allow Exchange calendars to be accessed
 *
 * @package ExhcangeCalendar
 * @author Paul Maddox <paul.maddox@gmail.com>
 * @copyright Paul Maddox 4 Jan 2010
 */
class ExchangeCalendar extends Calendar {
	
	/*##########################################################################*//**
	 * Gets an array of appointments for a day
	 * @param $intDay, $intMonth, $intYear
	 * @return $arrAppointments;
	 */
	function getAppointments( $intDay, $intMonth, $intYear ) {

		$objHTTP = new HTTP();
		$objHTTP->setURL($this->getURL() . "/?Cmd=contents&View=Daily&m=$intMonth&d=$intDay&y=$intYear");
		$objHTTP->setUsername($this->getUsername());
		$objHTTP->setPassword($this->getPassword());
		$objHTTP->run();
		
		#display($objHTTP);
		
		if($objHTTP->getResultCode() != 200){
			die("Failed to load Exchange calendar");
		}
		
		# Now parse out the appointments
		
		$chrPattern = '#([\w]+)-[\d]+\.EML#Um';
		preg_match_all($chrPattern, $objHTTP->getResult(), $arrResults);
		
		if(strlen($arrResults[1][0]) > 0){
			return $arrResults[1];
		}
		
		$chrPattern = '#([\w]+)\.EML#Um';
		preg_match_all($chrPattern, $objHTTP->getResult(), $arrResults);
		
		if(strlen($arrResults[1][0]) > 0){
			return $arrResults[1];
		}

		return array();
		
	} # end method
	

}




















































































