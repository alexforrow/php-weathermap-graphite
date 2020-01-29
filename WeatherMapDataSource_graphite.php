<?php

/**
 * Datasource for Graphite (https://graphite.readthedocs.org)
 *
 * TARGET graphite:graphite_host:metric_in:metric_out
 * Can report single value or two values like if_octets.rx and if_octets.tx
 * Graphite port numbers are also supported, so can use graphite.example.com:8080
 * @example graphite:graphite.example.com:devices.router1.status
 * @example graphite:graphite.example.com:devices.router1.interfaces.eth0.rx:devices.router1.interfaces.eth0.tx
 */
class WeatherMapDataSource_graphite extends WeatherMapDataSource {
    
    /**
     * @var integer The default series step
     */
    protected $step;
    
    /**
     * Called after config has been read (so SETs are processed) but just before ReadData.
     * Used to allow plugins to verify their dependencies (if any) and bow out gracefully.
     *
     * @param $map WeatherMap
     * @return bool Return FALSE to signal that the plugin is not in a fit state to run at the moment.
     */
    function Init(&$map)
    {
        $this->step = $map->get_hint('step') ?: 60;
        
        if(function_exists('curl_init')) return true;
        wm_debug('curl_init() not found. Do you have the PHP cURL module?'.PHP_EOL);
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
     * @return array Returns an array of two values (in,out) and a timestamp
     */
    function ReadData($targetstring, &$map, &$item)
    {
        $step = $item->get_hint('graphite_step') ?: $this->step;
        
        if (preg_match('/^graphite:([^:]+(?::\d+)?):([^:]+)(?::([^:]+))?$/', $targetstring, $matches))
        {
            $host = $matches[1];
            $keys = array_splice($matches, 2, 2);
            $targets = array_filter($keys, function ($key) { return !is_null($key) && '-' !== $key; });
            $targets = array_map(function($key) { return urlencode(urldecode($key)); }, $targets);
            
            // make HTTP request
            $url = sprintf('http://%s/render?format=json&from=-%ds&target=%s', $host, 2*$step, implode('&target=', $targets));
            wm_debug(sprintf('Connecting to %s'.PHP_EOL, $url));
            
            $ch = curl_init($url);
            curl_setopt_array($ch, array(
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 3,
            ));
            $data = curl_exec($ch);
            $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($status != 200)
            {
                wm_warn(sprintf('Graphite API returned HTTP status code %d', $status));
                wm_debug($data.PHP_EOL);
                return;
            }
    
            // process results
            $metrics = json_decode($data);
            if (json_last_error())
            {
                wm_warn('Graphite API returned invalid JSON');
                if (function_exists('json_last_error_msg')) wm_debug(json_last_error_msg());
                wm_debug($data.PHP_EOL);
                return;
            }
            
            $timestamp = null;
            $wm_data = array(IN => null, OUT => null);
            foreach ($metrics as $i => $metric)
            {
                wm_debug(sprintf('Metric "%s" datapoints: %s'.PHP_EOL, $metric->target, json_encode($metric->datapoints)));
                list($value, $timestamp) = static::FindLastValidDatapoint($metric->datapoints);
                if (is_null($value)) continue;
                wm_debug(sprintf('Latest value is %s at %d.'.PHP_EOL, $value, $timestamp));
    
                if (count($metrics) == count($keys))
                {
                    $wm_data[$i] = $value;
                }
                elseif (defined($metric->target))
                {
                    $wm_data[constant($metric->target)] = $value;
                }
                else
                {
                    $wm_data[key($targets)] = $value;
                }
            }
            
            return array_merge($wm_data, array($timestamp));
        }
    }
    
    /**
     * @param array $datapoints
     * @return array last valid datapoint
     */
    protected static function FindLastValidDatapoint(array $datapoints)
    {
        foreach (array_reverse($datapoints) as $datapoint)
        {
            if (!is_null(current($datapoint))) return $datapoint;
        }
    }
}
