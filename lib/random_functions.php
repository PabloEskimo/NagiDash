<?php

/*##########################################################################*//**
* Automatically load any classes that haven't been included
* @access public
*/
function autoload($chrClass) {
	
	$chrClass = strtolower($chrClass);
	
	if(is_file(APP_HOME . "lib/{$chrClass}.php")){
		require("../lib/{$chrClass}.php");
	} else {
		return false;
	}
	
}



/*##########################################################################*//**
* @return
* @access public
* @desc Displays a formatted date
* @author paul.maddox
*/
function format_short_date($dtDate){
	 return(strtoupper(date("d-M-Y H:i:s", $dtDate)));
} // End function;


/*#############################################################################
	formatFilesize : Formats a filesize to human readable format
##########################################################################*//**
* @return
* @access public
* @desc Formats a filesize to human readable format
* @author paul.maddox
*/
function format_filesize($bytes){

   if ($bytes >= 1099511627776) {
       $return = round($bytes / 1024 / 1024 / 1024 / 1024, 2);
       $suffix = "TB";
   } elseif ($bytes >= 1073741824) {
       $return = round($bytes / 1024 / 1024 / 1024, 2);
       $suffix = "GB";
   } elseif ($bytes >= 1048576) {
       $return = round($bytes / 1024 / 1024, 2);
       $suffix = "MB";
   } elseif ($bytes >= 1024) {
       $return = round($bytes / 1024, 2);
       $suffix = "KB";
   } else {
       $return = $bytes;
       $suffix = "B";
   } // End If

   $return .= $suffix;

   return $return;

} // End function;

/*#############################################################################
        display : Dumps the contents of an array/object/variable
##########################################################################*//**
* @return
* @access public
* @desc Dumps the contents of an array/object/variable
* @author paul.maddox
*/
function display($objObject){

	echo "
	<pre>" . print_r($objObject, true) . "</pre>
	";

	flush();

} // End Function


/*#######m#####################################################################
	function tidy()
##########################################################################*//**
 * @return $objTidy (just echo it)
 * @param $chrHTML
 * @desc Tidies your HTML for you, of course.
*/
function tidy(&$chrHTML, $blnShowErrors=false, $config=array(
	'indent' => TRUE,
	'output-xhtml' => TRUE,
	'wrap' => 200,
), $chrEncoding = 'ascii') {

	return($chrHTML);
	//$objTidy = tidy_parse_string($chrHTML, $config, 'UTF8');
	$objTidy = tidy_parse_string($chrHTML, $config, $chrEncoding);
	if ( method_exists( $objTidy, "cleanRepair" ) ) {
		$objTidy->cleanRepair();
		if ($objTidy->errorBuffer && defined('DEVELOPER') && $blnShowErrors) {
			display("The following errors were detected:\n".htmlspecialchars($objTidy->errorBuffer));
		} # end function
		return $objTidy->value;
	} else {
		if ( defined( 'DEVELOPER' ) ) {
			display( "cleanRepair() method missing from tidy object" );
		}
		$chrHTML = "<!-- tidy method cleanRepair missing. -->" . $chrHTML;
		return( $chrHTML );
	}
} # end function

function encode($chrText){
	return base64_encode(serialize($chrText));
} // End functoin

function decode($chrText){
	return unserialize(base64_decode($chrText));
} // End functoin


if(!function_exists('get_called_class')) {
function get_called_class() {
    $bt = debug_backtrace();
    $l = 0;
    do {
        $l++;
        $lines = file($bt[$l]['file']);
        $callerLine = $lines[$bt[$l]['line']-1];
        preg_match('/([a-zA-Z0-9\_]+)::'.$bt[$l]['function'].'/',
                   $callerLine,
                   $matches);

       if ($matches[1] == 'self') {
               $line = $bt[$l]['line']-1;
               while ($line > 0 && strpos($lines[$line], 'class') === false) {
                   $line--;
               }
               preg_match('/class[\s]+(.+?)[\s]+/si', $lines[$line], $matches);
       }
    }
    while ($matches[1] == 'parent'  && $matches[1]);
    return $matches[1];
  }
}

function array_searchi($str,$array){
    $found=array();
    foreach($array as $k=>$v){


        if(strtolower($v)==strtolower($str)){
            $found[]=$v;
        }
    }
    $f=count($found);
    if($f===0)return false;elseif($f===1)return $found[0];else return $found;
}


function xmlToArray($xml, $root = true) {

	if (!$xml->children()) {
		return (string)$xml;
	}

	$array = array();
	foreach ($xml->children() as $element => $node) {
		$totalElement = count($xml->{$element});

		if (!isset($array[$element])) {
			$array[$element] = "";
		}

		$attributes = $node->attributes();

		// Has attributes
		if ($attributes) {
			$data = array(
				'attributes' => array(),
			);
 			if (!count($node->children())){
				$data['value'] = (string)$node;
			} else {
				$data = array_merge($data, xmlToArray($node, false));
			}
			foreach ($attributes as $attr => $value) {
				$data['attributes'][$attr] = (string)$value;
			}

			if ($totalElement > 1) {
				$array[$element][] = $data;
			} else {
				$array[$element] = $data;
			}
		// Just a value
		} else {
			if ($totalElement > 1) {
				$array[$element][] = xmlToArray($node, false);
			} else {
				$array[$element] = xmlToArray($node, false);
			}
		}
	}

	if ($root) {
		return array($xml->getName() => $array);
	} else {
		return $array;
	}
}
function time_since($time, $now=NULL, $fmt='l F jS, g:i a'){ 
    if($now === NULL){ 
        $now = time(); 
    } 
    $diff = $now - $time; 
    $today = date('dmy', $now) === date('dmy', $time) ? true : false;
    if($today && $diff < 60 * 60){ 
        $num = ceil($diff / 60); 
        return $num . 'min' . ($num > 1 ? 's' : '') . ' ago'; 
    }elseif($today){ 
        $num = floor($diff / 60 / 60); 
        return $num . 'hr' . ($num > 1 ? 's' : '') . ' ago'; 
    }else{ 
        $thisyear = date('y', $now) === date('y', $time) ? true : false;
        $daydiff = date('z', $now) - date('z', $time); 
        if($daydiff === 1 && $thisyear){ 
            return 'yesterday'; 
        } 
    } 
    return date('d/m/y', $time); 
}
