<?php

/**
 * Nest2ThingSpeak by Ian Maddox
 * @author Ian Maddox @isNoOp
 *
 * Please see README.md for more info
 */
spl_autoload_register(function($class) {
    $file = __DIR__ . '/' . lcfirst($class) . '.class.php';
    require_once($file);
});

$action = get_input('a', true);
if (!file_exists(__DIR__ . '/config.json') || !($json = file_get_contents(__DIR__ . '/config.json'))) {
    die("Cannot find config.json. Exiting.\n");
}
$cfg = json_decode($json);

$stats = new Stats($cfg);

switch ($action) {
    case 'auth':
	$url = $stats->auth();
	if ($url) {
	    if (PHP_SAPI == 'cli') {
		echo "Go to this URL and follow the prompts to get your pin:\n$url\n";
	    } else {
		header("Location: $url");
	    }
	}
	break;
    case 'pin':
	$code = filter_input(INPUT_GET, 'code');
	if(!$code) {
	    echo "ERROR: The pin command requires a value for code!\n";
	    break;
	}
	$stats->pin($code);
	break;
    case 'update':
	$stats->stats();
	break;
    default:
	help();
	break;
}

/**
 * A simple helper that either grabs a value from the querystring or from CLI args.
 *
 * @param string $key
 * @param bool $cliNaked Allows the 1st CLI param to be returned with no associated value.
 * @return string
 */
function get_input($key, $cliNaked = 0) {
    if(PHP_SAPI != 'cli') {
        return filter_input(INPUT_GET, $key);
    }
    $caret = 0;
    if($cliNaked || $caret = array_search($key, $_SERVER['argv'])) {
	return isset($_SERVER['argv'][$caret + 1]) ? $_SERVER['argv'][$caret + 1] : '';
    }
}

function help() {
    if(PHP_SAPI != 'cli') {
        echo "<!DOCTYPE html><html><title>Nest2ThingSpeak Help</title>"
	    . "<xmp theme='united' style='display:none;'>";

    }
    echo file_get_contents('README.md');
    if(PHP_SAPI != 'cli') {
        echo "</xmp>"
	    . "<script src='http://strapdownjs.com/v/0.2/strapdown.js'></script>"
	    . "<noscript><pre>" . file_get_contents('README.md') . "</pre></noscript>"
	    . "<a href=https://github.com/arturadib/strapdown'><img style='position: fixed; top: 0; right: 0; border: 0; z-index: 1000; margin: 0;' src='https://s3.amazonaws.com/github/ribbons/forkme_right_darkblue_121621.png' alt='Fork me on GitHub'></a>"
	    . "<a href='https://github.com/ianmaddox/Nest2ThingSpeak'>Check out Nest2ThingSpeak on GitHub</a>"
	    . "</html>";
    }

}