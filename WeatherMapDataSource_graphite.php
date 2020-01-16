<?php

/**
 * Datasource for Graphite (https://graphite.readthedocs.org)
 *
 * TARGET graphite:graphite_url/metric
 * Can report single value or two values like if_octets.rx and if_octets.tx
 * Graphite port numbers are also supported, so can use graphite.example.com:8080
 * @example graphite:graphite.example.com/devices.network.switch1.if_octets.rx
 * @example graphite:graphite.example.com/devices.network.switch1.if_octets.rx:devices.network.swtich1.if_octets.tx
 */
class WeatherMapDataSource_graphite extends WeatherMapDataSource {

    private $single_regex_pattern = "/^graphite:((?:[0-9]{1,3}\.){3}[0-9]{1,3}(?::[0-9]+)?|([a-zA-Z0-9](?:(?:[a-zA-Z0-9-]*|(?<!-)\.(?![-.]))*[a-zA-Z0-9]+)?(?::[0-9]+)?))\/([=,()*\w.-]+)$/";
    private $double_regex_pattern = "/^graphite:((?:[0-9]{1,3}\.){3}[0-9]{1,3}(?::[0-9]+)?|([a-zA-Z0-9](?:(?:[a-zA-Z0-9-]*|(?<!-)\.(?![-.]))*[a-zA-Z0-9]+)?(?::[0-9]+)?))\/([=,()*\w.-]+):([=,()*\w.-]+)$/";
    
    /**
     * Called after config has been read (so SETs are processed) but just before ReadData.
     * Used to allow plugins to verify their dependencies (if any) and bow out gracefully.
     *
     * @param $map WeatherMap
     * @return bool Return FALSE to signal that the plugin is not in a fit state to run at the moment.
     */
    function Init(&$map)
    {
        if(function_exists('curl_init')) return true;
        wm_debug("GRAPHITE DS: curl_init() not found. Do you have the PHP CURL module?\n");

        return false;
    }
    
    /**
     * @param $targetstring string The TARGET string
     * @return bool Depending on whether it wants to handle this TARGET called by map->ReadData()
     */
    function Recognise($targetstring)
    {
        return preg_match('/^graphite:/', $targetstring);
    }
    
    /**
     * @param $targetstring
     * @param $map WeatherMap
     * @param $item WeatherMapItem
     * @return array Returns an array of two values (in,out). -1,-1 if it couldn't get valid data
     */
    function ReadData($targetstring, &$map, &$item)
    {
        //single
        if(preg_match($this->single_regex_pattern, $targetstring, $matches)) {
            echo "single DS for $targetstring\n";
            $host = $matches[1];
            $key = $matches[3];
            echo "host: $host\n";
            echo "key: $key\n";

            // make HTTP request
            $url = "http://$host/render?from=-12minutes&target=$key&format=raw";
            //debug("GRAPHITE DS: Connecting to $url");
            echo "GRAPHITE DS: Connecting to $url\n";
            $ch = curl_init($url);
            curl_setopt_array($ch, array(
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 5,
            ));
            $data = curl_exec($ch);
            print "data: $data\n";
            $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);

            if ($status != 200) {
                //debug("GRAPHITE DS: Got HTTP code $status from Graphite");
                echo "GRAPHITE DS: Got HTTP code $status from Graphite\n";
                return;
            }

            # Data in form: devices.network.switch1.if_octets.rx,1425057000,1425057300,300|None

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
                //debug("GRAPHITE DS: No valid data points found");
                echo "GRAPHITE DS: No valid data points found\n";
                return;
            }
            echo "invalue: $value outvalue: $value\n";
            return array($value, $value, time());
        }

        //double
        if(preg_match($this->double_regex_pattern, $targetstring, $matches)) {
            echo "double DS for $targetstring\n";
            $host = $matches[1];
            $inkey = $matches[3];           
            $outkey = $matches[4];

            // make HTTP in request
            $url = "http://$host/render?from=-12minutes&target=$inkey&format=raw";
            //debug("GRAPHITE DS: Connecting to $url");
            echo "GRAPHITE DS: Connecting to $url\n";
            $ch = curl_init($url);
            curl_setopt_array($ch, array(
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 3,
            ));
            $data = curl_exec($ch);
            $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);

            if ($status != 200) {
                //debug("GRAPHITE DS: Got HTTP code $status from Graphite");
                echo "GRAPHITE DS: Got HTTP code $status from Graphite\n";
                return;
            }

            # Data in form: devices.network.switch1.if_octets.rx,1425057000,1425057300,300|None

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
                //debug("GRAPHITE DS: No valid data points found");
                echo "GRAPHITE DS: No valid data points found\n";
                return;
            }
            
            // make HTTP out request
            $url = "http://$host/render?from=-12minutes&target=$outkey&format=raw";
            //debug("GRAPHITE DS: Connecting to $url");
            echo "GRAPHITE DS: Connecting to $url\n";
            $ch = curl_init($url);
            curl_setopt_array($ch, array(
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 3,
            ));
            $data = curl_exec($ch);
            $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);

            if ($status != 200) {
                //debug("GRAPHITE DS: Got HTTP code $status from Graphite");
                echo "GRAPHITE DS: Got HTTP code $status from Graphite\n";
                return;
            }

            # Data in form: devices.network.switch1.if_octets.rx,1425057000,1425057300,300|None

            list($meta, $values) = explode('|', $data, 2);
            $values = explode(',', trim($values));
            
            # get most recent value that is not 'None'
            while(count($values) > 0) {
                $outvalue = array_pop($values);
                if ($outvalue !== 'None') {
                    break;
                }
            }

            if ($outvalue === 'None') {
                // no value found
                //debug("GRAPHITE DS: No valid data points found");
                echo "GRAPHITE DS: No valid data points found\n";
                return;
            }   
            echo "invalue: $value outvalue: $outvalue\n";
            return array($value, $outvalue, time());
        }
        echo "no match\n";
        return false;
    }
}
