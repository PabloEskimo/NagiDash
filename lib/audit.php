<?php
/**
 * This class provides a database auditing facility for logging messages
 *
 * @package Audit
 * @author Paul Maddox <paul.maddox@gmail.com>
 * @copyright Paul Maddox 4 Jan 2010
 */

class Audit extends BaseData {
	
	protected $idAudit;
	protected $chrIP;
	protected $intDate;
	protected $chrUsername;
	protected $chrInfo;
	protected $chrReferer; 

	/*##########################################################################*//**
	 * Logs a message
	 * @param $chrInfo = false
	 * @return true;
	 */
	public static function message($chrInfo) {

		# First, check if the database is connected, and if the Audit table exists
		if(DB::isConnected() && DB::tableExists('Audit')){
		
			$objAudit = new Audit();
			
			if(strlen($_SERVER['REMOTE_ADDR']) > 0){
				$objAudit->setIP($_SERVER['REMOTE_ADDR']);
			} else {
				$objAudit->setIP("Local CLI");
			}
	
			$objAudit->setDate(mktime());
			$objAudit->setInfo($chrInfo);
			
			if(isset($_SERVER['HTTP_REFERER'])){
				$objAudit->setReferer($_SERVER['HTTP_REFERER']);
			}
			
			if(strlen(Session::getUsername()) > 0){
				$objAudit->setUsername(Session::getUsername());
			} else {
				$objAudit->setUsername('n/a');
			}
	
			$objAudit->insert();
			
			return true;
	
		} else {
			
			return false;
			
		}
			
	} # end method
	
}




