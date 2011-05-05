<?php

include('../lib/common.php');

$arrBeacons = NagiosService::find_by_name('BEACON STATUS FILE');

if(sizeof($arrBeacons) > 0){
	
	$chrBeacons = "
		<tr>
			<td width=\"40\">&nbsp;</td>
			<td colspan=\"3\"><h2>Beacon Overview:</h2><br /></td>
		</tr>";
	
	foreach($arrBeacons as $idService){
		
		$objService = new NagiosService($idService);
		$objServer = new NagiosServer($objService->getNagiosServer());
		
		if($objService->getField('current_state') == NagiosStatus::CRITICAL){
			# Beacon is down
			$chrImage = "/images/red.png";
			$chrMessage = "<b>{$objServer->getName()}</b> beacon has been down since " . time_since($objService->getField('last_hard_state_change')) . " - TERMINALS SUSPENDED";
		} else {
			# Beacon is up
			$chrImage = "/images/green.png";
			$chrMessage = "<b>{$objServer->getName()}</b> beacon has been UP since " . time_since($objService->getField('last_time_critical')) . " - TERMINALS OK";
		}
		
		$chrBeacons .= "
		<tr>
			<td width=\"40\">&nbsp;</td>
			<td width=\"55\">
				<img src=\"$chrImage\">
			</td>
			<td colspan=\"2\">$chrMessage</td>
		</tr>";

	}
	
}

$blnEvents = false;


# Check each of the Nagios servers has been polled in the last 10mins
foreach(NagiosServer::get_all() as $idServer){
	
	$objServer = new NagiosServer($idServer);
	
	if($objServer->getUpdated() < (time() - 600)){	
		$chrEvents .= "
			<tr>
				<td width=\"40\">&nbsp;</td>
				<td width=\"55\">
					<img src=\"/images/yellow.png\">
				</td>
				<td width=\"100\">" . time_since($objServer->getUpdated(), time()) . "</td>
				<td>
					Failed to get current nagios information for {$objServer->getName()}
				</td>
			</tr>
			<tr>
		";
	}
}


# Now display any outstanding unacknowledged alerts
$arrCritical = NagiosStatus::get_critical();
foreach($arrCritical as $idNagiosService){
	
	$objService = new NagiosService($idNagiosService);
	
	$objServer = new NagiosServer($objService->getNagiosServer());
		
	$chrEvents .= "
	<tr>
		<td width=\"40\">&nbsp;</td>
		<td width=\"55\">
			<img src=\"/images/red.png\">
		</td>
		<td width=\"100\">" . time_since($objService->getField('last_hard_state_change'), time()) . "</td>
		<td>
			{$objServer->getName()} / {$objService->getField('host_name')} / {$objService->getField('service_description')} / {$objService->getField('plugin_output')}</td>
	</tr>
	<tr>
	";
	
	$blnEvents = true;
	
}

if($blnEvents){	
	$chrOutstanding .= "
	
		<tr>
			<td width=\"40\">&nbsp;</td>
			<td colspan=\"3\"><br /><h2>Outstanding faults:</h2><br /></td>
		</tr>
	
		$chrEvents";
} else {
	#$chrOutstanding .= "<tr><td colspan=\"4\"><br /><br /><div id=\"large_message\">There are currently no outstanding faults</div><br /><br /></td></tr>"; 
}


# And now show a history of recently recovered alerts
$arrRecovered = NagiosStatus::get_recovered(40);
$blnEvents = false;
foreach($arrRecovered as $idNagiosService){
	
	$objService = new NagiosService($idNagiosService);
	
	$objServer = new NagiosServer($objService->getNagiosServer());
		
	$chrRecovered .= "
	<tr>
		<td width=\"40\">&nbsp;</td>
		<td width=\"55\">
			<img src=\"/images/green.png\">
		</td>
		<td width=\"100\">" . time_since($objService->getField('last_time_critical'), time()) . "</td>
		<td>
			{$objServer->getName()} / {$objService->getField('host_name')} / {$objService->getField('service_description')} / {$objService->getField('plugin_output')}</td>
	</tr>
	
	";

}
	$chrHTML .= "
	
	<table>
		$chrBeacons
		$chrOutstanding
		<tr>
			<td width=\"40\">&nbsp;</td>
			<td colspan=\"3\"><br /><h2>Recently recovered faults:</h2><br /></td>
		</tr>
	
		$chrRecovered
	
	</table>";


$objPage = new Page();
$objPage->setBody($chrHTML);
$objPage->draw();



