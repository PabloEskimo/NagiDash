<?php
/**
 * This class provides a global constructor/destructor for placing code
 * due to be run at the beginning/end of code execution
 *
 * @package Destructor
 * @author Paul Maddox <paul.maddox@gmail.com>
 * @copyright Paul Maddox 19 Jul 2010
 */
class Destructor extends Base {

	protected $intStart;
	protected $intFinish;
	protected $intDuration;
	
	/**
	 * Class constructor
	 * @param 
	 * @return true
	 */
	public function __construct(  ) {
		
		$this->setStart(microtime(true));
		
		return true;
			
	} # end method
	
	/**
	 * Class destructor
	 * @param 
	 * @return true
	 */
	public function __destruct(  ) {
				
		$this->setFinish(microtime(true));
		$this->setDuration(round($this->getFinish() - $this->getStart() , 2));
		
		if(Config::get('debug:dbstats')){		
			display("Page took {$this->getDuration()}secs to load and ran " . DB::count() . " SQL queries");
		}

		return true;
			
	} # end method
	
	

}
