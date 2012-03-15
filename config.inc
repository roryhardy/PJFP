<?php
/**
 * @package Picasa RSS Feed Parser
 * @category Configuration
 * @author Rory Cronin-Hardy (GneatGeek)
 * @link people.oregonstate.edu/~croninhr/
 * @version 1.0
 * @todo Finish Caching System
 */
//\\/\/\/\/\/\/\\\\\\//// CONFIGURATION \\/\/\////\/\/\/\//\\\\/\/\/\/

define("USER",       "PICASA USER-NAME HERE"); # Picasa User
define("MAX_WIDTH",  550); # Maximum Image Width
define("MAX_HEIGHT", 367); # Maximum Image Height
define("ALT_MSG",    "Slide Show Image");
define("CACHE",      FALSE);    # Make a cache file of the parsed Picasa Feed   TO DO!!!
define("CACHE_DIR",  "./cache");

//\\/\/\/\/\/\/\\\\\\// END-CONFIGURATION \\/\/\////\/\/\/\//\\\\/\/\/

# Default Exception Message.
if(!function_exists("exception_handler")){
	function exception_handler($exception) {
	  echo "Uncaught exception: " , $exception->getMessage(), "\n";
	}
}

set_exception_handler('exception_handler');
?>