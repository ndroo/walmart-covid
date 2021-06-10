<?php

$cookieFile = "_ga=GA1.2.235854238.1621906150; _gid=GA1.2.1451323342.1623332059; io=L6ywU1M-TqlJtBDUBXfO; XSRF-TOKEN=eyJpdiI6InVtWU53SXpRQ2tCc3FPS2d4OEVCd1E9PSIsInZhbHVlIjoiXC9TRkRNSVZHdndoVCtTZG1ndWJGRmZoQ2hjS1wvcWJcL0FuR3hZMitZRnVpS1NTSHFpQ1RZM3FIMlNhQzhIN0VvbyIsIm1hYyI6IjQ3NTlmOWZhM2M1MjE3YjRkZDE2YmY5MGJjZGRhMGMwOTJkMWZhNjkwNWJkZDhkZGRlMDdmMWQxMjRlMmRjNzkifQ%3D%3D; hm_session=eyJpdiI6IlJQdzl2VUlyZmxlaXZDUlIrZE9iZHc9PSIsInZhbHVlIjoiNWpcL0dYYzFkVExiOVpleFZuUjBzcGowNUo4aXpaVmE4RzZPS1diOHBvSDFCU2ErdnlqZUIrOVI2dzZlcHdtTjUiLCJtYWMiOiI2YzJiMWRkY2Q0NzRiZDdiYWJhNmM2NTk2MTA5YmY0NzQ0Yzg3MGZiNmE4OWZjMzhjZGU0ZmE1MGIyMzM1ZGQ5In0%3D; locale=eyJpdiI6IlwvUWQzaEd0R1ppVEtIanlCUFZCSWFBPT0iLCJ2YWx1ZSI6IlVVc3NTaGt2RDR0OGo4bmw4SnhiOEE9PSIsIm1hYyI6ImQwNjIxNmMzMTA3YjczMTE1ODVlYzY0YzRkN2I3ZTkzZjQwNTFkZWU2ZmVhNDllODFkZWVmYTcxNjJjMTdjZWIifQ%3D%3D; _gat=1";

$types = array("Pfizer 2nd dose"=>5394,"Moderna 2nd dose"=>5396);

foreach($types as $key => $val) {

	echo "***Looking for $key appointments***\n\n";

	//get the locations
	$ch = curl_init("https://portal.healthmyself.net/walmarton/guest/booking/type/$val/locations");
	curl_setopt($ch, CURLOPT_HEADER, 0);
	curl_setopt($ch, CURLOPT_COOKIE, $cookieFile); // Cookie aware
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

	$result = curl_exec($ch);
	curl_close($ch);

	$result = json_decode($result);
	if(!is_array($result->data)) {
		echo "ERROR, could not get list of locations";
		die;
	}

	$loc_ids = $result->data;


	for($i = 0; $i < count($loc_ids);++$i) {
		$id = $loc_ids[$i];

		$ch = curl_init("https://portal.healthmyself.net/walmarton/guest/booking/$val/schedules?locId=$id");
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_COOKIE, $cookieFile); // Cookie aware
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

		$result = curl_exec($ch);
		curl_close($ch);

		$result = json_decode($result);
		if($result == null) {
			echo "ERROR, unable to contact API\n";
			break;
		}
		else if($result->data[0]->available == true) {
			//echo "Found $id!\n\n";
			echo $result->data[0]->schedule_name . " (" . $result->data[0]->name .")\n";
			echo $result->data[0]->address .", " . $result->data[0]->city . "\n";
			foreach($result->data[0]->nextByLocId as $appt) {
				echo "WHEN: " . $appt->next . "\n";
			}
			echo "\n";

		}
		else {
			//echo "Nothing at $id\n";
		}
	}
}

