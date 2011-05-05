<?php
/**
 * This class provides current status information for nagios checks from retention.dat
 *
 * @package NagiosStatus extends Base
 * @author Paul Maddox <paul.maddox@gmail.com>
 * @copyright Paul Maddox 6 Jul 2010
 */
class NagiosStatus extends Base {

	const OK = 0;
	const WARNING = 1;
	const CRITICAL = 2;
	
	protected $arrServices;
	protected $arrHosts;
	
	/**
	 * Class Constructor
	 * @param 
	 * @return true
	 */
	public function __construct($chrURL) {

		$arrHostname = explode('/', $chrURL);
		
		$arrServers = NagiosServer::find_by_hostname($arrHostname[2]);
		
		if(sizeof($arrServers) > 0){
			$idNagiosServer = $arrServers[0];
		} else {
			$objNagiosServer = new NagiosServer();
			$objNagiosServer->setHostname($arrHostname[2]);
			$objNagiosServer->setUpdated(time());
			$objNagiosServer->add();
			$idNagiosServer = $objNagiosServer->getID();
			
		}
		
		$intStart = microtime(true);
		$chrData = HTTP::get($chrURL);
		$intFinish = microtime(true);
		$intDuration = round($intFinish - $intStart, 2);
		display("Fetched $chrURL in $intDuration seconds");
		
		if(strlen($chrData) < 1){
			Error::warning("Failed to parse $chrURL");
			return false;
		}
		
		$objNagiosServer = new NagiosServer($idNagiosServer);
		$objNagiosServer->setUpdated(time());
		$objNagiosServer->update();
			
		# In order to avoid constantly inserting thousands of records into 
		# the database, we only want to process this feed if it's had a HARD
		# state change since last processing. This is quite important as without
		# it, MySQL runs at a constant 250 queries per second!
		
		# To achieve this we will take all of the 'last_hard_state_change' values
		# and compare them to a hash taken last time the import ran.
		
		# If there is no difference, skip this import!
		
		
		$chrPattern = '/last_hard_state_change=(.*)/i';
		preg_match_all($chrPattern, $chrData, $arrResults);
		$chrHash = md5(implode(',', $arrResults[1]));
		
		if($objNagiosServer->getHash() == $chrHash){
			# Yup, no hard status changes since last poll, so skip it
			return true;
		} else {
			# Whoa, there's changes! Update the hash and begin processing
			$objNagiosServer->setHash($chrHash);
			$objNagiosServer->update();
		}
		
		$intStart = microtime(true);
		
		$chrPattern = '/(\w+) {(.*)}/msU';
		preg_match_all($chrPattern, $chrData, $arrResults);
		
		$arrStatus = array();
		
		foreach($arrResults[1] as $intCount => $chrType){
			 
			switch($chrType){
				
				case 'service':
				case 'servicestatus':
					$this->addService( NagiosService::parse($arrResults[2][$intCount], $idNagiosServer) );
				break;
				
				case 'hoststatus':
					#$this->addHost( NagiosHost::parse($arrResults[2][$intCount]) );
				break;
				
			}
				
		}
		
		$intFinish = microtime(true);
		$intDuration = round($intFinish - $intStart, 2);
		
		display("Processed " . sizeof($this->getServices()) . " nagios services to DB in $intDuration seconds");
				
		return true;
			
	} # end method
	
	
	/**
	 * Gets the most recently recovered critical items
	 * @param $intNumber = 10
	 * @return $arrServices
	 */
	public static function get_recovered( $intNumber = 50 ) {
		
		$chrQuery = "
			SELECT NagiosValue.idNagiosService
			FROM NagiosValue
			LEFT JOIN NagiosField 
			ON NagiosValue.idNagiosField = NagiosField.idNagiosField
			WHERE NagiosField.chrName = ?
			ORDER BY NagiosValue.chrValue DESC
			LIMIT $intNumber
		";	
		
		$objQuery = DB::prepare($chrQuery);
		
		$objQuery->execute(
			array(
				'last_time_critical',
			)
		);
		
		$arrServices = array();
		foreach($objQuery->fetchAll() as $intCount => $arrResult){
			
			$objService = new NagiosService($arrResult['idNagiosService']);
			
			if($objService->getField('current_state') >= 2){
				continue;
			}
			
			$arrServices[] = $arrResult['idNagiosService'];
			
		}
	
		return $arrServices;
		
			
	} # end method
	
	/**
	 * Gets the outstanding critical alerts
	 * @param 
	 * @return mix
	 */
	public static function get_critical(  ) {
		return self::get_services();
	} # end method
	
	
	
	/**
	 * Gets all of the outstanding unacknowledged critical items
	 * @param params
	 * @return return
	 */
	public static function get_services($intState = 2) {

		$chrQuery = "
			SELECT NagiosValue.idNagiosService AS idService
			FROM NagiosValue
			LEFT JOIN NagiosField ON NagiosValue.idNagiosField = NagiosField.idNagiosField
			WHERE NagiosField.chrName = ?
			AND NagiosValue.chrValue = ?
		
			ORDER BY 
				(SELECT NagiosValue.chrValue 
				FROM NagiosValue
				LEFT JOIN NagiosField ON NagiosValue.idNagiosField = NagiosField.idNagiosField 
				WHERE NagiosValue.idNagiosService = idService
				AND  NagiosField.chrName = ?) DESC
		";
		
		$objQuery = DB::prepare($chrQuery);
		
		$objQuery->execute(
			array(
				'last_hard_state',
				$intState,
				'last_hard_state_change',
			)
		);
		
		$arrServices = array();
		foreach($objQuery->fetchAll() as $intCount => $arrResult){
			
			$objService = new NagiosService($arrResult['idService']);
		
			if($objService->getField('problem_has_been_acknowledged') != 0){
				# Problem has been acknowledged - skip it
				continue;
			}
			
			$arrServices[] = $arrResult['idService'];
			
		}
	
		return $arrServices;
			
	} # end method
	
	/**
	 * Polls the nagios status files and processes them into the database
	 * @param 
	 * @return true
	 */
	public static function poll(  ) {
				
		foreach(Config::get('nagios:status') as $chrURL){
			
			$objStatus = new NagiosStatus($chrURL);

		}
		
		display("Total of " . DB::count() . " database queries executed");
			
		return true;
			
	} # end method
	
}