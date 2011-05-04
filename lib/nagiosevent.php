<?php
/**
 * This class provides events for nagios
 *
 * @package NagiosEvent
 * @author Paul Maddox <paul.maddox@gmail.com>
 * @copyright Paul Maddox 9 Apr 2010
 */
class NagiosEvent extends BaseData {

	protected $idNagiosEvent;
	protected $chrNagiosHost;
	protected $chrState;
	protected $chrSeverity;
	protected $intDate;
	protected $chrHost;
	protected $chrShortDesc;
	protected $chrLongDesc;
	protected $chrHash;
	protected $blnRecovered;

	/**
	 * Parses a line from the nagios.log into a NagiosEvent object
	 * @param $chrLine
	 * @return true
	 */
	public static function parse( $chrLine ) {
		
		$chrPattern = '/^\[([\d]+)\] SERVICE ALERT: ([^;]+);([^;]+);([^;]+);([^;]+);([^;]+);(.*)$/';
		preg_match_all($chrPattern, $chrLine, $arrResults);
		
		if(is_array($arrResults) && sizeof($arrResults[7]) == 1){
			
			$objNagiosEvent = new NagiosEvent();
			$objNagiosEvent->setDate($arrResults[1][0]);
			$objNagiosEvent->setHost($arrResults[2][0]);
			$objNagiosEvent->setState($arrResults[4][0]);
			$objNagiosEvent->setShortDesc($arrResults[3][0]);
			$objNagiosEvent->setLongDesc($arrResults[7][0]);
			$objNagiosEvent->setSeverity($arrResults[5][0]);
			$objNagiosEvent->setHash(md5($chrLine));
			
			# Bit of a bodge to treat UNKNOWN events
			# as criticals - which they normally are
			if($objNagiosEvent->getState() == 'UNKNOWN'){
				$objNagiosEvent->setState('CRITICAL');
			}

			if($objNagiosEvent->getState() == 'WARNING' || $objNagiosEvent->getState() == 'OK' || $objNagiosEvent->getSeverity() != 'HARD'){
				$objNagiosEvent->setRecovered(true);
			} else {
				$objNagiosEvent->setRecovered(false);
			}
			
			return $objNagiosEvent;
			
		} else {
			return false;
		}
			
	} # end method
	
	/**
	 * Purges out records older than a week
	 * @param params
	 * @return true
	 */
	public static function purge( ) {
				
		$chrQuery = "
			DELETE FROM NagiosEvent
			WHERE intDate < ?
		";
		
		$objQuery = DB::prepare($chrQuery);
		
		$objQuery->execute(array(
			time() - 604800
		));
			
		return true;
			
	} # end method
	
	
	/**
	 * Checks if a nagios event has recovered
	 * @param 
	 * @return $blnRecovered
	 */
	public function hasRecovered(  ) {
				
		if($this->isRecovered()){
			return true;
		}
		
		if($this->getState() != 'CRITICAL'){
			$this->setRecovered(true);
			$this->update();
			return true;
		}
		
		$chrQuery = "
			SELECT idNagiosEvent 
			FROM NagiosEvent
			WHERE intDate > ?
			AND chrShortDesc = ?
			AND chrHost = ?
			AND (chrState = ? OR chrState = ?)
			AND chrNagiosHost = ?
		";
		
		$objQuery = DB::prepare($chrQuery);
		
		$objQuery->execute(array(
			$this->getDate(),
			$this->getShortDesc(),
			$this->getHost(),
			'OK',
			'WARNING',
			$this->getNagiosHost(),
		));
		
		if(sizeof($objQuery->fetchAll()) > 0){
			$this->setRecovered(true);
			$this->update();
			return true;
		} else {
			return false;
		}
      	
	} # end method
	
	
	/**
	 * Gets an array of the worst offenders for the last week
	 * @param $chrState
	 * @return $arrNagiosEvents
	 */
	public static function worstOffenders($chrState = 'CRITICAL', $chrSeverity = 'HARD') {

		$chrQuery = "
			SELECT chrNagiosHost, chrHost, chrShortDesc, COUNT(idNagiosEvent) as intCount 
			FROM NagiosEvent 
			WHERE intDate > (UNIX_TIMESTAMP(NOW()) - 604800) 
			AND chrState = ?
			AND chrSeverity = ?  
			AND chrNagiosHost != ?
			GROUP BY chrHost, chrShortDesc 
			ORDER BY intCount DESC 
			LIMIT 3;
		";
		$objQuery = DB::prepare($chrQuery);

		$objQuery->execute(array(
			$chrState,
			$chrSeverity,
			'nagios1.int.sisal.ingg.com',
		));

		$arrResults = $objQuery->fetchAll();
		
		$arrWorstOffenders = array();

		foreach($arrResults as $arrResult){
			
			$arrHost = explode('.', $arrResult['chrHost']);
			$chrHost = $arrHost[0];
			
			$arrWorstOffenders[] = array(
				'chrNagiosHost' => $arrResult['chrNagiosHost'],
				'chrHost' => $chrHost,
				'chrShortDesc' => $arrResult['chrShortDesc'],
				'intCount' => $arrResult['intCount'],
			);
		}

		return $arrWorstOffenders;

	}

	/**
	 * Gets an array of events/hour for the last 23hrs
	 * @param 
	 * @return $arrEventTimes
	 */
	public function getHourlyEvents($chrState = 'CRITICAL', $chrSeverity = 'HARD') {
		 $arrEventTimes = array();
		
		 for($i=0; $i<24; $i++){
		 	
		 	$intTime = strtotime("-$i hour", time());
			$intHour = date('H', $intTime);
			$intTime = strtotime("$intHour:00", $intTime);
		 	
		 	$chrQuery = "
		 		SELECT idNagiosEvent
		 		FROM NagiosEvent
		 		WHERE intDate > ?
		 		AND intDate < ?
				AND chrState = ?
				AND chrSeverity = ?
				AND chrNagiosHost != ?
		 	";
		 	
		 	$objQuery = DB::prepare($chrQuery);
		 	$objQuery->execute(array(
		 		$intTime,
		 		$intTime + 3600,
				$chrState,
				$chrSeverity,
				'nagios1.int.sisal.ingg.com',
		 	));
		 	
		 	$arrResults = $objQuery->fetchAll();
		 	
			$arrFound = array();
		 	foreach($arrResults as $arrResult){
		 		$arrFound[] = $arrResult['idNagiosEvent'];
		 	}

		 	$arrEventTimes[$intHour] = $arrFound;
		 	
		 }
		 
		 return array_reverse($arrEventTimes, true);
			
	} # end method
	
	/**
	 * Gets the outstanding nagios events
	 * @param $chrState
	 * @return $arrNagiosEvents
	 */
	public static function getOutstanding( $chrState = 'CRITICAL' ) {
				
		$chrQuery = "
			SELECT idNagiosEvent 
			FROM NagiosEvent
			WHERE blnRecovered = 0
			AND chrState = ?
			AND chrSeverity = ?
			ORDER BY intDate DESC
		";
		
		$objQuery = DB::prepare($chrQuery);
		$objQuery->execute(array(
			$chrState,
			'HARD',
		));
		
		$arrResult = $objQuery->fetchAll();

		$arrAll = array();
		foreach($arrResult as $arrResult){
			$arrAll[] = $arrResult['idNagiosEvent'];
		}
		
		$arrNagiosEvents = array();
		$arrDuplicates = array();
		
		foreach($arrAll as $idNagiosEvent){
	
			$objNagiosEvent = new NagiosEvent($idNagiosEvent);

			# We only care about HARD errors
			if($objNagiosEvent->getSeverity() != 'HARD'){
				continue;
			}
			
			# We're not interested if it's recovered already
			if($objNagiosEvent->hasRecovered()){
				continue;	
			}
				
			# Check for duplicates - if dupes exist, only present the oldest
			# unrecovered item
			
			$chrKey = $objNagiosEvent->getNagiosHost() . $objNagiosEvent->getHost() . $objNagiosEvent->getShortDesc();
			
			if(!isset($arrDuplicates[$chrKey]) || $objNagiosEvent->getDate() < $arrDuplicates[$chrKey]){
				$arrNagiosEvents[$chrKey] = $idNagiosEvent;
				$arrDuplicates[$chrKey] = $objNagiosEvent->getDate();
			}
			
		}
		
		return $arrNagiosEvents;
			
	} # end method
	
	
	
	
	
}






















































































