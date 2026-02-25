<?php
include_once($_SERVER['DOCUMENT_ROOT'].'\inc\init.php');
function GetHost() {
	 if (isset($_REQUEST['search'])){
		 $ip = $_REQUEST['search'];
		 $isValid = filter_var($ip, FILTER_VALIDATE_IP);
		 if ($isValid != false){
			 $hostname = gethostbyaddr($isValid);
			 print $hostname;
			}
		 else{ print "Type valid IP to search";}
		}
	 else{
		 $ip = $_SERVER['REMOTE_ADDR'];
		 $isValid = filter_var($ip, FILTER_VALIDATE_IP);
		 if ($isValid != false){
			 $hostname = gethostbyaddr($isValid);
			 print $hostname;
			}
		 else{ print "Your IP is not valid";}
		}
	}
GetHost();
echo "<br>";
################################################################
function isAlive($host, $port = 80, $timeout = 5) {
	$connection = @fsockopen($host, $port, $errno, $errstr, $timeout);
	if (is_resource($connection)) {
		fclose($connection);
		return true;
	} else {
		return false;
	}
}

if (isset($_REQUEST['host'])){
	 $host = $_REQUEST['host'];
	}
else {
	 $host = "walla.co.il"; // Replace with the IP address or hostname you want to check
	}
if (isAlive($host)) {
	 echo "$host is alive.\n";
	} 
else {
	 echo "$host is down.\n";
	}

?>
