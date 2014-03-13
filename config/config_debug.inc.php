<?php
/**
 * 
 * @author Gerhard STEINBEIS ( info [at] tinned-software [.] net )
 * @version 0.8.1
 * 
 * Cteation of all required objects for configuration items storage and 
 * debugging and logging. The debug objects are configured from within this 
 * file.
 * 
**/

// set the timezone to avoid strict warnings
date_default_timezone_set(@date_default_timezone_get());

include_once(dirname(__FILE__).'/../src/classes/debug_logging.class.php');





//
// Define and configure the global Logging, Profiling and Settings Object
//
if(isset($GLOBALS['DBG']) == FALSE)
{
    global $DBG;
    
    //
    // Settings for Debug_Logging (DBG)
    //
    // define DBG object
    $DBG = new Debug_Logging(FALSE, NULL, FALSE); // Test server setting
    
    //$DBG_mysql = new MySQL($GLOBALS['mysql_central'], TRUE);
    //$DBG_mysql_table = 'TSC.Debug_Logging';
    
    // Set logfile options
    $GLOBALS['DBG']->set_field_seperator        ('|');
    $GLOBALS['DBG']->set_time_diff              ('line');
    $GLOBALS['DBG']->set_sessid_filename        (FALSE);
    $GLOBALS['DBG']->set_max_filesize           ('50MB');
    $GLOBALS['DBG']->set_max_filenumber         ('10');
    $GLOBALS['DBG']->set_delete_logfiles        (TRUE);
    
    // Set the logging targets
    $GLOBALS['DBG']->set_log_target             (TRUE, dirname(__FILE__).'/../log/global_log', FALSE);
    


    //$GLOBALS['DBG']->set_log_target_db          ($DBG_mysql, $DBG_mysql_table);
    //$GLOBALS['DBG']->set_log_target_firephp     ($firephp_object);
    
    // Set file filtering options
    //$GLOBALS['DBG']->set_filepath_filter        ('', 'white');
    //$GLOBALS['DBG']->set_filepath_filter        ('', 'black');
    
    // Set loging types
    $GLOBALS['DBG']->set_logging_types          ('info'          , TRUE);
    $GLOBALS['DBG']->set_logging_types          ('debug'         , TRUE);
    $GLOBALS['DBG']->set_logging_types          ('debug_array'   , TRUE);
    $GLOBALS['DBG']->set_logging_types          ('error'         , TRUE);
    $GLOBALS['DBG']->set_logging_types          ('debug2'        , TRUE);
    $GLOBALS['DBG']->set_logging_types          ('debug2_array'  , TRUE);
    $GLOBALS['DBG']->set_logging_types          ('performance'   , TRUE);
    $GLOBALS['DBG']->set_logging_types          ('backtrace'     , TRUE);
    $GLOBALS['DBG']->set_logging_types          ('trace_include' , TRUE);
    $GLOBALS['DBG']->set_logging_types          ('trace_classes' , TRUE);
    $GLOBALS['DBG']->set_logging_types          ('trace_function', TRUE);
    
    // Set log file columns
    $GLOBALS['DBG']->set_logging_fields         ('datetime' , TRUE);
    $GLOBALS['DBG']->set_logging_fields         ('timediff' , TRUE);
    $GLOBALS['DBG']->set_logging_fields         ('sessionid', TRUE);
    $GLOBALS['DBG']->set_logging_fields         ('ip'       , TRUE);
    $GLOBALS['DBG']->set_logging_fields         ('type'     , TRUE);
    $GLOBALS['DBG']->set_logging_fields         ('line'     , TRUE);
    $GLOBALS['DBG']->set_logging_fields         ('file'     , TRUE);
    $GLOBALS['DBG']->set_logging_fields         ('function' , TRUE);
    
    // set option to catch errors and exceptions 
    $GLOBALS['DBG']->set_catch_php_errors          (TRUE);
    $GLOBALS['DBG']->set_catch_unhandled_exceptions(TRUE);
    
    //
    // Settings for Debug_Logging (Global) - END
    //
}
//
// Define and configure the global Logging, Profiling and Settings Object
//




?>
