<?php

$cookieFile = "_ga=GA1.2.235854238.1621906150; locale=eyJpdiI6Imd0MVBtV0M5Mk1XZzVyR2hLR2d3SWc9PSIsInZhbHVlIjoiNGlreTYxYlJVSHpzdDFvYXlvTTBQdz09IiwibWFjIjoiYjIyMjIwMjRlNGRmY2MyOTFjNTgwZGU3NzY1YTY4OWM3ZDZhOGMwZWEwM2EzOTUxMGZhMmU2Y2UzMDJjYzAyZiJ9; _gid=GA1.2.1644045343.1623427199; _gat_gtag_UA_78058015_2=1; _gat=1; io=UyCsOOfniavvjGyaBZ5w; XSRF-TOKEN=eyJpdiI6IlNoclhic3YxSUpKM2NMWlllUlhQOVE9PSIsInZhbHVlIjoiWVwvMHFiTXd6YXZcLzFkRm0wK1QxM2ZZTEN6a01MMFlrUnZxdU9NNm1ia2JDS3ZLWGwxXC9rcEt6aFdSVHRwUGJjNCIsIm1hYyI6IjJmNGY5NDY1MGI1OWVlZTNhOWZhMWZhNjU0N2QwOGMwZjdiOWJhNDYyYjE1NTJhMTUzYWY1YTA3NjBkY2JmYjUifQ%3D%3D; hm_session=eyJpdiI6InhNbDZUQWpEYnp1WjNCY2FPaDIxdVE9PSIsInZhbHVlIjoid0tkb3NCZjVOaG9tZXl5c0ZDTVIwWVU5d2g1WExaTUNMQWw3WkEyN1dmUVU3NmplUXA4V1FCQmtkMklFZFJHOCIsIm1hYyI6IjcwODQxMDQ0NzY1NTk5NzZlNjNlMjA5YmNkM2U0ZDRmZjVkYzNiNDA3NTdiZTg4MmZmZTAyNDI0NzAyZmUyMWMifQ%3D%3D";

$types = array("Pfizer"=>5394,"Moderna"=>5396);

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

