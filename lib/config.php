<?php
/**
 * This class allows parameters stored in a configuration file to be referenced by PHP
 *
 * @package Config
 * @author Paul Maddox <paul.maddox@gmail.com>
 * @copyright Paul Maddox 4 Jan 2010
 */
class Config extends Base {

	private static $objInstance;
	protected $arrSettings;

	private function __construct(){

		$chrFile = APP_HOME . 'conf/config.ini';
		if(!$this->arrSettings = @parse_ini_file($chrFile, true)){
			die("Failed to parse the application configuration file (conf/config.ini)");
		}
	
	}

	/*##########################################################################*//**
	 * As this is a singleton always check if an existing instance already exists
	 * @param $chrValue (always lowercase)
	 * @return $blnValid
	 */
	public static function getInstance(){

		if(!isset(self::$objInstance)){
			self::$objInstance = new Config();
		}

		return self::$objInstance;

	}
	
	
	/*##########################################################################*//**
	 * Returns a configuration setting - always in lowercase for consistancy
	 * @param $chrValue (always lowercase)
	 * @return $blnValid
	 */
	public static function get($chrSetting){
		
		$objInstance = self::getInstance();

		$arrSetting = explode(':', $chrSetting);
	
		if(sizeof($arrSetting) > 1){
			# Requested a section & setting like 'Database:user'
			if(array_key_exists($arrSetting[0], $objInstance->arrSettings)){
				if(array_key_exists($arrSetting[1], $objInstance->arrSettings[$arrSetting[0]])){
					return $objInstance->arrSettings[$arrSetting[0]][$arrSetting[1]];
				}
			}
		}

		if(array_key_exists($chrSetting, $objInstance->arrSettings)){
			return $objInstance->arrSettings[$chrSetting];
		} 
		
		Error::fatal("Failed to retrieve config item: $chrSetting");

	}
}
