<?php

/**
 * The Primary controller for the NestStats project. This class pulls together the two component APIs.
 * Most of the public methods here correspond with actions in the front script.
 */
class Stats {
    /* The token file pretends to be PHP because we can't safely move the file out of the docroot
     * but we still want to protect it from casual browsing.
     */
    const TOKEN_FILE = '/.nestToken.php';
    /* @var Nest */
    private $nest;

    /* @var Thingspeak */
    private $thingspeak;
    private $cfg;

    /**
     *
     * @param type $cfg
     */
    public function __construct($cfg) {
	$this->cfg = $cfg;
        $this->nest = new Nest($cfg);
        $this->thingspeak = new Thingspeak($cfg);
    }

    /**
     * Grab the nest authentication URL.
     *
     * @return string
     */
    public function auth() {
        return $this->nest->getAuthUrl();
    }

    /**
     * Turn the PIN given by the Nest OAuth server into a token.
     *
     * @param string $pin
     * @return boolean
     */
    public function pin($pin) {
        $token = $this->nest->getAccessToken($pin);
        if($token) {
	    // Make a nominal PHP script wrapper around the token JSON
            file_put_contents(__DIR__ . self::TOKEN_FILE, "<?php /*\n$token\n*/\n");
            echo "Authorization complete!\n";
            return true;
        }
        echo "Auth failed. Please double check that you are providing a correct pin\n";
        return false;
    }

    /**
     * The stats action grabs data from Nest and passes it to ThingSpeak.
     * Depending on the config value in config.json, this script could either return after one run or run indefinitely
     * in polling mode.
     */
    public function stats() {
        if(!$fp = fopen(__DIR__ . self::TOKEN_FILE, 'r')) {
            echo "Nest API authorization not complete! Please auth first.\n";
            return;
        }

	// Toss the first line of the token file
	fgets($fp);
        $tokenJson = fgets($fp);
	fclose($fp);

        if(empty($tokenJson)) {
            echo "Nest API authorization not complete! Please auth first.\n";
            return;
        }

	$tokenData = json_decode($tokenJson);
        $this->nest->setToken($tokenData->access_token);
	if($this->cfg->daemon_mode) {
            $rawData = $this->nest->getDataStream([$this, 'nestToThingspeak']);
	} else {
	    $this->nestToThingspeak($this->nest->getData());
	}
    }

    /**
     * Filter and copy the data from Nest to Thingspeak.
     * Any new/modified mappings can be made here.
     * Usable as a callback function.
     *
     * @param type $nestData
     */
    public function nestToThingspeak($nestData) {
	// Nest decided to return the data in a different format if you're streaming events.
	$thermo = current($this->cfg->daemon_mode ? $nestData['data']['thermostats'] : $nestData['thermostats']);
        $data = [
            'temp' => $thermo['ambient_temperature_f'],
            'target_temp' => $thermo['target_temperature_f'],
            'humidity' => $thermo['humidity'],
            'has_leaf' => (int)$thermo['has_leaf']
        ];
        $this->thingspeak->sendData($data);

	// Output data
	echo date(DATE_W3C);
	foreach($data as $key => $val) {
	    echo " $key=$val";
	}
	echo PHP_SAPI == 'cli' ? "\n" : '<br/>';
    }
}