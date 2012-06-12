<?php
/**
 * 
 * @version 0.5
 * 
 * 
 * Configuration items for all scripts are loaded. The following configuration 
 * items are loaded: debug configuration, scripts and language configuration, 
 * sql configuration.
 * 
**/

date_default_timezone_set(@date_default_timezone_get());

// 
// Object creation and configuration
// 
// Debug objects and Element_Container objects are created.
include_once(dirname(__FILE__)."/config_debug.inc.php");


// 
// Object creation and configuration
// 
// Debug objects and Element_Container objects are created.
include_once(dirname(__FILE__)."/config_script.inc.php");


?>