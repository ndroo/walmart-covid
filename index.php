<?php

function getMemcache() {
	$memcache = new Memcached;
	$memcache->addServer('127.0.0.1',11211);
	return $memcache;
}

function getCookie() {
        //obtain a cookie automatically
        $headers = array('authority: portal.healthmyself.net'
                , 'upgrade-insecure-requests: 1'
                , 'accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.9'
                , 'sec-fetch-site: same-origin'
                , 'sec-fetch-mode: navigate'
                , 'sec-fetch-user: ?1'
                , 'sec-fetch-dest: document'
                , 'referer: https://portal.healthmyself.net/walmarton/forms/Dpd');


        $ch = curl_init("https://portal.healthmyself.net/walmarton/guest/booking/form/8498c628-533b-41e8-a385-ea2a8214d6dc#/0G3/book/type");
        curl_setopt($ch, CURLOPT_HEADER, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

	$result = curl_exec($ch);
	curl_close($ch);

	preg_match_all('/^Set-Cookie:\s*([^;]*)/mi', $result, $matches);

	$cookies = array();
	foreach($matches[1] as $item) {
		parse_str($item, $cookie);
		$cookies = array_merge($cookies, $cookie);
	}

	$cookie_string = "";
	foreach($cookies as $key => $val) {
		$cookie_string .= $key."=".$val."; ";
	}

	return $cookie_string;
}

function getLocationIDs($cookie_string) {
	//get the locations
	$types = array("Pfizer 2nd dose"=>5394,"Moderna 2nd dose"=>5396);
	$found = array();

	$loc_ids = array();;
	foreach($types as $key => $val) {

		//get the locations
		$ch = curl_init("https://portal.healthmyself.net/walmarton/guest/booking/type/$val/locations");
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_COOKIE, $cookie_string); // Cookie aware
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_REFERER, 'https://portal.healthmyself.net/walmarton/forms/Dpd');

		$result = curl_exec($ch);
		curl_close($ch);

		$result = json_decode($result);
		if(!is_array($result->data)) {
			return false;
		}

		$loc_ids = array_merge($result->data,$loc_ids);
	}

	$ids = array();

	foreach($loc_ids as $obj) {
		if($obj->hasUnavailableAppointments == 0)
			$ids[] = $obj->id;
	}

	return $ids;
}


function checkLocation($id,$type,$cookie_string) {
	$ch = curl_init("https://portal.healthmyself.net/walmarton/guest/booking/$type/schedules?locId=$id");
	curl_setopt($ch, CURLOPT_HEADER, 0);
	curl_setopt($ch, CURLOPT_COOKIE, $cookie_string); // Cookie aware
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

	$result = curl_exec($ch);
	curl_close($ch);

	$result = json_decode($result);
	if($result == null) {
		return false;
	}
	else if($result->data[0]->available == true) {
		return $result->data[0];
	}
}

function formatResult($result) {
	$data = "";
	$data .= "<h2>" . $result->schedule_name . "</h2>";
	$data .= $result->name . "<br>";
	$data .= "Next available appointment: " . $result->nextByLocId[0]->next . "<br>";
	$data .= "Address: <br>";
	$data.= $result->address . ", " . $result->city . ", " . $result->postal . "<br>";
	$data.= "Phone: " . $result->phone;

	return $data;
}


function writeCache($data) {
	$time = time();
	getMemcache()->set("latest-".$time,$data);
	getMemcache()->set("latestTime",$time);
}


$types = array("Pfizer 2nd dose"=>5394,"Moderna 2nd dose"=>5396);

if(isset($_REQUEST['action'])) {


	if($_REQUEST['action'] == "refresh") {
		$cookie = getMemcache()->get("cookie");

		$locationIds = getLocationIDS($cookie);
		if($locationIds === false) {
			getMemcache()->set("cookie",getCookie());
			$locationIds = getLocationIDS(getCookie());
		}

		$data = array();
		//check if we need the locationIds or not
		if($locationIds === null) {
			//something bad has happened
			echo json_encode(array("error"=>"failed to get locationIds from API"));
		} else {
			//check the locations with availability
			foreach($locationIds as $location) {
				foreach($types as $description => $type_id) {
					$result = checkLocation($location,$type_id,$cookie);
					if($result !== null)
						$data[] = $result;

				}
			}
			$hash  = md5(json_encode($data));
			writeCache(array("data"=>$data,"hash"=>$hash,"time"=>time()));
			echo count($data) . " bookings found";
		}
	} else if($_REQUEST['action'] == "json") {
		$timeKey = getMemcache()->get("latestTime");
		$latest = getMemcache()->get("latest-".$timeKey);
		echo json_encode($latest);
	}
} else {
?>
        <!-- Global site tag (gtag.js) - Google Analytics -->
        <script async src="https://www.googletagmanager.com/gtag/js?id=G-SDMHP2GSLP"></script>
        <script>
        window.dataLayer = window.dataLayer || [];
        function gtag(){dataLayer.push(arguments);}
        gtag('js', new Date());

        gtag('config', 'G-SDMHP2GSLP');
	</script>

	<script>

		refreshData();

		async function refreshData() {
			var url = "/index.php?action=json"
			result = await fetch(url)
			var data = await result.json();
			console.log(data);

			//render
			await renderData(data)

			//refresh the data again
			await sleep(1000);
			refreshData();
		}



		function sleep(ms) {
			return new Promise(resolve => setTimeout(resolve, ms));
		}

		function seconds_since_epoch(d){
			return Math.floor( d / 1000 );
		}

		function renderData(data) {
			var d = new Date();
			var sec = seconds_since_epoch(d);

			document.getElementById("age").innerHTML = (sec - data.time) + " seconds ago";
			var bookingHtml = "";
			for(var i = 0; i < data.data.length; ++i) {
				var booking = data.data[i];
				bookingHtml += "<h2>" + booking.location + "</h2>";
				bookingHtml += "<p>"+booking.name+"<br/>"
				bookingHtml += "Next available appointment: <strong>" + booking.nextByLocId[0].next + "</strong><br/>"
				bookingHtml += "Address: " + booking.address+", " + booking.city + ", " + booking.postal + "<br/>"
				bookingHtml += "Phone: " + booking.phone + "<br/>"
				bookingHtml += "</p>"

			}

			document.getElementById("booking").innerHTML = bookingHtml;

		}
	</script>

	<h1>Avalable bookings at Walmarts in Ontario</h1>
	<p>This website updates every ~20 seconds. The last update was <span id="age"></span></p>
	<p><a href="https://portal.healthmyself.net/walmarton/forms/Dpd#/" target="_BLANK">Click here to make an appointment</a></p>

	<div id="booking"></div>

	<div>Hi, I'm Andrew and I made this site to help people get their vaccines. If you have any questions or concerns about this website please feel free to contact me andrewjohnmcgrath at gmail com</div>

<?php
}
