<?php
/**
 * This class writes out formatted HTTP pages to the client browser based on theme files 
 *
 * @package Page
 * @author Paul Maddox <paul.maddox@gmail.com>
 * @copyright Paul Maddox 4 Jan 2010
 */
class Page extends Base {
	
	private static $blnDrawn = false;
	
	protected $chrHeader;
	protected $chrFooter;
	protected $chrTitle;
	protected $chrSubtitle; 
	protected $chrHTML;
	protected $chrPageTitle;
	protected $chrPageSubtitle;
	protected $chrSummary;
	protected $chrBody;
	protected $chrLogout;
	 
	/*##########################################################################*//**
	 * Class Constructor
	 * @param 
	 * @return RETURN
	 */
	public function __construct(  ) {
			
		$this->setTitle(Config::get('general:name'));
		$this->setSubtitle(Config::get('general:heading'));	
		$this->setFooter(Config::get('general:footer'));
		
		if(strlen(Session::getUsername()) > 0){
			
			$arrUsers = User::find_by_username(Session::getUsername());
			$objUser = new User($arrUsers[0]);
			
			$this->setLogout("<img src=\"/images/icons/user.png\"> {$objUser->getName()} <a href=\"/login/wipe\">(logout)</a>");
		}
		
	} # end method
	
	/*##########################################################################*//**
	 * Loads a theme file
	 * @param $chrTheme
	 * @return true;
	 */
	protected function loadTheme( $chrTheme ) {
			
		if(!$this->chrHTML = @file_get_contents(APP_HOME . "themes/$chrTheme.theme")){
			Error::warning("Failed to load theme: $chrTheme");
			display("Failed to load theme: $chrTheme");
			exit;
		}
	
		foreach(get_object_vars($this) as $chrPlaceholder => $chrValue){
			$this->chrHTML = preg_replace("/<!--\{$chrPlaceholder\}-->/", $chrValue, $this->chrHTML);
		}
	
		return true;
	
	} # end method
	
	/**
	 * Checks if a page has already been outputted to the client/browser
	 * @param 
	 * @return self::$blnDrawn
	 */
	public static function isDrawn(  ) {
		return self::$blnDrawn;
	} # end method
	
	
	/*##########################################################################*//**
	 * Draws the page
	 * @param 
	 * @return true;
	 */
	public function draw( $blnExit = true ) {

		# If a page has already been drawn to the screen (eg: Progress window)
		# then clear the current browser window
		if(self::$blnDrawn){
			echo "<script language=\"javascript\">document.body.innerHTML = '';</script>"; 
			flush();
		}
		
		$this->loadTheme(Config::get('general:theme'));
		
		echo $this->chrHTML;
		flush();
		
		# Mark that a page has been drawn
		self::$blnDrawn = true;
		
		if($blnExit){
			exit;
		}
	
	} # end method

	/*#############################################################################
	        javascript
	##########################################################################*//**
	 * Return code encapsulated in JS script tags
	 * @param string $chrCode
	 * @return chrJavascript
	 */
	public static function javascript($chrCode) {
	
	        echo "
	        <script type=\"text/javascript\">
	                $chrCode
	        </script>";
	        flush();
	
	} # end function
	
	/*##########################################################################*//**
	* Displays a javascript alert message
	* @access public
	*/
	public static function alert($chrText){
	
		$chrText = str_replace("'", '\'', $chrText);
		echo "<script language=\"javascript\">Page::alert('$chrText');</script>\n";
		flush();
	
	}
	
	/*##########################################################################*//**
	 * Uses javascript to change the page location
	 * @param $chrURL
	 * @return true
	 */
	public static function location( $chrURL ) {
	
		# Determin our redirect method...
	
		if(Page::isDrawn()){
			# If a page has already been drawn using Page class then we cannot use header()
			# redirection as output has already been sent to the screen
			echo "<script language=\"javascript\"> document.location.href = '$chrURL'; </script>\n";
			flush();
		} else {
			header("Location: $chrURL", TRUE, 302);
		}
	
		return true;

}
	
	
}
