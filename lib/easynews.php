<?php
/**
 * This class provides an interface to easynews.com facilities
 *
 * @package Easynews
 * @author Paul Maddox <paul.maddox@gmail.com>
 * @copyright Paul Maddox 8 Feb 2010
 */

class Easynews extends Base {

	protected $chrHostname;
	protected $blnSSL;
	protected $arrResults;
	protected $chrUsername;
	protected $chrPassword;

	/*##########################################################################*//**
	 * Class Constructor
	 * @param
	 * @return true
	 */
	public function __construct(  ) {

		$this->setHostname(Config::get('easynews:hostname'));
		$this->setSSL(Config::get('easynews:ssl'));

		$this->setUsername(Config::get('easynews:username'));
		$this->setPassword(Config::get('easynews:password'));

		return true;

	}

	/*##########################################################################*//**
	 * Searches the Easynews database and parses results
	 * @param $chrPhrase
	 * @return $this->getResults;
	 */
	public function search( $chrPhrase ) {

		# Purge results older than 24hrs
		#Search::purge();

		# Lets see if this search has already been performed
		# and is cached in the database
		$arrSearches = Search::find_by_phrase($chrPhrase);

		if(sizeof($arrSearches) > 0){
			$objSearch = new Search($arrSearches[0]);
			$arrResults = SearchResult::find_by_search($objSearch->getID());

			if(sizeof($arrResults) > 0){
				return $objSearch;
			}
		}

		# Ok it's not cached so lets get on with the searching

		$objSearch = new Search();
		$objSearch->setPhrase($chrPhrase);
		$objSearch->setUsername(Session::getUsername());
		$objSearch->setDate(mktime());
		$objSearch->add();

		if($this->getSSL()){
			$chrURLPrefix = 'https://secure.';
		} else {
			$chrURLPrefix = 'http://';
		}

		$chrURL = $chrURLPrefix . Config::get('easynews:hostname') . '/global4/search.html?pby=100&gps=' . urlencode($chrPhrase) . '&fty\[\]=VIDEO&b1=150M&s1=dsize&s1d=-';

		$arrContext = stream_context_create(array(
		    'http' => array(
		        'header'  => "Authorization: Basic " . base64_encode($this->getUsername() . ':' . $this->getPassword()),
		    )
		));

		
		$chrResults = file_get_contents($chrURL, false, $arrContext); # DEBUG

		if($chrResults === false){
			Error::fatal('Failed to fetch search results from Easynews');
			exit;
		}

		$chrPattern = "#<td class=\"subject\".*</td>#i";
		preg_match_all($chrPattern, $chrResults, $arrMatches);

		$arrAdded = array();
		foreach($arrMatches[0] as $chrMatch){

			# FILE:  <A href="http://boost4-downloads.members.easynews.com/news/a/9/2/a923618fb7e0dfed015cc2aebbe72ac705b23f8b7.ts/Discovery.HD.-.The.Greatest.Fireworks.On.Earth.1080i.ts" target="subjTarget">(
			# THUMB: <A href="http://boost4-downloads.members.easynews.com/news/a/9/2/th-a923618fb7e0dfed015cc2aebbe72ac705b23f8b7.jpg/th-Discovery.HD.-.The.Greatest.Fireworks.On.Earth.1080i.jpg" target="thumblarge2">

			$chrPattern = "#<a.*href=\"(.*)\".*target=\"subjTarget\"#i";
			preg_match($chrPattern, $chrMatch, $arrFileMatches);
			$chrURL = $arrFileMatches[1];

			$chrPattern = "#<a.*href=\"(.*)\".*target=\"thumblarge2\"#i";
			preg_match($chrPattern, $chrMatch, $arrThumbMatches);
			$chrThumb = $arrThumbMatches[1];

			$chrPattern = "#<td class=\"fSize\".*>(.*)</td>#Ui";
			preg_match($chrPattern, $chrMatch, $arrSizeMatches);
			$chrSize = $arrSizeMatches[1];

			# De-dupe the results
			if(isset($arrAdded[$chrURL])){
				continue;
			} else {
				$objSearchResult = new SearchResult();
				$objSearchResult->setSearch($objSearch->getID());
				$objSearchResult->setURL($chrURL);
				$objSearchResult->setThumbnail($chrThumb);
				$objSearchResult->setSize($chrSize);
				$objSearchResult->add();
				$arrAdded[$chrURL] = $chrURL;
			}

		}

		return $objSearch;

	}

	/**
	 * Fetches information about the media via mediainfo binary
	 * @param $chrURL
	 * @return $arrInfo
	 */
	public function getInfo( $chrURL ) {

		if(!file_exists(Config::get('mediainfo:bin'))){
			$objError = new Error("Unable to find mediainfo binary - please install it", Error::FATAL);
			exit;
		}

		Progress::heading('Fetching file headers');
		Progress::update('Please wait...');

		$arrContext = stream_context_create(array(
		    'http' => array(
		        'header'  => "Authorization: Basic " . base64_encode($this->getUsername() . ':' . $this->getPassword()),
		    )
		));

		$chrTempFile = tempnam('', 'MEDIA-');

		$objFile = fopen($chrTempFile, 'w');
		fwrite($objFile, file_get_contents($chrURL, false, $arrContext, 0, 10000));
		fclose($objFile);

		$xmlInfo = simplexml_load_string(shell_exec(Config::get('mediainfo:bin') . " --Output=XML " . $chrTempFile));

		$arrRawInfo = xmlToArray($xmlInfo);

		$arrInfo = array();
		$intTrack = 0;

		foreach($arrRawInfo['Mediainfo']['File']['track'] as $arrTrack){

			switch(strtolower($arrTrack['attributes']['type'])){

				case 'video':
					unset($arrTrack['attributes']);
					$arrInfo['video'][] = $arrTrack;

				break;

				case 'audio':
					unset($arrTrack['attributes']);
					$arrInfo['audio'][] = $arrTrack;
				break;

				case 'general':
					unset($arrTrack['attributes']);
					$arrInfo['general'][] = $arrTrack;
				break;

				case 'text':
					unset($arrTrack['attributes']);
					$arrInfo['subtitle'][] = $arrTrack;
				break;

			}

			$intTrack++;

		}

		#unlink($chrTempFile);

		return $arrInfo;

	} # end method



}
