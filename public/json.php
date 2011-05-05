<?php

include('../lib/common.php');

switch($_REQUEST['action']){
	
	case 'environments':
		showEnvironments();
	break;
	
	case 'beacons':
		showBeacons();
	break;
	
	case 'criticals':
		showServices(NagiosStatus::CRITICAL);
	break;
	
	case 'warnings':
		showServices(NagiosStatus::WARNING);
	break;
	
	default:
		showDescription();
	
}

/**
 * Shows the JSON RESTful API description
 * @param 
 * @return true
 */
function showDescription(  ) {
			
	$chrDescription = "
*****************************************************************
Name: Nagios Portal
Author: Paul Maddox <paul.maddox@gmail.com>
	
This application provides a single dashboard for multiple nagios
servers. It has a RESTful API (JSON) that can be used by external
services to obtain information about environments and the state
of nagios checks
*****************************************************************
	
URL:  /json
Type: GET
Desc: This information page

URL:  /json/environments
Type: GET
Desc: Returns a JSON output of the various nagios environments/servers

URL: /json/criticals 
Type: GET
Desc: Returns a JSON output all services currently in CRITICAL state

URL: /json/warnings
Type: GET
Desc: Returns a JSON output of all services currently in WARNING state

";

	display($chrDescription);
		
	return true;
		
} # end method



/**
 * Outputs a JSON representation of services with optional state
 * @param $intState = NagiosStatus::
 * @return return
 */
function showServices( $intState = NagiosStatus::OK ) {
			
	$arrServices = NagiosStatus::get_services($intState);
	
	$arrOutput = array();
	
	foreach($arrServices as $idNagiosService){
		
		$objService = new NagiosService($idNagiosService);
		$objServer = new NagiosServer($objService->getNagiosServer());
	
		$arrOutput[] = array(
			'id' => $idNagiosService,
			'hostname' => $objService->getField('host_name'),
			'description' => $objService->getField('description'),
			'output' => $objService->getField('plugin_output'),
			'state' => $objService->getField('current_state'),
			'updated' => $objService->getField('last_hard_state_change'),
			'last_critical' => $objService->getField('last_time_critical'),
			'last_warning' => $objService->getField('last_time_warning'),
			'environment' => array(
				'id' => $objService->getNagiosServer(),
				'name' => $objServer->getAlias(),
				'hostname' => $objServer->getHostname(),
			),
		
		);
		
	}
	
	echo json_encode($arrOutput);
		
	return true;
			
} # end method

/**
 * Outputs a JSON representation of the environments
 * @param 
 * @return true
 */
function showEnvironments(  ) {
			
	$arrServers = NagiosServer::get_all();
		
	$arrOutput = array();
	
	foreach($arrServers as $idServer){
		
		$objServer = new NagiosServer($idServer);
		
		$arrOutput[] = array(
			'id' => $idServer,
			'name' => $objServer->getAlias(),
			'hostname' => $objServer->getHostname(),
		);
		
	}
	
	echo json_encode($arrOutput);
	
	return true;
		
} # end method


/**
 * Outputs a JSON representation of the various beacons
 * @param 
 * @return true
 */
function showBeacons(  ) {
			
	$arrBeacons = NagiosService::find_by_name('BEACON STATUS FILE');
	
	$arrOutput = array();
	
	foreach($arrBeacons as $idBeacon){
		
		$objBeacon = new NagiosService($idBeacon);

		$objNagiosServer = new NagiosServer($objBeacon->getNagiosServer());
				
		$arrOutput[] = array(
			'id' => $idBeacon,
			'name' => $objNagiosServer->getAlias(),
			'output' => $objBeacon->getField('plugin_output'),
			'hostname' => $objBeacon->getField('host_name'),
			'state' => $objBeacon->getField('current_state'),
			'updated' => $objBeacon->getField('last_hard_state_change'),
			'last_critical' => $objBeacon->getField('last_time_critical'),
			'last_warning' => $objBeacon->getField('last_time_warning'),
		);
		
	}
	
	echo json_encode($arrOutput);	
		
	return true;
		
} # end method



$_REQUEST['action'];
$_REQUEST['id'];