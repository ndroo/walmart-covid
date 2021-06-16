<?php

//Replace this cookie with one from your browser (the one here won't work, but im leaving it here so you know what to look for)
$cookieFile =  "_ga=GA1.2.1430305689.1623800171; _gid=GA1.2.1317163074.1623800171; locale=eyJpdiI6IkV4anA5NDh2VHFpbTltcEJ3ak1cLzBRPT0iLCJ2YWx1ZSI6IkRBd3RCU05wMzVXUzl2QnY2YnllcWc9PSIsIm1hYyI6IjEyZTI1MzhlN2NiNGU4YzMzNjFlNDAyM2M0NjdhNTRkNTFhNGUyN2RmMzBhZWQzOGM3MWIxY2YzOTlmZDQ5YWEifQ%3D%3D; io=S-d_MA62AiX17IxhA_PP; XSRF-TOKEN=eyJpdiI6IjZBNTFzNlh2YUwrK0s0aGlXVUgycEE9PSIsInZhbHVlIjoiOVcrMExzUWpYZjZzR0kybGtIXC9LcUdzbHJ0XC84bXVsYWNRY3ZcL1wvb1BrdllDcjhWcE9RUngxR1lJckJUc3NPWlYiLCJtYWMiOiJmYjJiNmRhNzg4NDdkZjMyODFjOTk1YTY0Y2M1MTA1MGVkNWI5MmI1NjNhNGIzMjFmOGMxODI2MTM2ZTNjODVjIn0%3D; hm_session=eyJpdiI6InlXbFliZjVzbFFabWcrMGt6eXRSRlE9PSIsInZhbHVlIjoiVmFDbGZzVEQra3VDNndQNFlOUTFDWjVBQVJ4YWNKd3pXY2p5MjRIXC9pK2d5K29pUTJqMEErelwvNE9lU1dKZ3U2IiwibWFjIjoiOWRmMzA3YTc3YTgwY2Q5NGU3NTE5YmI0Njc4MGZhZmI1YmNjMjUwZWViMThkMDM1ZmY1ZmYwYzE5M2ZkYzM1YyJ9";


$types = array("Pfizer 2nd dose"=>5394,"Moderna 2nd dose"=>5396);

$found = array();

$loc_ids = array();;
foreach($types as $key => $val) {

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

	$loc_ids = array_merge($result->data,$loc_ids);
}

$loc_ids = array_unique($loc_ids);

while(1) {
	for($i = 0; $i < count($loc_ids);++$i) {
		foreach($types as $key => $val) {
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
				$hash = md5(json_encode($result->data[0]));


				if(!isset($found[$hash])) {
					$found[$hash] = $result;
					echo_found($found[$hash]);
				}


			}
			else {
				//echo "Nothing at $id\n";
			}
		}
	}
}

function echo_found($result) {
	echo "Found at:" . time() ."\n";
	echo $result->data[0]->schedule_name . " (" . $result->data[0]->name .")\n";
	echo $result->data[0]->address .", " . $result->data[0]->city . "\n";
	foreach($result->data[0]->nextByLocId as $appt) {
		echo "WHEN: " . $appt->next . "\n";
	}
	echo "\n";
}
