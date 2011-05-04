<?php
/**
 * This class provides job scheduling functionatlity for PHP applications
 * It works by creating a cronjob on the local system that runs jobs stored in DB
 *
 * @package Job
 * @author Paul Maddox <paul.maddox@gmail.com>
 * @copyright Paul Maddox 11 May 2010
 */
class Job extends BaseData {

	protected $idJob;
	protected $chrCommand;
	protected $chrSchedule;
	protected $blnEnabled;	
	protected $intCreated = 0;
	protected $intLastRun = 0;
	protected $intLastDuration = 0;
	protected $intRunCount = 0;
	
	/**
	 * Ensure the bootloader is installed in the crontab
	 * @param 
	 * @return true
	 */
	public static function install_bootloader(  ) {
				
		# First check if its already installed
		$chrBootloader = "http://{$_SERVER['HTTP_HOST']}/jobs";
		
		# Because this application may be called via many different URLs
		# and we don't want lots of different job bootloaders created
		# we will use a commented hash of the DOCUMENT_ROOT in the cron line
		# to ensure only one bootloader is cronned for each application
		$chrHash = md5($_SERVER['DOCUMENT_ROOT']);
		
		exec('crontab -l', $arrCurrentCron);
		
		$blnExists = false;
		
		foreach($arrCurrentCron as $chrLine){
			$chrNewCron .= "$chrLine\n";
			
			if(strstr($chrLine, $chrHash) !== false){
				# Cron for this application is already installed
				return true;
			}
			
		}		
		
		$chrNewCron .= "* * * * * wget -O /dev/null $chrBootloader # $chrHash\n";
		
		$chrTmp = tempnam('/tmp', 'crontab_');
		$objFile = fopen($chrTmp, 'w');
		fwrite($objFile, $chrNewCron);
		fclose($objFile);
		
		exec("crontab $chrTmp");
		unlink($chrTmp);
		
		return true;
			
	} # end method
	
	/**
	 * Uninstalls the bootloader from the crontab
	 * @param 
	 * @return true
	 */
	public static function uninstall_bootloader(  ) {

		$chrHash = md5($_SERVER['DOCUMENT_ROOT']);
		exec("crontab -l | grep -v $chrHash | crontab");
			
		return true;
			
	} # end method
	
	/**
	 * This is the bootloader. It's run every minute via a cron job automatically
	 * setup by Job::install_bootloader();
	 * @return $blnSuccess;
	 */
	public static function bootloader() {
				
		$arrJobs = Job::find_by_enabled(true);
	
		foreach($arrJobs as $idJob){
			
			$objJob = new Job($idJob);
			
			if($objJob->getLastRun() < $objJob->getLastScheduledRun()){
				$objJob->run();
			}
			
		}
			
		return true;
			
	} # end method
	
	/**
	 * Runs a job
	 * @param 
	 * @return true
	 */
	public function run(  ) {
				
		$intStart = microtime(true);
		
		eval($this->getCommand());
		
		$intPrevCount = $this->getRunCount();
		$this->setRunCount($intPrevCount + 1);
		
		$intFinish = microtime(true);
		$intDuration = ($intFinish - $intStart);
		
		$this->setLastDuration($intDuration);
		$this->setLastRun(time());
		
		$this->update();
			
		return true;
			
	} # end method
	
	/**
	 * Gets the last time a job should have run, based on the current time
	 * @param params
	 * @return return
	 */
	public function getLastScheduledRun(  ) {

		$arrSchedule = explode(' ', $this->getSchedule());
		
		if(sizeof($arrSchedule) != 5){
			$objError = new Error("Job has malformed schedule (ID: {$this->getID()}", Error::WARNING);
			return false;
		}
		
		if($arrSchedule[0] == "*"){
			$chrMinute = date('i'); 
		} else {
			$chrMinute = $arrSchedule[0];
		}
		
		if($arrSchedule[1] == "*"){
			$chrHour = date('H'); 
		} else {
			$chrHour = $arrSchedule[1];
		}
			
		if($arrSchedule[2] == "*"){
			$chrDay = date('d'); 
		} else {
			$chrDay = $arrSchedule[2];
		}
		
		if($arrSchedule[3] == "*"){
			$chrMonth = date('F'); 
		} else {
			$chrMonth = $arrSchedule[3];
		}
		
		$chrDate = "$chrDay $chrMonth " . date('Y') . " $chrHour:$chrMinute:00";
		
		return strtotime($chrDate);
			
	} # end method
	
	/**
	 * Quick way of adding new jobs (if they don't exist already)
	 * @param $chrCommand, $chrSchedule
	 * @return true
	 */
	public static function create( $chrCommand, $chrSchedule ) {
				
		$arrJobs = Job::find_by_command($chrCommand);
		
		foreach($arrJobs as $idJob){
			$objJob = new Job($idJob);
			if($objJob->getSchedule() == $chrSchedule){
				# A job with this command & schedule already exists
				return true;
			}
		}
			
		$objJob = new Job();
		$objJob->setCommand($chrCommand);
		$objJob->setSchedule($chrSchedule);
		$objJob->setEnabled(true);
		$objJob->setCreated(time());
		$objJob->add();
		
		return true;
			
	} # end method

	
	
}