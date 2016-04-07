<?php
	// Copyright 2016 Marcel Haupt
	// http://marcel-haupt.eu/
	//
	// Licensed under the Apache License, Version 2.0 (the "License");
	// you may not use this file except in compliance with the License.
	// You may obtain a copy of the License at
	//
	// http ://www.apache.org/licenses/LICENSE-2.0
	//
	// Unless required by applicable law or agreed to in writing, software
	// distributed under the License is distributed on an "AS IS" BASIS,
	// WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
	// See the License for the specific language governing permissions and
	// limitations under the License.
	//
	// Github Project: https://github.com/cbacon93/DCSServerStats

	
	
function echoSiteContent($mysqli) 
{
	if (isset($_GET['pid'])) 
	{
		echoPilotStatistic($mysqli, $_GET['pid']);
	} 
	else if (isset($_GET['flights'])) 
	{
		echo "<h2>Flights</h2><br><br>";
		echoFlightsTable($mysqli);
	} 
	else if (isset($_GET['aircrafts'])) 
	{
		echo "<h2>Aircrafts</h2><br><br>";
		echoAircraftsTable($mysqli);
	} 
	else if (isset($_GET['weapons'])) 
	{
		echo "<h2>Weapons</h2><br><br>";
		echoWeaponsTable($mysqli);
	} 
	else 
	{
		echo "<h2>Pilots</h2><br><br>";
		echoPilotsTable($mysqli);
	}
}
	
function timeToString($time) {
	$flight_hours = floor($time / 60 / 60);
	$flight_mins = floor($time / 60) - $flight_hours * 60;
	$flight_secs = $time  - $flight_mins * 60 - $flight_hours * 3600;
	
	if ($flight_mins < 10)
		$flight_mins = '0' . $flight_mins;
	if ($flight_secs < 10)
		$flight_secs = '0' . $flight_secs;
		
	return "$flight_hours:$flight_mins:$flight_secs";
}


function echoFooter($mysqli) {
	$result = $mysqli->query("SELECT * FROM dcs_parser_log ORDER BY id DESC LIMIT 1");
	if ($row = $result->fetch_object()) {
	
		echo "Last update at " . date('G:i', $row->time) . " processed " . $row->events . " events in " . $row->durationms .  " ms";
	}
}
	
	
function echoPilotsTable($mysqli) {
	echo "<table class='table_stats'>";
	echo "<tr class='table_header'><th>Pilot</th><th>Flights</th><th>Flight time</th><th>Kills</th><th>Ejections</th><th>Crashes</th><th>last active</th><th>status</th></tr>";
	
	$result = $mysqli->query("SELECT * FROM pilots ORDER BY flighttime DESC");
	$i = 0;
	
	while($row = $result->fetch_object()) {
		$onlinestatus = "<p class='pilot_offline'>On the Ground</p>";
		if ($row->online == 1)
			$onlinestatus = "<p class='pilot_online'>Flying</p>";
		
		echo "<tr onclick=\"window.document.location='?pid=" . $row->id . "'\" class='table_row_" . $i%2 . "'><td>" . $row->name . "</td><td>" . $row->flights . "</td><td>" . timeToString($row->flighttime) . "</td><td>" . $row->kills . "</td><td>" . $row->ejects . "</td><td>" . $row->crashes . "</td><td>" . date('d.m.Y', $row->lastactive) . "</td><td>" . $onlinestatus . "</td></tr>";
		
		$i++;
	}
	
	if ($i == 0) {
		echo "<tr><td style='text-align: center' colspan='8'>No Pilots listed</td></tr>";
	}
	
	echo "</table>";
}



function echoPilotsFlightsTable($mysqli, $pilotid) {	
	$result = $mysqli->query("SELECT * FROM flights, aircrafts WHERE flights.pilotid=" . $pilotid . " AND aircrafts.id=flights.aircraftid ORDER BY flights.id DESC LIMIT 10");
	
	echo "<table class='table_stats'>";
	echo "<tr class='table_header'><th>Aircraft</th><th>Coalition</th><th>Takeoff</th><th>Landing</th><th>Duration</th><th>Type of Landing</th></tr>";
	
	$i = 0;
	while($row2 = $result->fetch_object()) {
		echo "<tr class='table_row_" . $i%2 . "'><td>" . $row2->name . "</td><td>" . $row2->coalition . "</td><td>" . date('d.m.Y - H:i', $row2->takeofftime) . "</td><td>" . date('d.m.Y - H:i', $row2->landingtime) . "</td><td>" . timeToString($row2->duration) . "</td><td>" . $row2->endofflighttype . "</td></tr>";
		$i++;
	}
	
	if ($i == 0) {
		echo "<tr><td style='text-align: center' colspan='6'>No Flights listed</td></tr>";
	}
	
	echo "</table><br><br>";
}


function echoFlightsTable($mysqli) {	
	$result = $mysqli->query("SELECT flights.*, aircrafts.*, pilots.name as pname, pilots.id as pid FROM flights, aircrafts, pilots WHERE pilots.id=flights.pilotid AND aircrafts.id=flights.aircraftid ORDER BY flights.id DESC LIMIT 30");
	
	echo "<table class='table_stats'>";
	echo "<tr class='table_header'><th>Pilot</th><th>Aircraft</th><th>Coalition</th><th>Takeoff</th><th>Landing</th><th>Duration</th><th>Type of Landing</th></tr>";
	
	$i = 0;
	while($row2 = $result->fetch_object()) {
		echo "<tr onclick=\"window.document.location='?pid=" . $row2->pid . "'\" class='table_row_" . $i%2 . "'><td>" . $row2->pname . "</td><td>" . $row2->name . "</td><td>" . $row2->coalition . "</td><td>" . date('d.m.Y - H:i', $row2->takeofftime) . "</td><td>" . date('d.m.Y - H:i', $row2->landingtime) . "</td><td>" . timeToString($row2->duration) . "</td><td>" . $row2->endofflighttype . "</td></tr>";
		$i++;
	}
	
	if ($i == 0) {
		echo "<tr><td style='text-align: center' colspan='7'>No Flights listed</td></tr>";
	}
	
	echo "</table><br><br>";
}


function echoPilotsAircraftsTable($mysqli, $pilotid) {
	$result = $mysqli->query("SELECT pilot_aircrafts.flights, aircrafts.name, pilot_aircrafts.time, pilot_aircrafts.ejects, pilot_aircrafts.crashes, pilot_aircrafts.kills FROM pilot_aircrafts, aircrafts WHERE pilot_aircrafts.pilotid=" . $pilotid . " AND pilot_aircrafts.aircraftid=aircrafts.id ORDER BY pilot_aircrafts.time DESC");
	
	echo "<table class='table_stats'>";
	echo "<tr class='table_header'><th>Aircraft</th><th>Flights</th><th>Flight time</th><th>Kills</th><th>Ejections</th><th>Crashes</th></tr>";
	
	$i = 0;
	while($row = $result->fetch_object()) {
		echo "<tr class='table_row_" . $i%2 . "'><td>" . $row->name . "</td><td>" . $row->flights . "</td><td>" . timeToString($row->time) . "</td><td>" . $row->kills . "</td><td>" . $row->ejects . "</td><td>" . $row->crashes . "</td></tr>";
		$i++;
	}
	
	if ($i == 0) {
		echo "<tr><td style='text-align: center' colspan='6'>No Aircrafts listed</td></tr>";
	}
	
	
	echo "</table><br><br>";
}


function echoAircraftsTable($mysqli) {
	$result = $mysqli->query("SELECT aircrafts.flights, aircrafts.name, aircrafts.flighttime, aircrafts.ejects, aircrafts.crashes, aircrafts.kills FROM aircrafts ORDER BY aircrafts.flighttime DESC");
	
	echo "<table class='table_stats'>";
	echo "<tr class='table_header'><th>Aircraft</th><th>Flights</th><th>Flight time</th><th>Kills</th><th>Ejections</th><th>Crashes</th></tr>";
	
	$i = 0;
	while($row = $result->fetch_object()) {
		echo "<tr class='table_row_" . $i%2 . "'><td>" . $row->name . "</td><td>" . $row->flights . "</td><td>" . timeToString($row->flighttime) . "</td><td>" . $row->kills . "</td><td>" . $row->ejects . "</td><td>" . $row->crashes . "</td></tr>";
		$i++;
	}
	
	if ($i == 0) {
		echo "<tr><td style='text-align: center' colspan='6'>No Aircrafts listed</td></tr>";
	}
	
	echo "</table><br><br>";
}


function echoActiveFlight($mysqli, $pilotid) {
	$prep = $mysqli->prepare("SELECT dcs_events.InitiatorCoa, dcs_events.InitiatorType, dcs_events.time FROM pilots, dcs_events WHERE pilots.id=? AND dcs_events.event IN ('S_EVENT_TAKEOFF', 'S_EVENT_BIRTH_AIRBORNE') AND dcs_events.InitiatorPlayer=pilots.name ORDER BY dcs_events.id DESC LIMIT 1");
	$prep->bind_param('i', $pilotid);
	$prep->execute();

	$row = new stdClass();
	$prep->bind_result($row->coalition, $row->actype, $row->time);
	
	if ($prep->fetch()) {
		$duration = time() - $row->time;
		echo "<b>Active Flight:</b> <br>";
		echo "<table class='table_stats'><tr class='table_header'><th>Aircraft</th><th>Coalition</th><th>Takeoff</th><th>Duration</th></tr>";
		echo "<tr><td>" . $row->actype . "</td><td>" . $row->coalition . "</td>";
		echo "<td>" . date('H:i d.m.Y', $row->time) . "</td>";
		echo "<td><p class='js_timer'>" . timeToString($duration) . "</p></td></tr></table>";
			
		echo "<br><br>";
	}
	$prep->close();
}


function echoPilotStatistic($mysqli, $pilotid) {
	
	//get pilot information
	$prep = $mysqli->prepare("SELECT id, name, flighttime, flights, lastactive, online FROM pilots WHERE id=?");
	$prep->bind_param('i', $pilotid);
	$prep->execute();
	
	$row = new stdClass();
	$prep->bind_result($row->id, $row->name, $row->flighttime, $row->flights, $row->lastactive, $row->online);
	if ($prep->fetch()) {
		
		$pilotid = $row->id;
		$online = $row->online;
		$onlinestatus = "<p class='pilot_offline'>On the Ground</p>";
		if ($row->online == 1)
			$onlinestatus = "<p class='pilot_online'>Flying</p>";
		
		echo "<h2>Pilot " . $row->name . "</h2><br><br>";
		echo "<table class='table_stats'><tr class='table_row_0'><td>Total Flight Time: </td><td>" . timeToString($row->flighttime) . "</td></tr>";
		echo "<tr class='table_row_1'><td>Flights: </td><td>" . $row->flights . "</td></tr>";
		echo "<tr class='table_row_0'><td>Last Activity: </td><td>" . date('d.m.Y', $row->lastactive) . "</td></tr>";
		echo "<tr class='table_row_1'><td>Status: </td><td>" . $onlinestatus . "</td></tr></table>";
		echo "<br><br>";
		
		$prep->close();
		
		//try to print active flight
		if ($online == 1) {
			echoActiveFlight($mysqli, $pilotid);
		}
		
		echo "<b>Last Flights:</b>";
		echoPilotsFlightsTable($mysqli, $pilotid);
		
		echo "<b>Flown Airplanes</b>";
		echoPilotsAircraftsTable($mysqli, $pilotid);
				
	} else {
		$prep->close();
		echo "Pilot not found!";
	}
}


function echoWeaponsTable($mysqli) {
	$result = $mysqli->query("SELECT * FROM weapons ORDER BY hits DESC");
	
	echo "<table class='table_stats'>";
	echo "<tr class='table_header'><th>Weapon</th><th>Category</th><th>Shots</th><th>Hits</th><th>Kills</th></tr>";
	
	$i = 0;
	while($row = $result->fetch_object()) {
		echo "<tr class='table_row_" . $i%2 . "'><td>" . $row->name . "</td><td>" . $row->type . "</td><td>" . $row->shots . "</td><td>" . $row->hits . "</td><td>" . $row->kills . "</td></tr>";
		$i++;
	}
	
	if ($i == 0) {
		echo "<tr><td style='text-align: center' colspan='5'>No Weapons listed</td></tr>";
	}
	
	echo "</table><br><em>Gunshots are not counted.</em><br>";
}
 	
?>