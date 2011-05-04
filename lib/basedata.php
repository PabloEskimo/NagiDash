<?php
/**
 * This class extends the Base class to automatically provide database related methods
 * to classes that require database interaction. It is similar to ActiveRecord in Ruby
 *
 * @package BaseData
 * @author Paul Maddox <paul.maddox@gmail.com>
 * @copyright Paul Maddox 4 Jan 2010
 */
abstract class BaseData extends Base  {

	/** @var bool */
	private $blnLoaded = false;

	/*##########################################################################*//**
	 * Default class constructor (can be overridden)
	 * @param $id
	 * @return true;
	 */
	public function __construct( $id = false ) {
				
		if($id > 0){
			$this->load($id);
		} else {
			$this->setID(0);
		}
	
	}
	
	/*###########################################################################*//**
	 * Returns the object identifier
	 * @return mixed
	 */
	final public function getID(){
		$chrClass = get_class($this);
		$chrIDMember = "id$chrClass";
		return $this->$chrIDMember;
	}
	
	/*###########################################################################*//**
	 * Sets the object identifier
	 * @return mixed
	 */
	final public function setID($id){
		$chrClass = get_class($this);
		$chrIDMember = "id$chrClass";
		$this->$chrIDMember = $id;
	}

	/*###########################################################################*//**
	 * Returns if the object was successfully loaded [from the database]
	 * @return bool $blnLoaded
	 */
	final public function isLoaded() {
		return $this->blnLoaded;
	} # end method

	/*###########################################################################*//**
	 * Used with array_walk to map database pulled data to an object
	 * @param mixed $value reference
	 * @param mixed $key
	 */
	final protected function mapObject($arrValues) {
		
		foreach($this->getFields() as $chrField => $chrValue){
			
			if(isset($arrValues[$chrField])){
				$this->$chrField = $arrValues[$chrField];
			}
			
		}
		
	} # end method
	
	/*##########################################################################*//**
	 * Adds a record into the database
	 * @param 
	 * @return true;
	 */
	function add(  ) {
		return $this->insert();
	} # end method
	
	/*##########################################################################*//**
	 * Inserts a record into the database	
	 * @return true;
	 */
	final public function insert() {

		$chrClass = get_class($this);
		
		$arrFields = $this->getFields();
		
		# We don't want to use the ID field
		unset($arrFields["id$chrClass"]);
		
		$chrFieldList = implode(',', array_keys($arrFields));
		$chrPlaceholders = trim(str_repeat('?, ', sizeof($arrFields)), ', ');
		
		# First prepare the SQL statement
		$chrQuery = "INSERT INTO $chrClass ($chrFieldList) VALUES ($chrPlaceholders)";
		$objQuery = DB::prepare($chrQuery);
		
		# And finally execute the insert
		$objQuery->execute(array_values($arrFields));
	
		$this->setID(DB::lastInsertId());
		
		$this->blnLoaded = true;
		
		return true;
	
	} # end method
	
	/*##########################################################################*//**
	 * Loads a record from the database
	 * @param $idRecord
	 * @return true;
	 */
	final public function load( $idRecord ) {

		$chrClass = get_class($this);
		
		# First prepare the query
		$chrQuery = "SELECT * FROM $chrClass WHERE id$chrClass = ?";
		$objQuery = DB::prepare($chrQuery);
		
		# Now execute the query
		$objQuery->execute(array($idRecord));
		$arrResults = $objQuery->fetchAll();
		
		# Check for dupe records
		if(sizeof($arrResults) > 1){
			$objError = new Error("Duplicate records found in $chrClass DB table", Error::FATAL);
		}
		
		# Check it actually found a result
		if(sizeof($arrResults) < 1){
			$objError = new Error("Failed to load $chrClass record from DB (id:$idRecord)", Error::FATAL);
		}
		
		$this->mapObject($arrResults[0]);
		$this->blnLoaded = true;
		
		return true;
	
	} # end method
	
	
	/*##########################################################################*//**
	 * Updates a record in the database
	 * @param 
	 * @return true;
	 */
	final public function update(  ) {
		
		$chrClass = get_class($this);
		
		if(!$this->isLoaded()){
			$objError = new Error("Failed to update $chrClass as the object was not loaded properly", Error::FATAL);
		}
		
		$arrFields = $this->getFields();
		
		# We don't want to update the ID field
		unset($arrFields["id$chrClass"]);
		
		$chrQueryFields = '';
		foreach($arrFields as $chrField => $chrValue){
			$chrQueryFields .= "$chrField = ?, ";
		}
		
		$chrQueryFields = trim($chrQueryFields, ', ');
		
		# First prepare the SQL statement
		$chrQuery = "UPDATE $chrClass SET $chrQueryFields WHERE id$chrClass = {$this->getID()}";
		$objQuery = DB::prepare($chrQuery);
		
		# And finally execute the insert
		$objQuery->execute(array_values($arrFields));
	
		return true;
	
	} # end method
	
	/*##########################################################################*//**
	 * Deletes a record from the database
	 * @param 
	 * @return true;
	 */
	final public function delete(  ) {
			
		$chrClass = get_called_class();
		
		if(!$this->isLoaded()){
			$objError = new Error("Failed to delete $chrClass as the object was not loaded properly");
		}
		
		$chrClass = get_class($this);
		
		
		# First prepare the SQL statement
		$chrQuery = "DELETE FROM $chrClass WHERE id$chrClass = ?";
		$objQuery = DB::prepare($chrQuery);
		
		# And finally execute the insert
		$objQuery->execute(array($this->getID()));
	
		return true;
	
	} # end method
	
	/*##########################################################################*//**
	 * Gets an array of record ID's based on an optional filter
	 * @param $chrKey = false, $chrValue = false, $chrFuzzy = false
	 * @return $arrIDs;
	 */
	final public static function find( $chrField = false, $chrValue = false, $blnFuzzy = false, $intLimit = 0) {
			
		$chrClass = get_called_class();
		
		if($intLimit > 0){
			$chrLimit = " LIMIT $intLimit";
		} else {
			$chrLimit = "";
		}
		
		
		if($chrField === false){
			$chrQuery = "SELECT id$chrClass FROM $chrClass ORDER BY id$chrClass ASC $chrLimit";
			$arrExecFields = array();
		} else {
			
			if($blnFuzzy){
				$chrQuery = "SELECT id$chrClass FROM $chrClass WHERE $chrField LIKE ? ORDER BY id$chrClass ASC $chrLimit";
				$arrExecFields = array("%$chrValue%");
			} else {
				$chrQuery = "SELECT id$chrClass FROM $chrClass WHERE $chrField = ? $chrLimit";  // ORDER BY id$chrClass ASC";
				$arrExecFields = array("$chrValue");
			}
		}
		
		$objQuery = DB::prepare($chrQuery);
		$objQuery->execute($arrExecFields);
		
		$arrSearch = array();
		foreach($objQuery->fetchAll() as $intCount => $arrRow){
			$arrSearch[] = (int) $arrRow[0];
		}
		
		return $arrSearch;
		
	} # end method
	
	/*##########################################################################*//**
	 * Gets all records of a datatype
	 * @param 
	 * @return $arrAll;
	 */
	final public static function get_all(  ) {
			
		return self::find();
		
	} # end method
		
	/*##########################################################################*//**
	 * Gets an array of field names from the child class to be used as DB field names
	 * @param 
	 * @return $arrFields
	 */
	final private function getFields(  ) {
						
		# Local class members defined here in BaseData shouldn't be treated as DB fields
		$arrLocalMembers = get_class_vars(get_class());
		
		# But members of the child class should be
		$arrFields = get_object_vars($this);
		
		# So remove the local members from the list of members
		foreach($arrLocalMembers as $chrKey => $chrValue){
			unset($arrFields[$chrKey]);
		}
		
		return $arrFields;
	
	} # end method
	
	/*###########################################################################*//**
	 * Automatically define the static methods required for a DB set
	 * @return $this->$property
	 */
	final public static function __callStatic($chrMethod, $arrArguments) {
			
		$chrClass = get_called_class();
		
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
			'find_by_',
		);
		
		$chrMethodPrefix = '';
		foreach($arrMethodPrefixes as $chrPrefix){
			
			if(substr($chrMethod, 0, strlen($chrPrefix)) == $chrPrefix){
				$chrMethodPrefix = $chrPrefix;
				break;
			}
		}
		
		$chrProperty = preg_replace("/^$chrMethodPrefix/", '', $chrMethod);

		if (empty($chrMethodPrefix) || empty($chrProperty)) {
			
				# Seems like we want to call a methods on a singleton - first lets check if the 
				# child class *is* a singleton (it'll have the getInstance method)
				if(in_array('getInstance', get_class_methods($chrClass))){
					$objInstance = $chrClass::getInstance();
					return call_user_func_array(array($objInstance, $chrMethod), $arrArguments);
				}
				
			return;
		}

		$arrDebug = end(debug_backtrace());

		switch($chrMethodPrefix){
		
			case 'find_by_':
				
				# To allow find_by_username to match chrUsername, we need to play
				# a few tricks with the case
				
				$arrFieldNames = array();
				foreach(array_keys(get_class_vars($chrClass)) as $chrField){
					$arrFieldNames[strtolower($chrField)] = $chrField;	
				}
				
				foreach($arrPropertyPrefixes as $chrPropertyPrefix){
					
					if(isset($arrFieldNames[strtolower($chrPropertyPrefix) . strtolower($chrProperty)])){
						$chrSearchField = $arrFieldNames[strtolower($chrPropertyPrefix) . strtolower($chrProperty)];
						return self::find($chrSearchField, $arrArguments[0]);
					}
					
				}				
				
				Page::alert("Unable to search $chrClass records by $chrProperty as this field doesn't exist");
				
			break;
			
		}
			
	}
	
	/*##########################################################################*//**
	 * Creates the database table if it doesn't already exist
	 * @param 
	 * @return true
	 */
	final private function createTable(  ) {
	
		$chrClass = get_class($this);
		
		# This array matches variable prefixes to their datatype
		$arrFieldPrefixes = array(
			'arr' => 'text',
			'chr' => 'text',
			'str' => 'text',
			'obj' => 'text',
			'int' => 'int(1)',
			'bln' => 'int(1)',
			'id' => 'int(1)',
		);
		
		$chrSQL = "CREATE TABLE IF NOT EXISTS `$chrClass` (\n";
		
		foreach($this->getFields() as $chrField => $chrValue){
			
			foreach($arrFieldPrefixes as $chrPrefix => $chrFieldDescription){
							
				if(substr($chrField, 0, strlen($chrPrefix)) == $chrPrefix){
				
					$chrFieldPrefix = $chrPrefix;
					$chrProperty = preg_replace("/^$chrFieldPrefix/", '', $chrField);
					
					if($chrField == "id$chrClass"){
						# Field is the primary key
						$chrSQL .= "\t$chrField $chrFieldDescription NOT NULL PRIMARY KEY AUTO_INCREMENT,\n";
					} else {
						$chrSQL .= "\t$chrField $chrFieldDescription,\n";
					}
					
					break;
				}
			}
		}
		
		
		$chrSQL = trim($chrSQL);
		$chrSQL = trim($chrSQL, ',');
		
		$chrSQL .= ")";
		
		DB::exec($chrSQL);
	
	} # end method
	
	/*##########################################################################*//**
	 * Installs the table to a DB
	 * @param PARAMS
	 * @return 
	 */
	final public function install() {
			
		$chrClass = get_class($this);
		
		# This array matches variable prefixes to their datatype
		$arrFieldPrefixes = array(
			'arr' => 'text',
			'chr' => 'text',
			'str' => 'text',
			'obj' => 'text',
			'int' => 'int(1)',
			'bln' => 'int(1)',
			'id' => 'int(1)',
		);
	
		# Ok first we need to get the current status of the table if it exists
		$arrTables = array();
		foreach( DB::query("SHOW TABLES") as $arrResult){
			$arrTables[] = $arrResult[0];
		}
		
		if(!in_array($chrClass, $arrTables)){
			# Table doesn't exist already
			# So create it
			return $this->createTable();
		}
		
		# Ok so the table exists, but does it have all of the required fields?
		$arrClassFields = array_keys($this->getFields());
		$arrDatabaseFields = array();
		
		foreach(DB::query("DESCRIBE $chrClass") as $arrResult){
			$arrDatabaseFields[] = $arrResult['Field'];
		}
			
		foreach($arrClassFields as $chrField){
			if(!in_array($chrField, $arrDatabaseFields)){
				
				
				foreach($arrFieldPrefixes as $chrPrefix => $chrFieldDescription){
							
					if(substr($chrField, 0, strlen($chrPrefix)) == $chrPrefix){
					
						$chrFieldPrefix = $chrPrefix;
						$chrProperty = preg_replace("/^$chrFieldPrefix/", '', $chrField);
						
						if($chrField == "id$chrClass"){
							# Field is the primary key
							$chrSQL = "ALTER TABLE $chrClass ADD COLUMN $chrField $chrFieldDescription NOT NULL PRIMARY KEY AUTO_INCREMENT";
							DB::exec($chrSQL);
						} else {
							$chrSQL = "ALTER TABLE $chrClass ADD COLUMN $chrField $chrFieldDescription";
							DB::exec($chrSQL);
						}
						
						break;
					}
				}
				
			}
		}
		
		return true;
	
	} # end method

} # end class