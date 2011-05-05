<?php
/**
 * This class provides LDAP functionality for authentication and information 
 *
 * @package LDAP
 * @author Paul Maddox <paul.maddox@gmail.com>
 * @copyright Paul Maddox 4 Jan 2010
 */
class LDAP extends Base {

    /** Constant for creating new  */
    const NEW_LDAP = -1;

    /** @var int */

    /** @var string */
    protected $chrBaseDN;
    protected $chrUsernameDN;
    protected $chrPassword;
    protected $chrServer;
    protected $arrFields;

    /*##########################################################################*//**
     *  constructor
     * @param
     * @return true
     */
    function __construct(){
    	
    	# Domain Controllers; more than one can be specified. Seperated by spaces
    	$this->setServer( Config::get('ldap:hostname') );

    	# The administrative username which will be used for searching LDAP. Must be full DN notation
		$this->setUsernameDN( Config::get('ldap:username') );

		# The password for the administrative username
		$this->setPassword( Config::get('ldap:password') );

		# When searching the LDAP directory, use this as the top-level
		$this->setBaseDN( Config::get('ldap:base') );

		# It's good practise to restrict searches to a specific list of fields
		# otherwise the LDAP server will return everything possible
		$arrFields = array(
			'objectCategory',
			'member',
			'memberof',
			'cn',
			'info',
			'description',
			'distinguishedname',
			'objectcategory',
			'samaccountname',
			'canonicalname',
			'url',
			'mail',
			'sn',
			'givenname',
		); # end array

		$this->setFields($arrFields);

    	return true;

    } # end function

    /*##########################################################################*//**
     * Get group members
     * @param $chrGroup
     * @return $arrMembers
     */
    function getGroupMembers($chrGroup){

    	$arrMembers = array();

    	$arrInfo = $this->getGroupInfo($chrGroup);

    	# Check we got back a valid result
    	if($arrInfo === false || $arrInfo['count'] != 1){
    		Error::fatal("Failed to get information for LDAP group: $chrGroup");
    	} # end if

    	foreach($arrInfo[0]['member'] as $chrKey => $chrDN){

    		if($chrKey === 'count'){
    			continue;
    		} # end if

    		# Split out the user name
    		$arrDN = ldap_explode_dn($chrDN, 1);
    		$chrName = $arrDN[0];

    		# If a result is a user, add it to the list of members
    		# if it's a group, ignore it. I cba to do the whole recursive
    		# group membership shite

    		$arrUser = $this->getUserInfo($chrName);

    		if($arrUser === false || !is_array($arrUser) || sizeof($arrUser) < 1){
	   			continue;
   			} else {
   				$arrMembers[] = $arrUser[0]['samaccountname'][0];
   			} # end if

    	} # end foreach

    	return $arrMembers;

    } # end function

    /*##########################################################################*//**
     * Gets information on an LDAP group
     * @param $chrGroup
     * @return $arrInfo
     */
    function getGroupInfo($chrGroup){

    	$objAuth = ldap_connect($this->getServer());
		if($objAuth === false){
			Session::wipe();
			Error::fatal("Failed to connect to LDAP server");
			return false;
		} # end if

		$objBind = ldap_bind($objAuth, $this->getUsernameDN(), $this->getPassword());
		if($objBind === false){
			Session::wipe();
			Error::fatal("Failed to connect to LDAP server (authentication error)");
			return false;
		} # end if

		$objSearch = ldap_search($objAuth, $this->getBaseDN(), "(&(objectCategory=group)(|(name=$chrGroup)(cn=$chrGroup)))", $this->getFields());
		if($objSearch === false){
			Session::wipe();
			Error::fatal("Failed to connect to LDAP server (permission error)");
			return false;
		} # end if

		# Make sure only ONE result was returned -- if not, they might've thrown a * into the username.  Bad user!
		if( ldap_count_entries($objAuth, $objSearch) != 1 ){
	        return false;
		} # end if

		$arrInfo = ldap_get_entries($objAuth, $objSearch);

		ldap_close($objAuth);
    	return $arrInfo;

    } # end function

    /*##########################################################################*//**
     * Gets information on an LDAP user
     * @param $chrUsername
     * @return $arrInfo
     */
    function getUserInfo($chrUsername){

    	$objAuth = ldap_connect($this->getServer());
		if($objAuth === false){
			Session::wipe();
			Error::fatal("Failed to connect to LDAP server");
			return false;
		} # end if

		#ldap_set_option($objAuth, LDAP_OPT_TIMELIMIT, 1);

		$objBind = ldap_bind($objAuth, $this->getUsernameDN(), $this->getPassword());
		if($objBind === false){
			Session::wipe();
			Error::fatal("Failed to connect to LDAP server (authentication error)");
			return false;
		} # end if

		$objSearch = ldap_search($objAuth, $this->getBaseDN(), "(&(objectCategory=user)(|(sAMAccountName=$chrUsername)(cn=$chrUsername)(mail=$chrUsername)))", $this->getFields());
		if($objSearch === false){
			Session::wipe();
			Error::fatal("Failed to connect to LDAP server (permission error)");
			return false;
		} # end if

		# Make sure only ONE result was returned -- if not, they might've thrown a * into the group name.  Bad user!
		if( ldap_count_entries($objAuth, $objSearch) != 1 ){
	        Error::fatal("Failed to load information for LDAP user: $chrUsername");
			return false;
		} # end if

		$arrInfo = ldap_get_entries($objAuth, $objSearch);

		ldap_close($objAuth);
    	return $arrInfo;

    } # end function

    /*##########################################################################*//**
     * Authenticates a username/password with ldap
     * @param $chrUsername, $chrPassword
     * @return false
     */
    function authenticate($chrUsername, $chrPassword){

    	if(strlen($chrPassword) < 1){
    		return false;
    	} # end if

    	$objAuth = ldap_connect($this->getServer());
		if($objAuth === false){
			Session::wipe();
			Error::fatal("Failed to connect to LDAP server");
			return false;
		} # end if

		$objBind = ldap_bind($objAuth, $this->getUsernameDN(), $this->getPassword());
		if($objBind === false){
			Session::wipe();
			Error::fatal("Failed to connect to LDAP server (authentication error)");
			return false;
		} # end if

		$objSearch = ldap_search($objAuth, $this->getBaseDN(), "(&(objectCategory=user)(|(sAMAccountName=$chrUsername)(cn=$chrUsername)(mail=$chrUsername)))", $this->getFields());
		if($objSearch === false){
			Session::wipe();
			Error::fatal("Failed to connect to LDAP server (permission error)");
			return false;
		} # end if

		# Make sure only ONE result was returned -- if not, they might've thrown a * into the username.  Bad user!
		if( ldap_count_entries($objAuth, $objSearch) != 1 ){
	        return false;
		} # end if

		$arrInfo = ldap_get_entries($objAuth, $objSearch);

		# Now, try to rebind with their full dn and password.
    	$objBind = @ldap_bind($objAuth, $arrInfo[0][dn], $chrPassword);
    	if( !$objBind || !isset($objBind)){
    		$objAudit = new Audit("Bad authentication attempt ($chrUsername / ***********)");
    		return false;
      	} # end if

    	ldap_close($objAuth);
    	return true;

    } # end function

} # end 


?>
