# Nest2ThingSpeak

This is a tool built to serve the needs of a single user who wants to copy their data to ThingSpeak. It is built to operate both from the command line and through a web server. It can be used as an on-demand data pusher or a near real-time transport daemon.

Hosted on GitHub at https://github.com/ianmaddox/Nest2ThingSpeak

## About
Version: 1.0<br/>
Copyright: 2014, MIT License<br/>
Author: Ian Maddox @isNoOp<br/>

## Config
The config file `config.json` contains three important sections:
* daemon_mode: 0 for one-time execution, 1 for real-time updates
* nest: Credentials obtained from https://developer.nest.com/
* thingspeak: A channel API key can be obtained from https://thingspeak.com/

## Setup
**ThingSpeak Setup**

1. Create an account and a new channel on https://thingspeak.com/
2. Add four fields to the channel in the following order:
 * Temperature
 * Target Temp
 * Humidity
 * Has Leaf
3. Grab your API Key for the config file if you haven't already

**Nest Setup**

1. Go to the Nest developer site at https://developer.nest.com/
2. Sign up and create a new client
3. Grab your AIP Keys for the config file if you haven't already

**CLI Method**

1. Copy `config.json.example` to `config.json` and replace all the placeholder values in the new file
2. Execute `php index.php auth`
3. Go to the URL it provides
4. Follow the prompts until you get your pin
5. Execute `php index.php pin code PIN_CODE_HERE`

**Web Method**

1. Browse to `/index.php?a=auth`
2. Follow the prompts until you get your pin
3. Browse to `/index.php?a=pin&code=PIN_CODE_HERE`

## Usage
Both of the methods described below can be used with a cron job. The on-demand method will send a single set of data on each execution.

Daemon mode monitors a near real-time feed of events directly from Nest. Each incoming event triggers an update to ThingSpeak. You can ensure a daemon is constantly running by executing the script once every few minutes through a cron job. It uses a daemon controller which prevents more than one process from running at a time.

###To run from the command line:###

Execute `php index.php update`

###To run from a web server:###

Browse to `/index.php?a=update`

## History
* 1.0 Initial release

## Todo
* Enable C/F switching in `prefs.json`
* Add better explanations for possible error states
