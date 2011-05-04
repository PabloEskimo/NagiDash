<?php
/**
 * This class allows HTTP operations via cURL
 *
 * @package HTTP
 * @author Paul Maddox <paul.maddox@gmail.com>
 * @copyright Paul Maddox 4 Jan 2010
 */
class HTTP extends Base {
	
	protected $chrURL;
	protected $chrUsername;
	protected $chrPassword;
	protected $objContext;
	protected $intResultCode;
	protected $chrResult;

	/*##########################################################################*//**
	 * Class Constructor
	 * @param $chrURL
	 * @return $chrHTML;
	 */
	function __construct( $chrURL = false ) {
		
		$this->setURL($chrURL);
		$this->setUsername('');
		$this->setPassword('');
		$this->setResult('');
		$this->setResultCode(false);
		
		
		$arrContext = array(
			'http' => array(
				'method' => 'GET',
				'timeout' => 10,
				'header'  => "Authorization: Basic " . base64_encode("{$this->getUsername()}:{$this->getPassword()}"),
			),
		);
		
		$this->setContext(stream_context_create($arrContext));
		stream_context_set_default($arrContext);
		
		if($this->getURL() !== false){
			$this->run();
		}
		
	} # end method
	
	/*##########################################################################*//**
	 * Gets a http/https page
	 * @param PARAMS
	 * @return RETURN
	 */
	function run() {
	
		$this->setResult( @file_get_contents($this->getURL(), false, $this->getContext()) );
		return $this->getResult();
		
	} # end method
	
	
	/**
	 * Quick-fire static function for getting HTTP pages
	 * @param $chrURL
	 * @return new self($chrURL)
	 */
	public static function get( $chrURL ) {
		
		$objHTTP = new self($chrURL);
		return $objHTTP->getResult();
		
	} # end method
	
	/**
	 * Quick-fire static function for getting HTTP headers
	 * @param $chrURL
	 * @return new $arrHeaders
	 */
	public static function headers( $chrURL ) {
		
		$objHTTP = new self;
		return @get_headers($chrURL);
	
	} # end method
	
}