# WoT\WN8 1.04
PHP class for processing World of Tanks WN8 stat value. This class require instance of Wargamin\API that can be downloaded from here https://github.com/artur-stepien/wargaming-papi

##Sample usage##
``` php
<?php

// Include API and WN8 class
require_once 'api.php';
require_once 'wn8.php';

// API Instance (here you define on which server player has account)
$api = new \Wargaming\API('demo', \Wargaming\LANGUAGE_ENGLISH, \Wargaming\SERVER_EU);

// Test method to get WN8 of player malkowitch on EU server.
try {
	
	echo (string)(new \Wot\WN8($api, 'malkowitch'));
	
	// Ups we got an error
} catch (Exception $e) {
	
	die($e->getMessage());
	
}
```

##News##
###1.00 - 2015-08-19###
Class published