<?php
/**
 * This class extends the Base class to provide methods related to singletons
 *
 * @package BaseSingleton
 * @author Paul Maddox <paul.maddox@gmail.com>
 * @copyright Paul Maddox 4 Jan 2010
 */
abstract class BaseSingleton extends Base {

	/** @var object */
	protected static $objInstance = null;

	/*###########################################################################*//**
	 * Constructor
	 */
	protected function __construct() {
	} # end if

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

}
/** end class */