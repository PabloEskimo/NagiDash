<?php
/**
 * This class provides nagios server polling for notifications and imports them
 *
 * @package NagiosPoller
 * @author Paul Maddox <paul.maddox@gmail.com>
 * @copyright Paul Maddox 9 Apr 2010
 */
class NagiosPoller extends Base {

	protected $arrServers;
	
	/**
	 * Runs the poller
	 * @param 
	 * @return true
	 */
	public static function run(  ) {
		
		$intStart = microtime(true);
		
		$arrNagiosServers = Config::get('nagios:host');
		
		if(!is_array($arrNagiosServers) || sizeof($arrNagiosServers) < 1){
			$objError = new Error("Failed to find any valid nagios servers in config.ini", Error::FATAL);
			exit;
		}
		
		$intCount = 0;
		foreach($arrNagiosServers as $chrNagiosServer){
			
			$chrNewNotifications = file_get_contents($chrNagiosServer);
			
			foreach(explode("\n", $chrNewNotifications) as $chrLine){
				
				# First lets see if this line exists, if it does, skip it
				$arrNagiosEvents = NagiosEvent::find_by_hash(md5($chrLine));
				
				if(sizeof($arrNagiosEvents) > 0){
					continue;
				}
				
				
				# Ok, it doesn't already exist so parse & insert it to the DB
				
				$objEvent = NagiosEvent::parse($chrLine);
				
				if($objEvent !== false){
					
					# Ok parse the hostname out of the Nagios server URL 
					$chrPattern = "|http[s]?://([^/]+)|";
					preg_match_all($chrPattern, $chrNagiosServer, $arrResults);
					
					$objEvent->setNagiosHost($arrResults[1][0]);
					$objEvent->insert();
					$intCount++;
					
				}
				
			}

		}
		
		$intFinish = microtime(true);
		$intDuration = round($intFinish - $intStart, 2);
		$intServers = sizeof($arrNagiosServers);
	
		# Removed - fills up audit table too much!
		#Audit::message("Polled $intServers servers in $intDuration seconds and found $intCount new event(s)");
		
		return true;
			
	} # end method
	
}
