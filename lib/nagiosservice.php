<?php
/**
 * This class provides a parser and information store for nagios services
 *
 * @package NagiosService
 * @author Paul Maddox <paul.maddox@gmail.com>
 * @copyright Paul Maddox 8 Jul 2010
 */
class NagiosService extends BaseData {

	protected $idNagiosService;
	protected $idNagiosServer;
	protected $idNagiosHost;
	protected $chrName;
	private $arrFields;
	
	/**
	 * Class Constructor
	 * @param 
	 * @return 
	 */
	public function construct(  ) {
		
		if($this->isLoaded()){
			$this->loadFields();
		}
		
	} # end method
	
	/**
	 * Gets a value based on field name
	 * @param $chrField
	 * @return $chrValue
	 */
	public function loadFields() {
				
		$chrQuery = "
			SELECT NagiosField.chrName, NagiosValue.chrValue
			FROM NagiosValue
			LEFT JOIN NagiosField 
			ON NagiosValue.idNagiosField = NagiosField.idNagiosField
			WHERE idNagiosService = ?
		";
		
		$objQuery = DB::prepare($chrQuery);
		$objQuery->execute(
			array(
				$this->getID(),
			)
		);
		
		$arrValues = array();
		foreach($objQuery->fetchAll() as $intCount => $arrResult){
			$arrValues[$arrResult['chrName']] = $arrResult['chrValue'];
		}
			
		$this->arrFields = $arrValues;
			
	} # end method
	
	/**
	 * Gets a field value
	 * @param $chrField
	 * @return $this->arrFields[$chrField]
	 */
	public function getField( $chrField ) {
		return $this->arrFields[$chrField];
	} # end method
	
	/**
	 * Parses a nagios service definition
	 * @param $chrDefinition
	 * @return $objNagiosService
	 */
	public static function parse( $chrDefinition, $idNagiosServer ) {
		
		$objService = new self;		
		
		$objService->setNagiosServer($idNagiosServer);

		$chrPattern = '/(\w+)=(.*)$/msU';
		preg_match_all($chrPattern, $chrDefinition, $arrResults);
		
		# Determin the service description/name
		$intIndex = array_search('service_description', $arrResults[1]);
		
		if($intIndex !== false){
			$objService->setName($arrResults[2][$intIndex]);
		} else {
			# This is not a valid service - it has no description!
			return false;
		}
		
		# Now see if the host for this service check exists - create it if not
		$intIndex = array_search('host_name', $arrResults[1]);
		if($intIndex !== false){
				$arrHosts = NagiosHost::find_by_name($arrResults[2][$intIndex]);
				if(sizeof($arrHosts) > 0){
					$objService->setNagiosHost($arrHosts[0]);
				} else {
					$objHost = new NagiosHost();
					$objHost->setName($arrResults[2][$intIndex]);
					$objHost->add();
					$objService->setNagiosHost($objHost->getID());
				}
		}
		
		# And see if this service check exists in the DB - update/add it as necessary
		$idNagiosService = $objService->exists();
		if($idNagiosService === false){
			# Service doesn't exist already, so create it
			$objService->add();
		} else {
			# Update the existing one
			$objService->setID($idNagiosService);
			$objService->setLoaded(true);
			$objService->update();
		}
		
		$arrFields = NagiosField::get_names();
		
		foreach((Array) $arrResults[1] as $intCount => $chrField){

			# If the nagios field doesn't exist in the DB - add it!
			$idNagiosField = array_search($chrField, $arrFields);
			
			if($idNagiosField === false){
				$objNagiosField = new NagiosField();
				$objNagiosField->setName($chrField);
				$objNagiosField->add();
				$idNagiosField = $objNagiosField->getID();
			}
			
			$chrSQL .= "(?, ?, ?, ?),";
			
			$arrValues[] = $idNagiosField;
			$arrValues[] = $objService->getNagiosHost();
			$arrValues[] = $objService->getID();
			$arrValues[] = $arrResults[2][$intCount];
			
		}

		$chrQuery = "
				INSERT INTO NagiosValue
				(idNagiosField, idNagiosHost, idNagiosService, chrValue)
				VALUES 
				" . trim($chrSQL, ',') . "
				ON DUPLICATE KEY UPDATE 
				idNagiosField = VALUES(idNagiosField),
				idNagiosHost = VALUES(idNagiosHost),
				idNagiosService = VALUES(idNagiosService),
				chrValue = VALUES(chrValue) 
			";
		
		
		#DB::beginTransaction();	
		$objQuery = DB::prepare($chrQuery);
		$objQuery->execute($arrValues);
		#DB::commit();
		
		return $objService->getID();
			
	} # end method
	
}