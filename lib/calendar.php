<?php
/**
 * This class provides calendar related functions to the application and can be 
 * extended to support different types of calendar (eg: MS Exchange / Google)
 *
 * @package Calendar
 * @author Paul Maddox <paul.maddox@gmail.com>
 * @copyright Paul Maddox 4 Jan 2010
 */
abstract class Calendar extends Base {
	
	protected $chrUsername;
	protected $chrPassword;
	protected $chrURL;
	
	/*##########################################################################*//**
	 * Gets a list of calendar appointments
	 * @param $intDay, $intMonth, $intYear
	 * @return $arrAppointments;
	 */
	abstract public function getAppointments( $intStart, $intFinish );
		
	/**
	 * Gets the oncall user(s) for a specified day
	 * @param $intDay, $intMonth, $intYear
	 * @return $arrUsers
	 */
	public function getOncallUser( $intStart = -1, $intFinish = -1) {
				
		if($intStart === -1){
			$intStart = time();
		}
		
		if($intFinish === -1){
			$intFinish = time();
		}
		
		# Due to the way oncall rota is 9am - 9am we need to check to see if it's
		# before 9am and if so, return the oncall user for the previous day
		$intHour = date('H', $intStart);
		
		if($intHour < 9){
			$intStart = strtotime('-1 day', $intStart);
			$intFinish = strtotime('-1 day', $intFinish);
		}
		
		$arrAllUsers = User::get_all();
		$arrUsers = array();
		$arrUserNames = array();
	
		$arrAppointments = $this->getAppointments($intStart, $intFinish);
		
		# Build up an array of users id=>name
		foreach($arrAllUsers as $idUser){
			$objUser = new User($idUser);
			$arrUserNames[$idUser] = $objUser->getName();
		}

		foreach($arrAppointments as $chrDate => $chrOncall){
			
			# Now use some levenshtein magic to see which user was most likely to be oncall
			
			$intPrevPercent = 0;
			$idLikelyUser = 0;
			
			foreach($arrUserNames as $idUser => $chrName){
				
				#$intLev = levenshtein($chrName, $chrOncall);
				
				similar_text($chrName, $chrOncall, $intPercent);

				#display("Testing $chrName against $chrOncall = Similar:$intPercent%");	

				if($intPercent > $intPrevPercent){
					$intPrevPercent = $intPercent;
					$idLikelyUser = $idUser;
				}
			
			}
			
			$arrUsers[strtotime($chrDate)] = $idLikelyUser;
	
		}
		
		return $arrUsers;
			
	} # end method
	
	
}

