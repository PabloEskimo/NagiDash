<?php
/**
 * This class provides storage for Nagios servers
 *
 * @package NagiosServer
 * @author Paul Maddox <paul.maddox@gmail.com>
 * @copyright Paul Maddox 12 Jul 2010
 */
class NagiosServer extends BaseData {

	protected $idNagiosServer;
	protected $chrHostname;
	protected $chrAlias;
	protected $chrHash;
	protected $intUpdated;
	
	/**
	 * Gets the alias for a nagios server (nice name) unless it doesn't have one
	 * when it will return the hostname
	 * @param 
	 * @return $chrAlias
	 */
	public function getName() {
				
		if(strlen($this->getAlias()) > 0){
			return $this->getAlias();
		} else {
			return $this->getHostname();
		}
		
	} # end method
	
}