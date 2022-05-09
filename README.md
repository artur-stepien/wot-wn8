# World of Tanks PHP WN8 calculation class.
PHP class for processing World of Tanks WN8 stat value. This class uses instance of [Wargaming\Api](https://github.com/artur-stepien/wargaming-papi) and requires to register a Wargaming Developer Application. 

## Sample usage
It is best to use composer manage dependencies.
``` php
<?php

use Wargaming\Language\EN;
use Wargaming\Server\EU;
use Wargaming\Api;
use Wargaming\WoT\WN8;

// Include API and WN8 class
require_once __DIR__.'/vendor/autoload.php';

// NOTICE: Replace YOUR_APPLICATION_ID with your application key.
$language = new EN();
$server = new EU('YOUR_APPLICATION_ID');
$api = new Api($language, $server);

// Test method to get WN8 of player ESL_Gorilla on EU server.
try {

	echo 'Calculated WN8 for player [ESL_Gorilla]: '.(string)(new WN8($api, 'ESL_Gorilla'));

} catch (Exception $e) {

	// Ups we got an error
	die($e->getMessage());

}
```

## Changelog
### 1.3.0 - 2022-05-09
- Changed API version support to >1.4.x, NOTICE: You need to use new api instance so be sure to update your code on update!
- Changed Exceptions to RuntimeException

### 1.2.2 - 2022-02-07
- Updating composer.lock
- Minor code updates.

### 1.2 - 2019-01-06
- Fixed WN8 calculation using expected tank values from modxvm.
- Added test to assure class works as expected.

### 1.1 - 2015-08-20
- Added option to calculate accurate WN8. When this is enabled tanks missing in expected tank values will be removed from account summary. Notice that accurate calculation is from 25% to 35% slower because require stats for each missing tank. 
- Also loading expected tank values has been moved to separate method to allow overriding.

### 1.0 - 2015-08-19
- Class published