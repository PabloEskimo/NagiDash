<?php
/**
 * This class provides an interface to the User table
 *
 * @package User
 * @author Paul Maddox <paul.maddox@gmail.com>
 * @copyright Paul Maddox 4 Jan 2010
 */
class User extends BaseData {
	
	protected $idUser;
	protected $chrName;
	protected $chrEmail;
	protected $chrUsername;
	protected $chrPassword;
	protected $chrPhoneNumber;
	protected $intWeekendRate;
	protected $intWeekdayRate;
	
	
	/*##########################################################################*//**
	 * Authenticates a password for a user
	 * @param $chrPassword
	 * @return $blnAuthenticated
	 * @todo Create LDAP connector
	 */
	public function authenticate( $chrPassword ) {
		
		switch(strtolower(Config::get('authentication:method'))){
			
			case 'ldap';
				# TODO finish this!
			
			break;
			
			case 'database':
				
				$chrQuery = "SELECT idUser 
							 FROM User
							 WHERE chrUsername = ?
							 AND chrPassword = ?";

				$objQuery = DB::prepare($chrQuery);
				
				$arrValues = array(
					$this->getUsername(),
					md5($chrPassword),
				);
				
				$objQuery->execute($arrValues);
				$arrRows = $objQuery->fetchAll();
				
				if(sizeof($arrRows) > 0){
					Session::setUsername($this->getUsername());
					return true;
				}
				
			break;
			
		}
	
		return false;
		
	}
	
	/**
	 * Finds a user by their phone number - applies logic to deal with international codes
	 * @param $chrPhoneNumber
	 * @return $arrUsers
	 */
	final public static function find_by_phone( $chrPhoneNumber ) {

		$arrUsers = array();
		
		# First search for the number as-is
		$arrUsers += User::find_by_phonenumber($chrPhoneNumber);
		
		# Now try without any whitespace
		$chrPhoneNumber = str_replace(' ', '', $chrPhoneNumber);
		$arrUsers += User::find_by_phonenumber($chrPhoneNumber);
		
		# Now try without a 0044 style international code
		$chrPattern = "#^00[\\d][\\d]#m";
		$chrPhoneNumber = preg_replace($chrPattern, "0", $chrPhoneNumber);
		$arrUsers += User::find_by_phonenumber($chrPhoneNumber);
		
		# Now try without a +44 style international code
		$chrPattern = "#^\\+[\\d][\\d]#m";
		$chrPhoneNumber = preg_replace($chrPattern, "0", $chrPhoneNumber);
		$arrUsers += User::find_by_phonenumber($chrPhoneNumber);
			
		return $arrUsers;
			
	} # end method
	
	
	
}