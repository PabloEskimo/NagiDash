<?php
/**
 * This class provides an interface to Google Calendars
 *
 * @package GoogleCalendar
 * @author Paul Maddox <paul.maddox@gmail.com>
 * @copyright Paul Maddox 15 Jul 2010
 */
class GoogleCalendar extends Calendar {

	protected $chrURL;
	
	
	/**
	 * Class Constructor
	 * @param $chrURL
	 * @return true
	 */
	public function __construct( $chrURL ) {
		
		$this->setURL($chrURL);
		
		# Commented out - too expensive time-wise
		#if(!$this->validate()){
		#	Error::fatal("Failed to load google calendar");	
		#} 
		
		return true;
			
	} # end method
	
	
	/**
	 * Checks to see if a google calendar URL is valid and available
	 * @param 
	 * @return $blnValid
	 */
	public function validate(  ) {

		$arrHeaders = HTTP::headers($this->getURL());
		
		if(is_array($arrHeaders) && $arrHeaders[0] == 'HTTP/1.0 200 OK'){
			return true;
		} else {
			return false;
		}
		
	} # end method
	
	/**
	 * Gets an array of appointment titles for a given day
	 * @param $intDay, $intMonth, $intYear
	 * @return $arrAppointments
	 */
	public function getAppointments($intStart, $intFinish = -1) {

		# Bit of date validation...
		$chrStart = date('Y-m-d', $intStart);
		
		if($intFinish == -1){
			$chrFinish = $chrStart;
		} else {
			$chrFinish = date('Y-m-d', $intFinish);
		}
		
		$chrURL = "http://www.google.com/calendar/feeds/" . $this->getURL() . "/public/full?max-results=100&singleevents=true&orderby=starttime&start-min={$chrStart}T00:00:00&start-max={$chrFinish}T23:59:59&alt=json";
		
		$chrJSON = HTTP::get($chrURL);
		$arrJSON = json_decode($chrJSON, true);

		$arrAppointments = array();
		
		foreach((Array) $arrJSON['feed']['entry'] as $intCount => $arrEvent){

			if(stristr($arrEvent['gd$eventStatus']['value'], 'confirmed') === false){
				# This event is not confirmed (could be cancelled etc).
				# Skip it!
				continue;
			}
			
			if(isset($arrEvent['gd$recurrence'])){
				# This event is just a placeholder for a recurring event
				#continue;
			}
			
			$arrAppointments[$arrEvent['gd$when'][sizeof($arrEvent['gd$when']) - 1]['startTime']] = $arrEvent['title']['$t'];			
		}
		
		# ORDER BY DATE DESC
		ksort($arrAppointments);
		
		return $arrAppointments;
			
	} # end method
	
	
	
	
}
