<?php


// set database path
$GLOBALS['config_libphono_connection_string'] = 'sqlite3://'.realpath(dirname(__FILE__).'/../resources/Country_Information.sqlite3');

// set to a higher number for more debug output from the classes.
// 0 = no extraneous output
// 1 = some extra output
// 2 = all debugging information
$GLOBALS['config_debug_level_class'] = 0;

?>