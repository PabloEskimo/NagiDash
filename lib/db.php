<?php
/**
 * This singleton class provides a database facility to the application via
 * PHP's PDO functionality. 
 *
 * @package Audit
 * @author Paul Maddox <paul.maddox@gmail.com>
 * @copyright Paul Maddox 4 Jan 2010
 */
class DB {
	
	private static $objInstance;
	private static $blnConnected = false;
	private static $intQueryCount = 0;
	
	/**
	 * Class Constructor - Create a new database connection if one doesn't exist
	 * Set to private so no-one can create a new instance via ' = new DB();'
	 */
	private function __construct() {} 
	
	/**
	 * Like the constructor, we make __clone private so nobody can clone the instance
	 */
	private function __clone() {}
	
	/**
	 * Returns DB instance or create initial connection
	 * @param 
	 * @return $objInstance;
	 */
	public static function getInstance(  ) {
			
		if(!self::$objInstance){
			self::$objInstance = new PDO(Config::get('database:dsn'), Config::get('database:username'), Config::get('database:password'));
			self::$blnConnected = true;			
			self::$objInstance->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		}
		
		return self::$objInstance;
	
	} # end method
	
	/**
	 * Passes on any static calls to this class onto the singleton PDO instance
	 * @param $chrMethod, $arrArguments
	 * @return $mix
	 */
	final public static function __callStatic( $chrMethod, $arrArguments ) {
			
		$objInstance = self::getInstance();
		
		if($chrMethod == 'prepare'){
			self::$intQueryCount++;
		}
		
		return call_user_func_array(array($objInstance, $chrMethod), $arrArguments);
		
	} # end method
	
	/**
	 * Checks whether a database connection is currently established
	 * @param 
	 * @return $blnConnected
	 */
	final public static  function isConnected() {
		return self::$blnConnected;
	} # end method
	
	/**
	 * Checks if a database table exists
	 * @param $chrTable
	 * @return $blnExists
	 */
	final public static function tableExists( $chrTable ) {

		$objQuery = DB::prepare("SELECT * FROM information_schema.tables WHERE TABLE_NAME = ? AND ? LIKE(CONCAT('%', TABLE_SCHEMA, '%'))");
		$objQuery->execute(array($chrTable, Config::get('database:dsn')));
		$arrResults = $objQuery->fetchAll();
		
		if(sizeof($arrResults) > 0){
			return true;
		} else {
			return false;
		}
		
	} # end method
	
	/**
	 * Returns the number of queries run since by this DB object
	 * @param 
	 * @return self::$intQueryCount
	 */
	final public static function count(  ) {
		return self::$intQueryCount;
	} # end method
	
}

