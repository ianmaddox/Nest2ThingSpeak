<?php

/**
 * A basic Nest(tm) API class.  Supports token based OAuth.
 */
class Nest {
    const OAUTH_AUTH_URL = 'https://home.nest.com/login/oauth2?client_id={CLIENT_ID}&state={STATE}';
    const OAUTH_TOKEN_URL = 'https://api.home.nest.com/oauth2/access_token?client_id={CLIENT_ID}&code={CODE}&client_secret={SECRET}&grant_type=authorization_code';
    const API_URL = 'https://developer-api.nest.com/devices.json?auth={TOKEN}';
    const DAEMON_TIMEOUT = 15;

    private $token;
    private $cfg;

    /**
     * Save the config locally
     * @param stdClass $cfg
     */
    public function __construct($cfg) {
	$this->cfg = $cfg;
    }

    /**
     * Step 1 in OAuth with Nest. The end user must open the URL that is returned to begin the auth process
     * @return string Auth URL
     */
    public function getAuthUrl() {
        return str_replace(['{CLIENT_ID}', '{STATE}'], [$this->cfg->nest->client_id, uniqid()], self::OAUTH_AUTH_URL);
    }

    /**
     * Step 2 in OAuth with Nest. The end user takes the token Nest gave them and provides it here.
     * The return is a JSON string with a token that can be used to access the API.
     * @param string $pin
     * @return string JSON TokenData
     */
    public function getAccessToken($pin) {
        $pin = strtoupper(str_replace(' ', '', $pin));
        $url = str_replace(['{CLIENT_ID}', '{CODE}', '{SECRET}'], [$this->cfg->nest->client_id, urlencode($pin), $this->cfg->nest->client_secret], self::OAUTH_TOKEN_URL);
	$opts = ['http' => ['method'  => 'POST']];
	$context  = stream_context_create($opts);
	return file_get_contents($url, false, $context);
    }

    /**
     * Store the auth token in the object for later use with getData.
     * This method must be called before getData. The token value is obtained from
     * $this->getAccessToken()
     *
     * @param string $token
     */
    public function setToken($token) {
        $this->token = $token;
    }

    /**
     * Continually grab all available data from the Nest API using an event-stream.
     * The callback is passed the JSON data array. Note that the format may differ from the
     * one-shot getData().
     *
     * @param callable $callback
     */
    public function getDataStream(callable $callback) {
	// Create our daemon
	$daemon = new Daemon('nestStats', self::DAEMON_TIMEOUT, 1);
	if(!$daemon->start()) {
	    // There is another process running already
	    $daemon->stop();
	    return;
	}

        $url = str_replace('{TOKEN}', $this->token, self::API_URL);

	// Create an ongoing data stream with Nest
	$opts = ['http' => ['header'  => 'Accept: text/event-stream']];
	$context  = stream_context_create($opts);
	$fp = fopen($url, 'r', false, $context);
	stream_set_blocking($fp, 0);
	$data = '';
	while($daemon->heartbeat()) {
	    // Bump the script timeout out as long as we're not stuck
	    set_time_limit(self::DAEMON_TIMEOUT);
	    $json = trim(fgets($fp));
	    if(substr($json, 0, 5) == 'data:' && $json != 'data: null') {
		$data = json_decode(substr($json, 5), true);
		call_user_func($callback, $data);
	    }
	}
	echo "Daemon stopping...\n";
	$daemon->stop();
    }

    /**
     * A single pull of the API data. Note that Nest may be formatting the output of the event-stream
     * data differently.
     *
     * @return array API data
     */
    public function getData() {
        $url = str_replace('{TOKEN}', $this->token, self::API_URL);
	$json = trim(file_get_contents($url));
	$data = json_decode($json, true);
	return $data;
    }
}
