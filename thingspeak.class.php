<?php

/**
 * A model for writing to ThingSpeak
 */
class Thingspeak {
    const THINGSPEAK_API = 'https://api.thingspeak.com/update?api_key={KEY}&';

    private $cfg;

    /**
     * Store the config object
     *
     * @param stdClass $cfg
     */
    public function __construct($cfg) {
	$this->cfg = $cfg;
    }

    /**
     * Send a single value to ThingSpeak
     *
     * @param string $key
     * @param string $value
     * @return string HTTP response code
     */
    public function sendValue($key, $value) {
        $data = [$key => $value];
        return $this->sendData($data);
    }

    /**
     * Send an array of values to ThingSpeak.
     * The values of the array MUST be in the order of the fields inside TS.
     *
     * @param array $data
     * @return string HTTP response code
     */
    public function sendData(array $data) {
	$data['has_leaf'] = (int)$data['has_leaf'];
	$qs = [];
	$i = 1;
	foreach($data as $junk => $val) {
	    $key = 'field' . ($i++);
	    $qs[$key] = $val;
	}
        $url = str_replace('{KEY}', $this->cfg->thingspeak->api_key, self::THINGSPEAK_API);
        $url .= http_build_query($qs);
        file_get_contents($url);
        return $http_response_header[0];
    }

}