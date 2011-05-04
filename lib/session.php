<?php
/**
 * This class provides a cookie based authentication/session system
 *
 * @package Session
 * @author Paul Maddox <paul.maddox@gmail.com>
 * @copyright Paul Maddox 4 Jan 2010
 */
class Session extends BaseData {

    /** Constant for creating new class */
    const NEW_Session = -1;
    
    /** @var int **/
    protected $idSession;
    
    /** @var string **/
    protected $chrSessionID;
    
    /** @var int **/
    protected $intDate;
    protected $chrUsername;
    
    /** @var object **/
    private $objUser;
    
    /** @var object */
	protected static $objInstance = null;

	/*###########################################################################*//**
	* Returns the singleton instance of this
	* @return self $objInstance
	 */
	final public static function getInstance() {
		
		if (!(self::$objInstance instanceof self)) {
			self::$objInstance = new self;
		} # end if

		return self::$objInstance;
	} # end method
	
   /*##########################################################################*//**
    * Checks if a session is required, and if one is established
    * If not, it'll redirect the user straight to the login page
    * @param $blnRequireAuth
    */
    public static function validate() {
    	
    	# Firstly, if this is the install script, the DB won't be setup yet
    	if($_SERVER['SCRIPT_NAME'] == '/install.php'){
    		return false;
    	}
    	
    	# First, as a matter of maintenance, wipe any existing sessions 
    	# in the database older than 8hrs
    	self::purge();
    	
    	$objSession = self::getInstance();
    	
    	if(isset($_COOKIE[Config::get('authentication:cookie')])){
    		
    		# Ok, we've found a cookie, check if it matches to a valid session
    		$arrSessions = Session::find_by_sessionid($_COOKIE[Config::get('authentication:cookie')]);
    		
    		if(sizeof($arrSessions) > 0){
    			# Cool, found a session! 
    			$objSession->load($arrSessions[0]);
    			
    			if($objSession->isLoaded()){
    				return true;
    			}
    			
    		}
    	    		
    	} # end if
    	
    	# Mmmph, we haven't found any valid sessions for the luser
    	# If the page in question isn't exempt, lets show them the door
    
    	$arrExempt = Config::get('authentication:exempt');
    	
    	if(in_array($_SERVER['SCRIPT_NAME'], $arrExempt) || $_SERVER['SCRIPT_NAME'] == '/login.php'){
    		return false;
    	} else {
    		Page::location('/login?f=' . urlencode($_SERVER['REQUEST_URI']));
    		exit;
    	}
    	
    } # end method
    
   
    /*##########################################################################*//**
     * Creates a session for a user
     * @param $chrUsername
     * @return true
     */
    public static function create($chrUsername){
        	
    	$objSession = self::getInstance();
    	
    	# Clear any previous sessions for this user
    	self::wipe($chrUsername, false);
    	
    	# Generate a unique session identifier, not already in DB
    	while(strlen($objSession->getSessionID()) < 1){
    		
    		$chrSessionID = md5(uniqid(rand(), true));
    		
    		$arrSessions = Session::find_by_sessionid($chrSessionID);
    		
    		if(sizeof($arrSessions) > 0){
    			# Oops, we've generated a session ID thats already in use
    			continue;
    		}
    		
    		$objSession->setSessionID($chrSessionID);
    		
    	}
    	
		$objSession->setUsername($chrUsername);
		$objSession->setDate(mktime());
		$objSession->add();
    	
        #$intExpires = time()+60*60*24; // 1 day
        $intExpires = 0; // Expires when browser closed
    
        setcookie(Config::get('authentication:cookie'), $objSession->getSessionID(), $intExpires, '/', false, false, true); # Create the new one 
        	
        return true;
    	
    } # end function
    
    /*##########################################################################*//**
     * Wipes a session straight from the user's cookie jar
     * @param 
     * @return true
     */
    public static function wipe($chrUsername){
    
    	$arrSessions = self::find_by_username($chrUsername);
    	
    	if(sizeof($arrSessions) > 0){
    	
    		$chrSessions = implode(', ', $arrSessions);
    		
	    	$chrQuery = "
	    		DELETE FROM Session
	    		WHERE idSession IN ($chrSessions)
	    	";
	    	
	    	$objQuery = DB::exec($chrQuery);
	    	
    	}
    	
    	setcookie(Config::get('authentication:cookie'), '', time() - 86400, '/', false, false, true); # Clear any previous cookies
    	
    } # end function
    
    /*##########################################################################*//**
     * Purges sessions older than intHours (for security)
     * @param $intHours
     * @return true
     */
    public static function purge($intHours = 8){
    
    	$intSeconds = $intHours * 60 * 60;
    	$intHistory = mktime() - $intSeconds;
    	
    	$chrQuery = "
    		DELETE FROM Session 
    		WHERE intDate < $intHistory
    	"; 
    	
    	DB::exec($chrQuery);
    
    	return true;
    	
	} # end functionn
	
	/**
	 * Gets the user object of the logged in user
	 * @param 
	 * @return $objSession->objUser
	 */
	public static function getUser(  ) {
				
		if(!is_object($objSession->objUser) || !$objSession->objUser->idLoaded){
			$arrUsers = User::find_by_username(self::getUsername());
			$objSession->objUser = new User($arrUsers[0]);
		} 
	    	
		return $objSession->objUser;
			
	} # end method
	
	/*##########################################################################*//**
	 * Gets the name of a logged in user
	 * @param 
	 * @return $this->objUser->getName()
	 */
	public static function getName(  ) {
	
		$objSession = self::getInstance();
		
		if(!is_object($objSession->objUser) || !$objSession->objUser->idLoaded){
			$arrUsers = User::find_by_username(self::getUsername());
			$objSession->objUser = new User($arrUsers[0]);
		} 
		
		return $objSession->objUser->getName();
	
	}
	
	/*##########################################################################*//**
	 * Gets the first name of the logged in user
	 * @param 
	 * @return $arrName[0]
	 */
	public static function getFirstName(  ) {
		$arrName = explode(' ', self::getName());
		return $arrName[0];
	}
	
	/*##########################################################################*//**
	 * Gets the surname of the logged in user
	 * @param 
	 * @return $arrName[0]
	 */
	public static function getSurname(  ) {
		$arrName = explode(' ', self::getName());
		return end($arrName);
	}
	
	/*##########################################################################*//**
	 * Gets the email of a logged in user
	 * @param 
	 * @return $this->objUser->getEmail()
	 */
	public static function getEmail(  ) {
	
		$objSession = self::getInstance();
		
		if(!is_object($objSessoin->objUser) || !$objSession->objUser->idLoaded){
			$arrUsers = User::find_by_username(self::getUsername());
			$objSession->objUser = new User($arrUsers[0]);
		} 
		
		return $objSession->objUser->getEmail();
	
	}
	
} # end class