# WoT\WN8 1.1
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

###1.1 - 2015-08-20###
Added option to calculate accurate WN8. When this is enabled tanks missing in expected tank values will be removed from account summary. Notice that accurate calculation is from 25% to 35% slower because require stats for each missing tank. 
Also loading expected tank values has been moved to seperate method to allow overriding.

###1.00 - 2015-08-19###
Class published