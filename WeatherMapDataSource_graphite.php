<?php
// Datasource for Graphite (http://graphite.wikidot.com/)
// - currently reports same value for in and out (used with LINKSTYLE onway)

// TARGET graphite:graphite_url/metric
//      e.g. graphite:system.example.com:8081/devices.servers.XXXXX.system.load.1min

class WeatherMapDataSource_graphite extends WeatherMapDataSource {

	private $regex_pattern = "/^graphite:([\w.]+(:\d+)?)\/([,()*\w.-]+)$/";

        function Init(&$map)
        {
                if(function_exists('curl_init')) { return(TRUE); }
                debug("GRAPHITE DS: curl_init() not found. Do you have the PHP CURL module?\n");

                return(FALSE);
        }

	function Recognise($targetstring)
	{
		if(preg_match($this->regex_pattern, $targetstring, $matches))
		{
			return TRUE;
		}
		else
		{
			return FALSE;
		}
	}

	function ReadData($targetstring, &$map, &$item)
	{
		if(preg_match($this->regex_pattern, $targetstring, $matches)) {
			$host = $matches[1];
			$key = $matches[3];

			// make HTTP request
			$url = "http://$host/render/?rawData&from=-3minutes&target=$key";
			debug("GRAPHITE DS: Connecting to $url");
			$ch = curl_init($url);
			curl_setopt_array($ch, array(
				CURLOPT_RETURNTRANSFER => true,
				CURLOPT_TIMEOUT => 3,
			));
			$data = curl_exec($ch);
			$status = curl_getinfo($ch, CURLINFO_HTTP_CODE);

			if ($status != 200) {
				debug("GRAPHITE DS: Got HTTP code $status from Graphite");
				return;
			}

			# Data in form: devices.servers.XXXXXXX.software.items.read.roc,1331035560,1331035740,60|3037.56666667,2995.4,None

			list($meta, $values) = explode('|', $data, 2);
			$values = explode(',', trim($values));
			
			# get most recent value that is not 'None'
			while(count($values) > 0) {
				$value = array_pop($values);
				if ($value !== 'None') {
					break;
				}
			}

			if ($value === 'None') {
				// no value found
				debug("GRAPHITE DS: No valid data points found");
				return;
			}
			
			return array($value, $value, time());
		}

		return false;
	}
}

// vim:ts=4:sw=4:
?>
