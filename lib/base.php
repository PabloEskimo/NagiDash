<?php
/**
 * This class acts as a base to all other classes and provides automatic getter/setters
 *
 * @package Base
 * @author Paul Maddox <paul.maddox@gmail.com>
 * @copyright Paul Maddox 4 Jan 2010
 */
abstract class Base {

	/*###########################################################################*//**
	 * By default returns the classname for the instance (self)
	 * @return string $chrClassName
	 */
	public function __toString() {
		return get_class($this);
	} # end method

	/*###########################################################################*//**
	 * Automatically define the get/set methods for all properties
	 * @return $this->$property
	 */
	function __call($chrMethod, $arrArguments) {
		
		$arrPropertyPrefixes = array(
			'array' => 'arr',
			'string' => 'chr',
			'string (alt)' => 'str',
			'object' => 'obj',
			'integer' => 'int',
			'boolean' => 'bln',
			'identifier' => 'id',
		);
		
		$arrMethodPrefixes = array(
			'add',
			'remove',
			'get',
			'set',
			'is',
			'find_by_',
		);
		
		foreach($arrMethodPrefixes as $chrPrefix){
			
			if(substr($chrMethod, 0, strlen($chrPrefix)) == $chrPrefix){
				$chrMethodPrefix = $chrPrefix;
				break;
			}
		}
		
		$chrProperty = preg_replace("/^$chrMethodPrefix/", '', $chrMethod);
				
		if (empty($chrMethodPrefix) || empty($chrProperty)) {
			return;
		}

		$arrDebug = end(debug_backtrace());

		switch($chrMethodPrefix){
		
			case 'add':
				
				if(is_object($arrArguments[0]) || is_array($arrArguments[0])){
					$chrHash = md5(encode($arrArguments[0]));
				} else {
					$chrHash = md5($arrArguments[0]);
				}
				
				$chrTestProperty = "arr$chrProperty";
				
				if(property_exists($this, $chrTestProperty)){
					$this->{$chrTestProperty}[$arrArguments[0]] = $arrArguments[0];
					return true;
				} else {
					
					if(substr($chrTestProperty, -1, 1) === "y"){
						$chrTestProperty = preg_replace("/y$/", 'ies', $chrTestProperty);
						if(property_exists($this, $chrTestProperty)){
							$this->{$chrTestProperty}[$chrHash] = $arrArguments[0];
							return true;
						}
			
					} else {
						$chrTestProperty = $chrTestProperty . "s";

						if(property_exists($this, $chrTestProperty)){
							$this->{$chrTestProperty}[$chrHash] = $arrArguments[0];
							return true;
						}
					}
	
				}
				
				Page::alert("Unable to add " . get_class($this) . "::$chrProperty\\n{$arrDebug['file']} (line {$arrDebug['line']})");
				
			break;
			
			case 'remove':
				
				if(is_object($arrArguments[0])){
					$chrHash = md5(encode($arrArguments[0]));
				} else {
					$chrHash = md5($arrArguments[0]);
				}
				
				$chrTestProperty = "arr$chrProperty";

				if(property_exists($this, $chrTestProperty)){
					unset($this->{$chrTestProperty}[$arrArguments[0]]);
					return true;
				} else {
					
					if(substr($chrTestProperty, -1, 1) === "y"){
						$chrTestProperty = preg_replace("/y$/", 'ies', $chrTestProperty);
						if(property_exists($this, $chrTestProperty)){
							unset($this->{$chrTestProperty}[$chrHash]);
							return true;
						}
			
					} else {
						$chrTestProperty = $chrTestProperty . "s";
						if(property_exists($this, $chrTestProperty)){
							unset($this->{$chrTestProperty}[$chrHash]);
							return true;
						}
					}
	
				}
				
				Page::alert("Unable to add " . get_class($this) . "::$chrProperty\\n{$arrDebug['file']} (line {$arrDebug['line']})");
				
			break;
			
			case 'is':
			case 'get':
				
				foreach($arrPropertyPrefixes as $chrType => $chrPropertyPrefix){
					$chrTestProperty = $chrPropertyPrefix . $chrProperty;
					if(property_exists($this, $chrTestProperty)){
						return $this->$chrTestProperty;
					}
				}
				Page::alert("Unable to get " . get_class($this) . "::$chrProperty\\n{$arrDebug['file']} (line {$arrDebug['line']})");
				
			break;
			
			case 'set':		
			
				foreach($arrPropertyPrefixes as $chrType => $chrPropertyPrefix){
					
					$chrTestProperty = $chrPropertyPrefix . $chrProperty;
					if(property_exists($this, $chrTestProperty)){
						$this->$chrTestProperty = $arrArguments[0];
						return true;
					}
				}
				Page::alert("Unable to set " . get_class($this) . "::$chrProperty\\n{$arrDebug['file']} (line {$arrDebug['line']})");
				
			break;
			
		}
	
	}

	/*###########################################################################*//**
	 * Set version of {@link __get()}
	 * @see self::__get()
	 * @param string $chrVar
	 * @param mixed $mixValue
	 * @throws SPException
	 * @return mixed var
	 * @todo Rewrite using reflection as doesn't work with inheritence.
	 */
	public function __set( $chrVar, $mixValue ) {	

		$arrDebug = end(debug_backtrace());

		if (in_array($chrVar, get_object_vars($this))) {
			Page::alert("Setting ".get_class($this)."->$chrVar publicly.  Please stop this mal-practice, protect your members, and get/set them.\\n{$arrDebug['file']} (line {$arrDebug['line']})");
			$this->$chrVar = $mixValue;
		} else {
			Page::alert("Sorry, class member ".get_class($this)."->$chrVar doesn't exist\\n{$arrDebug['file']} (line {$arrDebug['line']})");
		} # end if
	} # end if

	/*###########################################################################*//**
	 * Now, this little bit of code will catch any calls to undefined class members.
	 * If you call something which does exist but not through a called method, then it'll
	 * work and return, but an alert will popup to tell you you're being dirty.
	 * If, however, the class member doesn't exist, it'll tell you where to go.  Sorted.
	 * @param string $chrVar
	 * @throws SPException
	 * @return mixed var
	 * @todo Rewrite using reflection as doesn't work with inheritence.
	 */
	public function __get( $chrVar ) {

		$arrDebug = end(debug_backtrace());

		if (in_array($chrVar, get_object_vars($this))) {
			
			Page::alert("Getting ".get_class($this)."->$chrVar publicly.  Please stop this mal-practice, protect your members, and get/set them.\\n{$arrDebug['file']} (line {$arrDebug['line']})");
			return $this->$chrVar;
		} else {
			Page::alert("Sorry, class member ".get_class($this)."->$chrVar doesn't exist\\n{$arrDebug['file']} (line {$arrDebug['line']})");
		} # end if
	} # end if
}
