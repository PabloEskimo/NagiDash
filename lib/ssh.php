<?php
/**
 * This class provides SSH/SCP facilities
 *
 * @package SSH
 * @author Paul Maddox <paul.maddox@gmail.com>
 * @copyright Paul Maddox 4 Jan 2010
 */
class SSH extends Base {

	/** @var const */
	const NO_TIMEOUT = -1;
	const RETURN_ARRAY = 1;
	const RETURN_FULL = 2;
	const RETURN_PASSTHRU = 3;

	/** @var string */
	protected $chrHostname;
	protected $chrUsername;
	protected $chrKey;
	protected $chrCommand;

	/** @var int */
	protected $intReturn;
	protected $intReturnMethod;
	protected $intTimeout;

	/** @var array */
	protected $arrOutput;

	/** @var bool */
	protected $blnSuperuser;
	protected $blnLog;

	/*##########################################################################*//**
	 * Class constructor
	 * @param
	 * @return true
	 */
	function __construct(){

		$this->setTimeout(5);
		$this->setHostname('');
		$this->setUsername('root');
		$this->setKey(SSH_KEY);
		$this->setCommand('');
		$this->intReturn = false;
		$this->setOutput( array() );
		$this->setReturnMethod( self::RETURN_ARRAY );
		$this->setSuperuser(false);
		$this->setLog(false);

		return true;

	} # end function

	/*##########################################################################*//**
	 * Runs the SSH command
	 * @param
	 * @return $this->intReturn
	 */
	function run(){

		if($this->getTimeout() != self::NO_TIMEOUT ){
			$chrTimeout = "-o ConnectTimeout={$this->getTimeout()}";
		} else {
			$chrTimeout = "";
		} # end if

		# Clean out bad chars
		$chrExecute = str_replace('"', '\"', $this->getCommand());
		$chrExecute = str_replace('`', '\`', $chrExecute);

		if($this->blnSuperuser){
			$chrExecute = "sudo $chrExecute";
		}
		
		$chrCommand = "/usr/bin/ssh -o StrictHostKeyChecking=no -i {$this->getKey()} {$this->getUsername()}@{$this->getHostname()} \"$chrExecute\"";
	#		display($chrCommand);
	
		if($this->getLog()){
			$objFile = fopen('/tmp/ssh.log', 'a');
			fwrite($objFile, "\n" . $chrCommand . "\n");
			fclose($objFile);
		}

		switch($this->getReturnMethod()){

			case self::RETURN_PASSTHRU:
				echo "Return: " . passthru($chrCommand, $this->intReturn);
				flush();
			break;

			case self::RETURN_ARRAY:
				exec($chrCommand, $this->arrOutput, $this->intReturn);
			break;

		} # end switch

		if($this->intReturn != 0){
			#$objError = new Error("Failed to contact server: " . $this->getHostname(), Error::WARNING );
			return false;
		} else {
			return true;
		} # end if

	} # end function

	/*##########################################################################*//**
	 * Writes some text to a file
	 * @param $chrText
	 * @return true
	 * @todo Work out escape method for backticks
	 */
	function write($chrText, $chrFilename){

		$chrText = str_replace('\$', str_repeat('\\', 8) . '$', $chrText); # sheesh!
		$chrText = str_replace('\`', str_repeat('\\', 8) . '`', $chrText); # sheesh!
		$chrText = str_replace('\t' ,'\\\\\\\t', $chrText);
		$chrText = str_replace('"', '\\\\"', $chrText);
		$chrText = str_replace('\\\\\"' ,'\\\\\\\"', $chrText);
		$chrText = str_replace('$', str_repeat('\\', 7) . '$', $chrText); # sheesh!
		$chrText = str_replace('`', str_repeat('\\', 7) . '`', $chrText); # sheesh!

		$chrCommand = "sh -c \"cat <<RANDOMRANDOMBLAHRANDOM > $chrFilename
$chrText\"
";

		$this->setCommand($chrCommand);
		$this->run();

		return true;

	} # end function

	/*##########################################################################*//**
	 * Appends some text to a file
	 * @param $chrText
	 * @return true
	 * @todo Work out escape method for backticks
	 */
	function append($chrText, $chrFilename){

		$chrText = str_replace('\t' ,'\\\\\\\t', $chrText);
		$chrText = str_replace('"', '\\\\"', $chrText);
		$chrText = str_replace('\\\\\"' ,'\\\\\\\"', $chrText);
		$chrText = str_replace('$', str_repeat('\\', 7) . '$', $chrText); # sheesh!
		$chrText = str_replace('\$', str_repeat('\\', 8) . '$', $chrText); # sheesh!

		$chrCommand = "sh -c \"cat <<RANDOMRANDOMBLAHRANDOM >> $chrFilename
$chrText\"
";

		$this->setCommand($chrCommand);
		$this->run();

		return true;

	} # end function



} # end class

?>
