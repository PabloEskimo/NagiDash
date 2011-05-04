<?php
/**
 * This class provides access to an existing RT ticketing system database
 *
 * @package RT
 * @author Paul Maddox <paul.maddox@gmail.com>
 * @copyright Paul Maddox 5 Jan 2010
 */
class RT extends Base {
	
	/* MySQL Connection Details */
	protected $chrHostname;
	protected $chrUsername;
	protected $chrPassword;
	protected $chrDatabase;
	
	/* Ticket Queue */
	protected $intQueueNumber;
	protected $chrQueue;
	
	/* MySQL Connection Object */
	protected $objConnection;
	
	
	/**
	 * Class Constructor
	 * @param 
	 * @return true
	 */
	public function __construct() {
				
		$this->setHostname(Config::get('rt:hostname'));
		$this->setUsername(Config::get('rt:username'));
		$this->setPassword(Config::get('rt:password'));
		$this->setDatabase(Config::get('rt:database'));
		
		$this->openConnection();
		
		$chrQueue = Config::get('rt:queue');
		
		$idQueue = $this->queueExists($chrQueue);
		
		if($idQueue){
			$this->setQueueNumber($idQueue);
			$this->setQueue($chrQueue);
		} else {
			$objError = new Error("Attempted to connect to a non existent RT queue ($chrQueue)", Error::FATAL);
		}
		
		return true;
			
	} # end method
	
	/**
	 * Open a connection to the RT database
	 * @param 
	 * @return $objConnection
	 */
	private function openConnection(  ) {
				
		$objConnection = mysql_connect($this->getHostname(), $this->getUsername(), $this->getPassword());
		mysql_select_db($this->getDatabase());
		$this->setConnection($objConnection);

		return $objConnection;
			
	} # end method
	
	/**
	 * Checks if an RT queue exists
	 * @param $chrQueue
	 * @return $blnExists
	 */
	public function queueExists( $chrQueue ) {
				
		$chrQuery = "
			SELECT id
			FROM Queues
			WHERE Name = '$chrQueue' 
		";
		
		$objQuery = mysql_query($chrQuery, $this->getConnection());
		while(($arrRow = mysql_fetch_assoc($objQuery)) !== false){
			return $arrRow['id'];
		}
		
		return false;
		
	} # end method
	
	/**
	 * Gets the RT tickets in a queue within the time period specified (unix_timestamp)
	 * @param $intStart, $intFinish
	 * @return $arrTickets
	 */
	public function getTickets( $intStart, $intFinish ) {

		$arrTickets = array();
		
		$chrQuery = "
			SELECT Tickets.*, Users.EmailAddress 
			FROM Tickets
			LEFT JOIN Users 
			ON Tickets.Creator = Users.id
			WHERE Tickets.Queue = {$this->getQueueNumber()}
			AND unix_timestamp(Tickets.Created) > $intStart 
			AND unix_timestamp(Tickets.Created) < $intFinish
			
		";
			
		$objQuery = mysql_query($chrQuery, $this->getConnection());
		while(($arrRow = mysql_fetch_assoc($objQuery)) !== false){
			$arrTickets[$arrRow['id']] = $arrRow;
		}
		
		return $arrTickets;
			
	} # end method
	
	

}