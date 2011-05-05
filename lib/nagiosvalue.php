<?php
/**
 * This class provides storage for values pulled from nagios status files
 *
 * @package NagiosValue
 * @author Paul Maddox <paul.maddox@gmail.com>
 * @copyright Paul Maddox 9 Jul 2010
 */
class NagiosValue extends BaseData {

	protected $idNagiosValue;
	protected $idNagiosField;
	protected $idNagiosHost;
	protected $idNagiosService;
	protected $chrValue;

	/**
	 * Finds a nagios value based on Host/Service/Field
	 * @param $idField, $idService, $idHost
	 * @return true
	 */
	public static function fetch( $idField, $idService, $idHost ) {

		$chrQuery = "
			SELECT idNagiosValue
			FROM NagiosValue
			WHERE idNagiosField = ?
			AND idNagiosService = ?
			AND idNagiosHost = ?
		";
		
		$objQuery = DB::prepare($chrQuery);
		$objQuery->execute(
			array(
				$idField,
				$idService, 
				$idHost,
			)
		);
		
		$arrResults = $objQuery->fetchAll();
		
		if(sizeof($arrResults) > 0){
			return $arrResults[0]['idNagiosValue'];
		} else {
			return false;
		}
	
	} # end method
	
	/**
	 * Finds a nagios value based on Host/Service/Field
	 * @param $idField, $idService, $idHost
	 * @return true
	 */
	public static function get_by_service_host( $idService, $idHost ) {

		$chrQuery = "
			SELECT idNagiosValue
			FROM NagiosValue
			WHERE idNagiosService = ?
			AND idNagiosHost = ?
		";
		
		$objQuery = DB::prepare($chrQuery);
		$objQuery->execute(
			array(
				$idService, 
				$idHost,
			)
		);
		
		return $objQuery->fetchAll();
		
	
	} # end method
	
	
}