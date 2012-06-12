<?php
/**
 * Debug_Logging class file
 * 
 * Class to provide easy access to logging functionality.
 * 
 * @author Gerhard Steinbeis (info [at] tinned-software [dot] net)
 * @copyright Copyright (c) 2008 - 2009, Gerhard Steinbeis
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * 
 * @package framework
 * @subpackage debug
 * 
 * @version 0.24.4.3
 * 
 * @todo Create a method to clone the object
 *       $obj->__clone() ... http://www.php.net/manual/en/language.oop5.cloning.php
 * @todo Add support for multiple white-list and black-list entries.
 * @todo Implement logging to syslogd server (via syslog object provided).
 * @todo Caching log messages should be configured by log type. 
 * @todo logging via email during the destruction. (require log message caching)
 *       error_log("sometext", 1, "email_to@my.domain", "Subject: $email_subject\nFrom: email_from@my.domain\n");
 *       event triggert via error log message (if error sent, mail the last 
 *       and next X log message lines to the email recipient)
 * @todo Add the possibility to add the date and optional the 'am' / 'pm' sign 
 *       to the logfile to be able to indicate the date of the logfile.
 * 
**/

date_default_timezone_set(@date_default_timezone_get());


/**
 *                                   
 * Debug_Logging class to provide easy access to logging functionality.
 * 
 * The Debug_Logging class provides an easy way to store different kind of 
 * logging information into logfiles or a database. It also supports sending 
 * the log information to the browser or into the FirePHP Firefox extension 
 * (http://www.firephp.org/).
 * 
 * The different log messages are seperated by log types. The Debug_Logging a 
 * class provides number of differenttypes such as INFO, DEBUG, ERROR, ... and 
 * so on. Additional there are some spezial types like DEBUG_ARRAY where a 
 * complete array of informations can be logged or PERFORMANCE where time 
 * measurements can be made.
 * 
 * This class supports also the rotation of logfiles. a main feature is the 
 * limit of the logfile to a specific size. When the size of the file exceeds a 
 * configured limit, a new file with a new sequence number is generated. 
 * Additional to this, the Debug_Logging class supports it to keep a 
 * configureable number of logfiles and the posibility to remove/delete all 
 * older logfiles.
 * 
 * As a special feature it is possible to disable and / or filter the messages 
 * before they are written to the log target. It is possible to disable messages 
 * by their type of the filepath they are origin from. This makes it possible 
 * to reduce the amount of logging information in the log file or the database.
 * 
 * The output can also be changed by configuring the fields of the log message 
 * to be stored or ignored. This makes it possible to hide unused fields of the 
 * log messages to save logfile space.
 * 
 * @link http://www.firephp.org/
 * @package framework
 * @subpackage debug
 * 
**/
class Debug_Logging
{
    ////////////////////////////////////////////////////////////////////////////
    // PROPERTIES of the class
    ////////////////////////////////////////////////////////////////////////////
    
    
    
    /**
     * @ignore
     * To enable internal logging. This will send log messages of the class to 
     * the browser. Used to debug the class.
     * @access public
     * @var integer
    **/
    public $dbg_intern                  = 0;
    
    
    /**
     * Set the column length for the time-diff field to a minimum length
     * @access public
     * @var integer
    **/
    public $strlen_diff                 = 7;
    /**
     * Set the column length for the session-id field to a minimum length
     * @access public
     * @var integer
    **/
    public $strlen_sess                 = 27;
    /**
     * Set the column length for the ip-address field to a minimum length
     * @access public
     * @var integer
    **/
    public $strlen_ip                   = 15;
    /**
     * Set the column length for the type field to a minimum length
     * @access public
     * @var integer
    **/
    public $strlen_type                 = 8;
    /**
     * Set the column length for the line-number field to a minimum length
     * @access public
     * @var integer
    **/
    public $strlen_line                 = 5;
    /**
     * Set the column length for the file-name field to a minimum length
     * @access public
     * @var integer
    **/
    public $strlen_file                 = 30;
    /**
     * Set the column length for the function-name field to a minimum length
     * @access public
     * @var integer
    **/
    public $strlen_func                 = 27;
    
    /**
     * Set the browscap class file which is used to parse the browser string if 
     * the php internal function is not available or not configured.
     * @access public
     * @var string
    **/
    public $browscap_class_file         = null;
    
    
    
    // used to set loggin targets for file and browser
    private $tobrowser                  = false;
    private $tofile                     = false;
    
    // used to set external logging objects
    private $tofirephp                  = null;
    private $tosqldb                    = null;
    private $tosqldb_table              = null;
    
    // log filename variables
    private $filenamebase               = null;
    private $filename                   = null;
    private $filename_performance       = null;
    
    // used php session id and remote ip address
    private $sessid                     = "";
    private $remote_ip                  = "";
    
    // set the micro time options
    private $micro_timestamp            = null;
    private $micro_timestamp_perform    = null;
    private $timestamp_line_diff        = false;
    
    // tro write empty lines before first log entry
    private $first_log                   = true;
    
    // max file size in bytes
    private $max_filesize               = 10000000; // about 10MB
    
    // max number of log-files
    private $max_filenumber             = 20; 
    
    // logfiles deletetion allowed?   
    private $delete_logfiles            = false;
    
    // filename setting
    private $sessid_in_filename         = false;
    
    // To enable / disable log types and log message fields (array's)
    private $log_types                  = null;
    private $log_field                  = null;
    private $log_phptypes               = null;
    
    // field seperator
    private $field_seperator            = "|";
    
    // filename and filepath filtering
    private $filepath_whitelist         = null;
    private $filepath_blacklist         = null;
    
    // old error and exception handler
    private $old_error_handle           = null;
    private $old_exception_handle       = null;
    
    // logfile name sufix variable
    private $logfile_suffix             = null;
    
    // cach log messages variables
    private $log_cach                   = array();
    private $log_cach_content           = array();
    
    
    
    
    ////////////////////////////////////////////////////////////////////////////
    // CONSTRUCTOR & DESCTRUCTOR methods of the class
    ////////////////////////////////////////////////////////////////////////////
    
    
    
    /**
     * Constructor for the class
     * 
     * The constructor accepts the basic configuration for the log target file 
     * and browser as well as the configuration for the logfile basename. The 
     * basename will be extended by a sequence number followed by the file 
     * extension ".log".
     *
     * @param bool $to_file Write log messages to the logfile
     * @param string $filenamebase Base of the logfile name containing path and filename without extension.
     * @param bool $to_browser Write log messages to the browser
    **/
    public function __construct($to_file = false, $filenamebase = null, $to_browser = false)
    {
        if($this->dbg_intern >= 1) echo "<pre style='padding: 0px; margin: 0px;'>".get_class($this)."->".__FUNCTION__." - called.<br/></pre>\n";
        
        // set the logfile prefix first
        $this->logfile_suffix["*"] = "";
        $this->logfile_suffix["PERFORM"] = "_perf";
        
        
        // FIX for PHP issue
        // without this line, The if statement below will always behave as 
        // there is no $GLOBALS["_SERVER"] variable. After the count function, 
        // the if statement works as expected
        count($_SERVER);        // Fix for strange PHP behaviour.
        
        
        // initialize logging target
        $this->set_log_target($to_file, $filenamebase, $to_browser);
        
        
        // set the micro seconds to the current time
        if(isset($GLOBALS['_SERVER']['REQUEST_TIME']))
        {
            if($this->dbg_intern >= 1) echo "<pre style='padding: 0px; margin: 0px;'>".get_class($this)."->".__FUNCTION__." - Set initial time to REQUEST time.<br/></pre>\n";
            $this->micro_timestamp = $GLOBALS['_SERVER']['REQUEST_TIME'];
        }
        else
        {
            if($this->dbg_intern >= 1) echo "<pre style='padding: 0px; margin: 0px;'>".get_class($this)."->".__FUNCTION__." - Set initial time to NOW.<br/></pre>\n";
            $this->micro_timestamp = $this->timestamp_msec();
        }
        
        
        // initialize the performance timestamp
        $this->micro_timestamp_perform = $this->micro_timestamp + 3600;
        
        
        // set for behaviour of first log entry
        $this->first_log = true;
        
        
        // set the current session id to the class if available
        $this->sessid = session_id();
        if($this->dbg_intern >= 1) echo "<pre style='padding: 0px; margin: 0px;'>".get_class($this)."->".__FUNCTION__." - Set session-id (".$this->sessid.").<br/></pre>\n";
        
        
        // Set the remote IP address
        if(isset($GLOBALS["_SERVER"]["REMOTE_ADDR"]))
        {
            $this->remote_ip = $GLOBALS["_SERVER"]["REMOTE_ADDR"];
            if($this->dbg_intern >= 1) echo "<pre style='padding: 0px; margin: 0px;'>".get_class($this)."->".__FUNCTION__." - Set remote ip-address (".$this->remote_ip.").<br/></pre>\n";
        }
        
        
        // set default time diff calculation
        if($this->dbg_intern >= 1) echo "<pre style='padding: 0px; margin: 0px;'>".get_class($this)."->".__FUNCTION__." - Set the time diff behaviour to 'script'.<br/></pre>\n";
        $this->set_time_diff("script");
        
        // Enable all types of logging
        if($this->dbg_intern >= 1) echo "<pre style='padding: 0px; margin: 0px;'>".get_class($this)."->".__FUNCTION__." - Enable all logging types.<br/></pre>\n";
        $this->set_logging_types("info"          , true);
        $this->set_logging_types("debug"         , true);
        $this->set_logging_types("debug_array"   , true);
        $this->set_logging_types("error"         , true);
        $this->set_logging_types("debug2"        , true);
        $this->set_logging_types("debug2_array"  , true);
        $this->set_logging_types("performance"   , true);
        $this->set_logging_types("backtrace"     , true);
        $this->set_logging_types("client_info"   , true);
        $this->set_logging_types("php_errors"    , true);
        $this->set_logging_types("unh_exceptions", true);
        
        
        // Enable all fields of logging
        if($this->dbg_intern >= 1) echo "<pre style='padding: 0px; margin: 0px;'>".get_class($this)."->".__FUNCTION__." - Enable all logging fields.<br/></pre>\n";
        $this->set_logging_fields("datetime" , true);
        $this->set_logging_fields("timediff" , true);
        $this->set_logging_fields("sessionid", true);
        $this->set_logging_fields("ip"       , true);
        $this->set_logging_fields("type"     , true);
        $this->set_logging_fields("line"     , true);
        $this->set_logging_fields("file"     , true);
        $this->set_logging_fields("function" , true);
        
        // set the field seperator
        if($this->dbg_intern >= 1) echo "<pre style='padding: 0px; margin: 0px;'>".get_class($this)."->".__FUNCTION__." - Set field seperator to '|'.<br/></pre>\n";
        $this->set_field_seperator("|");
        
        
        // set the default value for the browscap class file
        $this->browscap_class_file = dirname(__FILE__).'/browscap.class.php';
        
    }
    
    
    
    /**
     * Destructor for the class
     * 
     * The descructor will send a last log message to the configured log 
     * targets when the object is destroid. The old error and exception handler will also be restored
    **/
    public function __destruct()
    {
        if($this->dbg_intern >= 1) echo "<pre style='padding: 0px; margin: 0px;'>".get_class($this)."->".__FUNCTION__." - called.<br/></pre>\n";
        if($this->first_log == false)
        {
            if($this->dbg_intern >= 1) echo "<pre style='padding: 0px; margin: 0px;'>".get_class($this)."->".__FUNCTION__." - Send destructor log message (its not the first log message).<br/></pre>\n";
            $this->info("Script terminated. logging object destroyed.");
        }
        
        // check for cached log messages
        foreach($this->log_cach as $type => $cach_object)
        {
            if($cach_object["enabled"] == true)
            {
                // check the target for cached log messages
                switch($cach_object["target"])
                {
                    case "browser":
                        echo "<pre style='padding: 0px; margin: 0px;'>\n";
                        echo join("\n", $this->log_cach_content[$type]);
                        $this->log_cach_content[$type] = null;
                        echo "</pre>\n";
                        break;
                }
            }
        }
        
        
        $this->set_catch_php_errors(false);
        $this->set_catch_unhandled_exceptions(false);
    }
    
    
    
    ////////////////////////////////////////////////////////////////////////////
    // SET methods to set class Options
    ////////////////////////////////////////////////////////////////////////////
    
    
    
    /**
     * Set the log target for the class
     *
     * The set_log_target accepts the basic configuration for the log target 
     * file and browser as well as the configuration for the logfile basename. 
     * The basename will be extended by a sequence number followed by the file 
     * extension ".log". If the filenamebase is a 'php output stream', the log 
     * messages will be sent to this php output stream as they would been sent 
     * to a file.
     * 
     * @link http://www.php.net/manual/en/wrappers.php.php  List of PHP input/output streams
     * @see set_log_target_firephp()
     * @see set_log_target_db()
     * @see get_log_target()
     * 
     * @param bool $to_file Write log messages to the logfile
     * @param string $filenamebase Base of the logfile name containing path and filename without extension.
     * @param bool $to_browser Write log messages to the browser
    **/
    public function set_log_target($to_file = false, $filenamebase = null, $to_browser = false)
    {
        if($this->dbg_intern >= 1) echo "<pre style='padding: 0px; margin: 0px;'>".get_class($this)."->".__FUNCTION__." - called.<br/></pre>\n";
        // check for parameters 
        if( !is_bool($to_file) || !is_bool($to_file) ) return;
        
        
        // set log target
        $this->tobrowser = $to_browser;
        $this->tofile = $to_file;
        
        // if filename if null set a default name
        if($to_file == true && strlen($filenamebase) < 1 && $this->filenamebase != null)
        {
            $filenamebase = dirname(__FILE__)."/../log/GLOBAL_LOG";
            if($this->dbg_intern >= 1) echo "<pre style='padding: 0px; margin: 0px;'>".get_class($this)."->".__FUNCTION__." - Set default logfile-basename (".$filenamebase.").<br/></pre>\n";
        }
        
        
        // set filename
        
        if( $filenamebase != null && preg_match("/php:\/\//", $filenamebase) == false)
        {
            if($this->dbg_intern >= 1) echo "<pre style='padding: 0px; margin: 0px;'>".get_class($this)."->".__FUNCTION__." - Set logging target (".$filenamebase.").<br/></pre>\n";
            $this->filenamebase = $filenamebase;
            $this->filename["*"] = $filenamebase.".0000.log";
            $this->filename["PERFORM"] = $filenamebase."_perf.0000.log";
        }
        else
        {
            if($this->dbg_intern >= 1) echo "<pre style='padding: 0px; margin: 0px;'>".get_class($this)."->".__FUNCTION__." - Set logging for 'php://' target (".$filenamebase.").<br/></pre>\n";
            $this->filenamebase = $filenamebase;
            $this->filename["*"] = $filenamebase;
            //$this->filename["PERFORM"] = $filenamebase;
        }
        
        
        // check the file size
        if($this->filename["*"] != null && preg_match("/php:\/\//", $this->filename["*"]) == false)
        {
            if($this->dbg_intern >= 1) echo "<pre style='padding: 0px; margin: 0px;'>".get_class($this)."->".__FUNCTION__." - Start log file management ...<br/></pre>\n";
            
            // call logfile manager for all defined log types with defined suffix
            foreach($this->logfile_suffix as $type => $suffix)
            {
                $this->manage_logfiles("$type", "$suffix");
            }
            
            if($this->dbg_intern >= 1) echo "<pre style='padding: 0px; margin: 0px;'>".get_class($this)."->".__FUNCTION__." - Start log file management ... Finished<br/></pre>\n";
        }
    }
    
    
    
    /**
     * Set log target for the class firephp
     * 
     * This method will accept a object of the FirePHP type. This object will 
     * be used to send log messages to. Keep in mind that the FirePHP object 
     * will only be anle to sent its log messages as long as the header is not 
     * already sent to the browser. If the header is already sent, this log 
     * target is automatically disabled by the Debug_Logging class. Any other 
     * configured target will remain enabled.
     * 
     * @link http://www.firephp.org/
     * @see set_log_target()
     * @see set_log_target_db()
     * @see get_log_target()
     * 
     * @param object &$to_firephp Write log messages to the FirePHP class (FirePHP Firefox addon)
    **/
    public function set_log_target_firephp(&$to_firephp = null)
    {
        // enable the FirePHP logging
        $this->tofirephp = null;
        if($to_firephp != null && get_class($to_firephp) == "FirePHP")
        {
            $this->tofirephp = $to_firephp;
            $this->tofirephp->setEnabled(true);
        }
    }
    
    
    
    /**
     * Set log target for the class to sql db
     * 
     * This method will accept a sql object. This object must have a method 
     * called 'query' to send the insert statements with the log messages to. 
     * If the 'query method returns an error, this log target is automatically 
     * disabled by the Debug_Logging class. Any other configured target will 
     * remain enabled.
     * 
     * @see set_log_target()
     * @see set_log_target_firephp()
     * @see get_log_target()
     * @see MySQL::query()
     * @see MsSQL::query()
     * 
     * @param object &$to_sqldb Write log messages to a sql database
     * @param string $to_sqldb_table Sql database table
    **/
    public function set_log_target_db(&$to_sqldb = null, $to_sqldb_table = null)
    {
        // enable the SQL logging
        $this->tosqldb = null;
        $this->tosqldb_table = null;
        if($to_sqldb != null && (get_class($to_sqldb) == "MySQL" || get_class($to_sqldb) == "MsSQL" || 
            get_class($to_sqldb) == "SQLite" || get_class($to_sqldb) == "SQLite3" || get_class($to_sqldb) == "SQL") 
            && $to_sqldb_table != null)
        {
            if(method_exists(get_class($to_sqldb), "query") == true)
            {
                $this->tosqldb = $to_sqldb;
                $this->tosqldb_table = $to_sqldb_table;
            }
        }
    }
    
    
    
    
    /**
     * Set catching of php generated error messages
     * 
     * With this method the php internal error handler is overridden. It 
     * defines a class method as the error handler. The old error handler is 
     * saved at the class to be able to restore the previous behaviour. When 
     * catching php errors is enabled, all php genereated error messages which 
     * are possible to catch via the defined error handler will be catched and 
     * logged according to the set_logging_phptypes configuration. The 
     * following errors can be catched: E_WARNING, E_NOTICE, E_STRICT as well 
     * as the user triggert errors E_USER_ERROR, E_USER_WARNING, E_USER_NOTICE.
     * 
     * @see set_logging_phptypes()
     * @see get_catch_php_errors()
     * 
     * @param bool $enablele True To enable catching php generated error messages
    **/
    public function set_catch_php_errors($enable)
    {
        if($enable == true)
        {
            if($this->dbg_intern >= 1) echo "<pre style='padding: 0px; margin: 0px;'>".get_class($this)."->".__FUNCTION__." - Set new php error handler ...<br/></pre>\n";
            //Set this class as error handling function
            $this->old_error_handle = set_error_handler(array (&$this, 'catch_php_error'));
            
            // set the errors to be logged
            $this->log_phptypes[E_ERROR            ] = true;    // Not possible to catch via user defined error handler
            $this->log_phptypes[E_PARSE            ] = true;    // Not possible to catch via user defined error handler
            $this->log_phptypes[E_CORE_ERROR       ] = true;    // Not possible to catch via user defined error handler
            $this->log_phptypes[E_CORE_WARNING     ] = true;    // Not possible to catch via user defined error handler
            $this->log_phptypes[E_COMPILE_ERROR    ] = true;    // Not possible to catch via user defined error handler
            $this->log_phptypes[E_COMPILE_WARNING  ] = true;    // Not possible to catch via user defined error handler
            $this->log_phptypes[0                  ] = true;    // Statement that caused the error was prepended by the @ error-control operator.
            $this->log_phptypes[E_WARNING          ] = true;
            $this->log_phptypes[E_NOTICE           ] = true;
            $this->log_phptypes[E_USER_ERROR       ] = true;
            $this->log_phptypes[E_USER_WARNING     ] = true;
            $this->log_phptypes[E_USER_NOTICE      ] = true;
            $this->log_phptypes[E_STRICT           ] = true;
            
            if($this->dbg_intern >= 1) echo "<pre style='padding: 0px; margin: 0px;'>".get_class($this)."->".__FUNCTION__." - Set new php error handler ... (old=".$this->old_error_handle.").<br/></pre>\n";
        }
        else if($this->old_error_handle != null)
        {
            if($this->dbg_intern >= 1) echo "<pre style='padding: 0px; margin: 0px;'>".get_class($this)."->".__FUNCTION__." - Restore old php error handler.<br/></pre>\n";
            set_error_handler($this->old_error_handle);
            $this->old_error_handle = null;
        }
    }
    
    
    
    /**
     * Set logging of php generated error messages
     * 
     * With this method the catched php errors can be filtered. The different 
     * types of error messages sent by php can be enabled or disabled for 
     * logging to the configured target.
     * 
     * The following errors can be catched: E_WARNING, E_NOTICE, E_STRICT as well 
     * as the user triggert errors E_USER_ERROR, E_USER_WARNING, E_USER_NOTICE.
     * 
     * @see set_catch_php_errors()
     * @see get_logging_phptypes()
     * 
     * @param int $errno the error number. Can be used like E_WARNING or E_NOTICE
     * @param bool $enable True To enable catching php generated error messages
    **/
    public function set_logging_phptypes($errno, $enable)
    {
        if(is_int($errno) && is_bool($enable) )
        {
            $this->log_phptypes[$errno] = $enable;
        }
    }
    
    
    
    /**
     * Set catching of unhandled exceptions
     * 
     * With this method the php internal exception handler is overridden. It 
     * defines a class method as the ecxeption handler. The old exception 
     * handler is saved at the class to be able to restore the previous 
     * behaviour. When catching unhandled exceptions is enabled, all exceptions 
     * not handled by a try-catch block are catched via the defined exception 
     * handler and logged to the configured log target. Please keep in mind 
     * that after the excaption handling is finished the script will be 
     * terminated by php.
     * 
     * @see get_catch_unhandled_exceptions()
     * 
     * @param bool $enable Set to True to enable catching unhandled exceptions
    **/
    public function set_catch_unhandled_exceptions($enable)
    {
        if($enable == true)
        {
            if($this->dbg_intern >= 1) echo "<pre style='padding: 0px; margin: 0px;'>".get_class($this)."->".__FUNCTION__." - Set new php exception handler ...<br/></pre>\n";
            //Set this class as error handling function
            $this->old_exception_handle = set_exception_handler(array (&$this, 'catch_unhandled_exception'));
            if($this->dbg_intern >= 1) echo "<pre style='padding: 0px; margin: 0px;'>".get_class($this)."->".__FUNCTION__." - Set new php exception handler ... (old=".$this->old_exception_handle.").<br/></pre>\n";
        }
        else if($this->old_exception_handle != null)
        {
            if($this->dbg_intern >= 1) echo "<pre style='padding: 0px; margin: 0px;'>".get_class($this)."->".__FUNCTION__." - Restore old php exception handler.<br/></pre>\n";
            set_exception_handler($this->old_exception_handle);
            $this->old_exception_handle = null;
        }
    }
    
    
    
    
    /**
     * Set if the session id should be part of logfile name
     * 
     * This method enables the session-id based logfiles. When this option is 
     * enabled, the filename will be extended with the session-id. This helps 
     * debugging because one file contains log entries of one client only. All 
     * log file rotation and log file deletion applies also to the files with 
     * session-id. Enabling this option on productive installation can cause a 
     * huge amount of logfiles which is not recomended.
     * 
     * @see get_sessid_filename()
     * 
     * @param bool $enable Define if this option should be enabled
    **/
    public function set_sessid_filename($enable)
    {
        if(is_bool($enable))
        {
            $this->sessid_in_filename = $enable;
        }
        
        
        // call logfile manager for all defined log types with defined suffix
        foreach($this->logfile_suffix as $type => $suffix)
        {
            if($this->dbg_intern >= 1) echo "<pre style='padding: 0px; margin: 0px;'>".get_class($this)."->".__FUNCTION__." - Call logfile manager for ... type='$type', suffix='$suffix'.<br/></pre>\n";
            $this->manage_logfiles("$type", "$suffix");
        }
        
    }
    
    
    
    /**
     * Set enabled types for logging
     * 
     * This method provides the basic filter functionality. Each type of 
     * messages can be enabled or disabled independent from all other types. By
     * default, all types are enabled. When a type is disabled, all log 
     * messages of this type will be discarded. 
     * 
     * The following types can be disabled via this method: 
     * info, debug, debug_array, error, debug2, debug2_array, performance, 
     * backtrace, client_info, php_errors, unh_exceptions
     * 
     * @see get_logging_types()
     * 
     * @param string $type Define the type of log message
     * @param bool $enabled enable (true) or disable (false) logging for this type of message.
    **/
    public function set_logging_types($type, $enabled=true)
    {
        if(is_bool($enabled))
        {
            // Enable logging types
            $this->log_types[strtolower($type)] = $enabled;
        }
    }
    
    
    
    /**
     * Set enabled log message fields for logging
     *
     * This method enables or disables specified fields from the log message. 
     * Per default all fields of the log message are enabled. All fields except 
     * the message-text can be disabled. Notice that the sctructure of the 
     * logfile changes when fields are enabled or disabled during runtime. If 
     * you use a log file viewer, this can lead into parsing or presentation 
     * problems. It is recommended to enable or disable the fields before a log 
     * entry is written and not to change them during runtime to keep the 
     * logfiles in consistant format. 
     * 
     * The following fields can be disabled via this method: 
     * datetime, timediff, sessionid, ip, type, line, file, function
     *
     * @see get_logging_fields()
     * 
     * @param string $field Define field of the log message
     * @param bool $enabled enable (true) or disable (false) logging for this log field
    **/
    public function set_logging_fields($field, $enabled=true)
    {
        if(is_bool($enabled))
        {
            // set the field  to be logged or not.
            if($field == "datetime" ) $this->log_field["datetime" ] = $enabled;
            if($field == "timediff" ) $this->log_field["timediff" ] = $enabled;
            if($field == "sessionid") $this->log_field["sessionid"] = $enabled;
            if($field == "ip"       ) $this->log_field["ip"       ] = $enabled;
            if($field == "type"     ) $this->log_field["type"     ] = $enabled;
            if($field == "line"     ) $this->log_field["line"     ] = $enabled;
            if($field == "file"     ) $this->log_field["file"     ] = $enabled;
            if($field == "function" ) $this->log_field["function" ] = $enabled;
        }
    }
    
    
    
    /**
     * Set time diff behaviour
     *
     * This method defines the behaviour of the timediff (time difference) 
     * column. The behaviour can be set to 'line' or 'script'. By default the 
     * behaviour is set to 'script'. If this option is set to 'script', the 
     * time difference is calculated from the first debug line or, if 
     * available, the request time. When this option is set to 'line, the time 
     * difference is calculated from one logging line to the next.
     * 
     * @see get_time_diff()
     * 
     * @param string $option Define logging behaviour for time diff
    **/
    public function set_time_diff($option)
    {
        // set time-difference behaviour
        if($option == "line"  ) $this->timestamp_line_diff = true;
        if($option == "script") $this->timestamp_line_diff = false;
        
    }
    
    
    
    /**
     * Set the seperating characters for the log fields
     * 
     * This method sets the seperating character for the log message fields. By 
     * default this character is set to '|'. This character can be changed to 
     * ';' (to be able to handle the logfiles like csv files) or to any other 
     * character.
     * 
     * @see get_field_seperator()
     * 
     * @param string $seperator The seperating character(s) for the log fields
    **/
    public function set_field_seperator($seperator)
    {
        // set the field seperator
        $this->field_seperator = $seperator;
        
    }
    
    
    
    /**
     * Set the maximum file size
     * 
     * This method sets the filesize per log file. The logfile size is not an 
     * exact size. The logfile size is checked during initialisation and during 
     * change of the log target. If the file size exceeds during runtime, the 
     * log messages will not go to another logfile. This is to keep all log 
     * messages of a script run together in one log file and makes it easier to 
     * read. The size can be specified in bytes, kilobytes and megabytes. To 
     * specify a size in bytes, simply provide the number of bytes. To specify 
     * megabyte or kilobyte size provide the number and the 'MB' for megabyte 
     * or 'KB' for kilobyte.
     * 
     * @see get_max_filesize()
     * 
     * @param string $size defines the maximum size of the log files
    **/
    public function set_max_filesize($size)
    {
        // Check for size given in KB
        if(preg_match('/^\d+KB$/', $size) == true)
        {
            $this->max_filesize = $size * 1000;
        }
        // Check for size given in MB
        if(preg_match('/^\d+MB$/', $size) == true)
        {
            $this->max_filesize = $size * 1000000;
        }
        // check for size given in bytes
        else if(preg_match('/^\d+$/', $size) == true)
        {
            $this->max_filesize = $size;
        }
        if($this->dbg_intern >= 1) echo "<pre style='padding: 0px; margin: 0px;'>".get_class($this)."->".__FUNCTION__." - set maximum filesize to ".$this->max_filesize."<br/></pre>\n";
        
        // call logfile manager for all defined log types with defined suffix
        foreach($this->logfile_suffix as $type => $suffix)
        {
            $this->manage_logfiles("$type", "$suffix");
        }
        
        return;
    }
    
    
    
    /**
     * Set the maximum number of files
     * 
     * This method sets the maxumum number of logfiles to keep. This setting 
     * only takes effet when the functionality to deleted old logfiles are. By 
     * default the option to delete logfiles is disabled. When the option to 
     * delete logfiles is enabled, this option defines the number of old 
     * logfiles to keep. Notice: In the moment of rotating to the next logfile, 
     * it could happen thet there will one file more then configured.
     * 
     * @see set_delete_logfiles()
     * @see get_max_filenumber()
     * 
     * @param int $amount number of logfiles allowed
    **/
    public function set_max_filenumber($amount)
    {
        if(is_int($amount))
        {
            $this->max_filenumber = $amount;
        }
    }
    
    
    
    /**
     * Set to delete old log files
     * 
     * This method enable the functionality to delete old logfiles. If the 
     * number of logfiles exceeds the configured number of files, they will be 
     * deleted. The files will be sorted by there file date to get the oldest 
     * files deleted.
     * 
     * ATTENTION: This method will immidiatly trigger the deletion of logfiles. 
     * It is recomended to set the maximum number of files to keep first.
     * 
     * 
     * @see set_max_filenumber()
     * @see get_delete_logfiles()
     * 
     * @param bool $enabled Set to true to enable deletion of logfiles
    **/
    public function set_delete_logfiles($enabled=true)
    {
        if(is_bool($enabled))
        {
            $this->delete_logfiles = $enabled;
        }
        
        // call the log file management to apply the changed settings
        // call logfile manager for all defined log types with defined suffix
        foreach($this->logfile_suffix as $type => $suffix)
        {
            $this->manage_logfiles("$type", "$suffix");
        }
        
    }
    
    
    
    /**
     * Set filepath for black- or white-list
     * 
     * This method allows to define a regex for filtering log messages. It is 
     * possible to define one regular expression for black-list and one regex 
     * for white-list. The regex will be applied to the full file- and pathname 
     * of the log message origin file. The blacklist will avoid logging the log 
     * message and the white-list allows to define a exception from the 
     * black-list.
     * 
     * @see get_filepath_filter()
     * @link http://www.php.net/manual/en/book.pcre.php Regular Expressions (Perl-Compatible)
     * 
     * @param string $path_regex regular-expression for the origin filepath of the log message
     * @param string $list name of the list ("white", "black") for the file / path
    **/
    public function set_filepath_filter($path_regex, $list)
    {
        // set the whitelist filter
        if($list == "white")
        {
            $this->filepath_whitelist = $path_regex;
            return true;
        }
        // set the blacklist filter
        else if($list == "black")
        {
            $this->filepath_blacklist = $path_regex;
            return true;
        }
        
        return false;
    }
    
    
    
    /**
     * Set logfile name suffix
     * 
     * This method allows to define a logfile name sufix based on the log type. 
     * If a new fogfile suffix is set to a log type, the logfile manager 
     * applies the file numbering and triggers also the deletion of old 
     * logfiles if enabled. The suffix is appended to the basename before the 
     * file numbering.
     * 
     * @see get_logfile_suffix()
     * 
     * @param string $type The log type to set the logfile name suffix for
     * @param string $suffix The logfile name suffix to append to the logfile name
    **/
    public function set_logfile_suffix($type, $suffix)
    {
        if(preg_match('/^[\d\w\_\-\.]*$/', $suffix) == true)
        {
            // set the sufix to the class
            $this->logfile_suffix[$type] = $suffix;
            
            // call logfile manager to get correct file number
            $this->filename[$type] = $this->filenamebase."$suffix.0000.log";
            $this->manage_logfiles("$type", "$suffix");
            
            return true;
        }
        
        return false;
    }
    
    
    
    /**
     * Set log message caching
     * 
     * This method allows to enable caching of log messages. If this option is 
     * enabled, every log message is stored within the class. The log messages 
     * can be send during destruction to the log target defined. If the log 
     * message caching is turned off, the cached log messages will be deleted 
     * to free up memory. For the definition of the target, the following 
     * values are available: browser
     * 
     * @see get_logcach()
     * 
     * @param string $target Define the target where the log messages should be sent to
     * @param string $enabled Set to true to enable log message caching
    **/
    public function set_logcach($type, $enabled, $target=null)
    {
        if(is_bool($enabled) == true)
        {
            // disable the caching
            if($enabled == false)
            {
                $this->log_cach[$type]["target"]  = false;
                $this->log_cach[$type]["enabled"] = false;
                $this->log_cach_content[$type]    = null;     // delete cached log messages
            }
            
            // enable the chaching
            if($enabled == true)
            {
                $this->log_cach[$type]["target"]  = $target;
                $this->log_cach[$type]["enabled"] = $enabled;
                $this->log_cach_content[$type]    = array();
                //$this->info("Enabled log message caching of type '$type' with target '$target'.", 1);
            }
        }
    }
    
    /**
     * Set log message session ID
     * 
     * This method is used to set the session ID for the log messages. The
     * session ID appears in a separate field in the log line.
     * 
     * @see get_session_id()
     * 
     * @param string $session_id The session ID as it appears in the log message
    **/
    public function set_session_id($session_id = FALSE)
    {
        if($session_id == FALSE)
        {
            return FALSE;
        }
        
        // set the session id to the class
        $this->sessid = $session_id;
        
        // start the set_log_target method to handle changed 
        // session-id in the logfile name
        $this->set_log_target($this->tofile, $this->filenamebase, $this->tobrowser);
        
    }
    
    
    
    ////////////////////////////////////////////////////////////////////////////
    // GET methods to get class Options
    ////////////////////////////////////////////////////////////////////////////
    
    
    
    /**
     * Get log target configuration
     * 
     * This method allows to get the current configured log target(s). 
     * Depending on the target, it returns if this target is enabled or 
     * disabled. In case of a non boolean configuration like the file-basename, 
     * it returns the current configuration.
     * 
     * The available targets to get the configuration for are: file, browser, 
     * basename, firephp, sqldb, sqldb_table
     * 
     * @see set_log_target()
     * @see set_log_target_firephp()
     * @see set_log_target_db()
     * 
     * @param string $target To check for a specific target
     * @return mixed Enable (true), disable (false) or the configuration element.
    **/
    public function get_log_target($target = "file")
    {
        $target = strtolower($target);
        switch($target)
        {
            case "file":
                return $this->tofile;
                break;
            case "browser":
                return $this->tobrowser;
                break;
            case "basename":
                return $this->filenamebase;
                break;
            case "firephp":
                return $this->tofirephp;
                break;
            case "sqlodb":
                return $this->tosqldb;
                break;
            case "sqldb_table":
                return $this->tosqldb_table;
                break;
        }
        return false;
    }
    
    
    
    /**
     * Get catching of php generated error messages
     * 
     * This method returns the state if the catching of php internal errors 
     * is enabled. When the catching of php errors is enabled true is returned 
     * false otherwhise.
     * 
     * @see set_catch_php_errors()
     * 
     * @return bool $enablele True if catching php error messages is enabled
    **/
    public function get_catch_php_errors()
    {
        // checlk if a old error handler is set
        if($this->old_error_handle != null)
        {
            return true;
        }
        
        return false;
    }
    
    
    
    /**
     * Get the state of the php error logging
     * 
     * This method returns the configured state of the php error logging for a 
     * specified error type. This error types can be specified using the 
     * predefined php error constants.
     * 
     * The following errors can be catched: E_WARNING, E_NOTICE, E_STRICT as well 
     * as the user triggert errors E_USER_ERROR, E_USER_WARNING, E_USER_NOTICE.
     * 
     * @see set_catch_php_errors()
     * @see set_logging_phptypes()
     * 
     * @param int $errno the error number. Can be used like E_WARNING or E_NOTICE
     * @return bool Enable (true) or disable (false) logging for this type of error.
    **/
    public function get_logging_phptypes($errno)
    {
        if(is_int($errno) && isset($this->log_phptypes[$errno]) == true )
        {
            return $this->log_phptypes[$errno];
        }
    }
    
    
    
    /**
     * Get catching of unhandled exceptions
     * 
     * This method returns the state if the catching of unhandled exceptions
     * is enabled. When the catching of unhandled exceptions is enabled true is 
     * returned false otherwhise.
     * 
     * @see set_catch_unhandled_exceptions()
     * 
     * @return bool $enablele True if catching php error messages is enabled
    **/
    public function get_catch_unhandled_exceptions()
    {
        // checlk if a old error handler is set
        if($this->old_exception_handle != null)
        {
            return true;
        }
        
        return false;
    }
    
    
    
     /**
     * Get the status if session-id is part of thge logfiles
     * 
     * This method returns the state of the configuration for the session-id. 
     * This option adds the session-id to the logfile name. When it is enabled, 
     * the method returns true and false otherwhise.
     * 
     * @see set_sessid_filename()
     * 
     * @return bool Enable (true) or disable (false) logging for this type of message.
    **/
    public function get_sessid_filename()
    {
        return $this->sessid_in_filename;
    }
    
    
    
    /**
     * Get status of field 
     *
     * Get the current configured status for log message types. It will return 
     * true if the requested type is enabled and false otherwhise.
     * 
     * @see set_logging_types()
     * 
     * @param string $type Define the type of log message
     * @return bool Enable (true) or disable (false) logging for this type of message.
    **/
    public function get_logging_types($type)
    {
        $status = null;
        
        // Enable logging types
        if(isset($this->log_types[strtolower($type)]) == true) 
        {
            $status = $this->log_types[strtolower($type)];
        }
        
        return $status;
        
    }
    
    
    
    /**
     * Get status of field 
     *
     * Get the current configured status for log message fields. It will return 
     * true if the requested field is enabled and false otherwhise.
     * 
     * @see set_logging_fields()
     * 
     * @param string $field Define field of the log message
     * @return bool Enable (true) or disable (false) logging for this log field
    **/
    public function get_logging_fields($field)
    {
        $status = null;
        
        // set the field  to be logged or not.
        if($field == "datetime" ) $status = $this->log_field["datetime" ];
        if($field == "timediff" ) $status = $this->log_field["timediff" ];
        if($field == "sessionid") $status = $this->log_field["sessionid"];
        if($field == "ip"       ) $status = $this->log_field["ip"       ];
        if($field == "type"     ) $status = $this->log_field["type"     ];
        if($field == "line"     ) $status = $this->log_field["line"     ];
        if($field == "file"     ) $status = $this->log_field["file"     ];
        if($field == "function" ) $status = $this->log_field["function" ];
        
        return $status;
    }
    
    
    
    /**
     * Get time diff behaviour
     * 
     * This method returns the status of the diff behaviour. It returns the 
     * configured string 'line' or 'script'.
     * 
     * @see set_time_diff()
     * 
     * @return string Returns the string configured for the time-diff behaviour
    **/
    public function get_time_diff()
    {
        // standart types
        if($this->timestamp_line_diff == true) return "line";
        if($this->timestamp_line_diff == false) return "script";
        
    }
    
    
    
    /**
     * Get the seperating characters for the log fields
     * 
     * This method returns the configured field seperator used to seperate the 
     * log message fields.
     * 
     * @see set_field_seperator()
     * 
     * @return bool The seperating character(s) for the log fields
    **/
    public function get_field_seperator()
    {
        // get the field seperator
        return $this->field_seperator;
        
    }
    
    
    
    /**
     * Get the maximum file size
     * 
     * This method returns the configured maximum file size. The returns size 
     * is always in bytes.
     * 
     * @see set_max_filesize()
     * 
     * @return int The maximum file size in bytes
    **/
    public function get_max_filesize()
    {
        // get the field seperator
        return $this->max_filesize;
        
    }
    
    
    
    /**
     * Get the maximum number of files
     *
     * This method returns the configured number of files to keep. The number 
     * of files takes only effect when the functionality to deleted old 
     * logfiles are.
     * 
     * @see set_max_filenumber()
     * @see set_delete_logfiles()
     * 
     * @return int The maximum number of logfiles allowed
    **/
    public function get_max_filenumber()
    {
        return $this->max_filenumber;
    }
    
    
    
    /**
     * Get the state of delete old log files
     * 
     * This method returns the status of the functionality to delete old 
     * logfiles. If this functionality is enabled true is returned false 
     * otherwhise.
     * 
     * @see set_delete_logfiles()
     * 
     * @return bool True, if deletion of logfiles is allowed
    **/
    public function get_delete_logfiles()
    {
        return $this->delete_logfiles;
    }
    
    
    
    /**
     * Get filepath gerular-expression for black- or white-list
     *
     * This method returns the configured regex of the filepath filter. The 
     * configured regex string of the provided list is returned. Lists 
     * available are 'white' and 'black'.
     * 
     * @see get_filepath_filter()
     * 
     * @param $list name of the list ("white", "black") for the file / path
     * @return string The regular-expression for the origin filepath of the log message
    **/
    public function get_filepath_filter($list)
    {
        if($list == "white")
        {
            return $this->filepath_whitelist;
        }
        else if($list == "black")
        {
            return $this->filepath_blacklist;
        }
        
        return null;
    }
    
    
    
    /**
     * Get logfile name suffix
     * 
     * This method allows to get the define logfile name sufix based on the 
     * log type. If no suffix is set, a empty string is returned.
     * 
     * @see set_logfile_suffix()
     * 
     * @param string $type The log type to get the logfile name suffix for
     * @return string The configured sufix for this log message type
    **/
    public function get_logfile_suffix($type)
    {
        if(isset($this->logfile_suffix[$type]) == false)
        {
            return "";
        }
        return $this->logfile_suffix[$type];
    }
    
    
    
    /**
     * Get log message caching
     * 
     * This method returnes the log message cach setting of the class. It will 
     * return the onfigured target if enabled or false if disabled. The cached 
     * log messages are returned from get_log_cach_content().
     * 
     * @see set_logcach()
     * @see get_logcach_content()
     * 
     * @param string $type The type of the logmessages to be cached
     * @return string $target The target or false if disabled.
    **/
    public function get_logcach($type)
    {
        if(isset($this->log_cach[$type]) == true)
        {
            return $this->log_cach[$type]["target"]  = false;
        }
        return false;
    }
    
    
    
    /**
     * Get log message cach content
     * 
     * This method allows to enable caching of log messages. If this option is 
     * enabled, every log message is stored within the class. The log messages 
     * can be send during destruction to the log target defined. For the 
     * definition of the target, the following values are available: browser
     * 
     * @see set_logcach()
     * 
     * @param string $type The type of the logmessages to be cached
     * @return mixed $target The cach settings as array with 'target', 'enabled' and 'count'
    **/
    public function get_logcach_content($type)
    {
        // return the cached log messages
        if(isset($this->log_cach[$type]) == true)
        {
            return $this->log_cach_content[$type];
        }
        
        // get all types
        if($type == "*")
        {
            $result_array = array();
            foreach($this->log_cach_content as $cached)
            {
                $result_array = array_merge($result_array, $cached);
            }
            return $result_array;
        }
        
        
        return null;
    }
    
    /**
     * Get log message session ID
     * 
     * This method is used to get the session ID for the log messages. The
     * session ID appears in a separate field in the log line.
     * 
     * @see set_session_id()
     * 
     * @return string The session ID as it appears in the log message
    **/
    public function get_session_id()
    {
        return $this->sessid;
    }
    
    
    
    ////////////////////////////////////////////////////////////////////////////
    // PRIVATE methods of the class
    ////////////////////////////////////////////////////////////////////////////
    
    
    
    /**
     * Reset micro timerstamp for timediff calculation
     * 
     * This method is used to get the micro timestamp used for the timediff
     * calcultion. The timestamp is returned as float.
     * 
     * @return float Current time including microseconds
    **/
    private function timestamp_msec()
    {
        // get the microtime in format "0.######## ##########"
        list($usec, $sec) = explode(" ",microtime());
        // create the micro timestamp
        $micro_ts = ((float)$usec + (float)$sec); 
        
        return $micro_ts;
    }
    
    
    
    /**
     * Manage the logfiles functionality
     * 
     * This method is handling all the logfile related issues. The list of 
     * files is gattered to check for the last file index. Then this method 
     * checks the log file size if it has exceeded the configured maximum 
     * filesize to rotate to the next fileindex. The rotation to the next 
     * logfile as well as the initialisation to delete old logfiles is handled 
     * by this method. The file deletion itself is done by a seperate method.
     * 
     * @see set_max_filesize()
     * @see set_max_filenumber()
     * @see set_delete_logfiles()
     * @see remove_logfiles()
     * 
     * @param string $perf_suffix the suffix for the performance logfile
    **/
    private function manage_logfiles($type="*", $suffix="")
    {
        //
        // Get list of files and sort them by date
        //
        if(preg_match("/php:\/\//", $this->filenamebase) > 0)
        {
            if($this->dbg_intern >= 1) echo "<pre style='padding: 0px; margin: 0px;'>".get_class($this)."->".__FUNCTION__." - filename base is php:// skip functionality<br/></pre>\n";
            return;
        }
        // get alle the log-filenames in the directory
        if($this->sessid_in_filename == true)
        {
            // if session-id in filename is enabled
            $filelist = glob ($this->filenamebase."_".$this->sessid."$suffix.*.log");
            if($this->dbg_intern >= 1) echo "<pre style='padding: 0px; margin: 0px;'>".get_class($this)."->".__FUNCTION__." - Get the list of files (with session-id) - Count=".count($filelist)."<br/></pre>\n";
            if($filelist == false)
            {
                $filelist = array();
            }
        }
        else
        {
            $filelist = glob ($this->filenamebase."$suffix.*.log");
            if($this->dbg_intern >= 1) echo "<pre style='padding: 0px; margin: 0px;'>".get_class($this)."->".__FUNCTION__." - Get the list of files (without session-id) - Count=".count($filelist)."<br/></pre>\n";
            if($filelist == false)
            {
                $filelist = array();
            }
        }
        
        // define result array
        $files = array();
        if($this->dbg_intern >= 2) echo "<pre style='padding: 0px; margin: 0px;'>".get_class($this)."->".__FUNCTION__." - Files from glob: ".print_r($filelist, true)."<br></pre>\n";
        
        
        // get the filedate and time of all files
        foreach($filelist as $filename)
        {
            
            // get the timestamp of last modification for each file
            $last_modified = filemtime($filename);
            if($this->dbg_intern >= 2) echo "<pre style='padding: 0px; margin: 0px;'>".get_class($this)."->".__FUNCTION__." - Filedate for file '$filename' ... $last_modified<br/></pre>\n";
            
            // write it into the array for the following sort
            $files["$filename"] = $last_modified;
        }
        // sort the array with date, newest will be last entry
        asort ($files);
        
        // get the keys of the array, because we need the filenames
        unset($filelist);
        $filelist = array_keys($files);
        if($this->dbg_intern >= 2) echo "<pre style='padding: 0px; margin: 0px;'>".get_class($this)."->".__FUNCTION__." - Files from glob sorted: ".print_r($filelist, true)."</br></pre>\n";
        
        //
        // Get list of files and sort them by date - END
        //
        
        
        
        //
        // Get number of last (newest) file
        //
        
        // get the last (newest) filename
        $last_filename = end($filelist);
        if($this->dbg_intern >= 1) echo "<pre style='padding: 0px; margin: 0px;'>".get_class($this)."->".__FUNCTION__." - Get the last file name '".$last_filename."'.<br/></pre>\n";
        
        // get the number out of the filename
        if($this->sessid_in_filename == true)
        {
            // if session-id in filename is enabled
            list($fnumber) = sscanf($last_filename, $this->filenamebase."_".$this->sessid."$suffix.%d.log");
        }
        else
        {
            list($fnumber) = sscanf($last_filename, $this->filenamebase."$suffix.%d.log");
        }
        if($this->dbg_intern >= 1) echo "<pre style='padding: 0px; margin: 0px;'>".get_class($this)."->".__FUNCTION__." - Get the last file number '".$fnumber."'.<br/></pre>\n";
        //
        // Get number of last (youngest) file - END
        //
        
        
        
        // actually, this will begin at the newest file, and the loop
        do
        {
            // if session-id in filename is enabled
            if($this->sessid_in_filename == true)
            {
                $tmp_filename = $this->filenamebase."_".$this->sessid."$suffix.".substr(sprintf ("%04d", $fnumber), -4).".log";
            }
            else
            {
                $tmp_filename = $this->filenamebase."$suffix.".substr(sprintf ("%04d", $fnumber), -4).".log";
            }
            // if the file does not exist, file size is ok anyway
            // this was programmed due to the problem with the hardcoded 0000 file (see above)
            $finished = false;
            if( (file_exists($tmp_filename) && filesize($tmp_filename) < $this->max_filesize) || file_exists($tmp_filename) == false)
            {
                $finished = true;
            }
            else
            {
                $fnumber++;
            }
        } while($finished == false);
        
        // assign new filename to the class
        if($this->dbg_intern >= 1) echo "<pre style='padding: 0px; margin: 0px;'>".get_class($this)."->".__FUNCTION__." - Set current log file name (type=$type) to '".$this->filename[$type]."'.<br/></pre>\n";
        $this->filename[$type] = $tmp_filename;
        
        
        
        if($this->delete_logfiles == true)
        {
            if($this->dbg_intern >= 1) echo "<pre style='padding: 0px; margin: 0px;'>".get_class($this)."->".__FUNCTION__." - Call method to delete files.<br/></pre>\n";
            $this->remove_logfiles($filelist, $suffix);
        }
        
    }
    
    
    
    
    /**
     * Delete logfiles older than x numbers
     * 
     * This method will be called from the manage_logfiles() method to delete 
     * old logfiles if configured. The list of logfiles will be searched for 
     * logfiles to delete according to the configured maximum number of 
     * logfiles to keep.
     * 
     * @see set_max_filenumber()
     * @see set_delete_logfiles()
     * @see manage_logfiles()
     * 
     * @param array $filelist  The list of all logfiles found for this basename
     * @param string $perf_suffix The filename suffix used for this basename
     **/
    private function remove_logfiles($filelist, $perf_suffix="")
    {
        if($this->delete_logfiles == false)
        {
            // if delete old logfiles is not enabled return without taking any action
            return;
        }
        
        if($this->max_filenumber > count($filelist))
        {
            if($this->dbg_intern >= 1) echo "<pre style='padding: 0px; margin: 0px;'>".get_class($this)."->".__FUNCTION__." - No files to delete ... return<br/></pre>\n";
            return;
        }
        
        // create filepattern for finding files
        if($this->sessid_in_filename == true)
        {
            $filepattern = $this->filenamebase."_".$this->sessid."$perf_suffix.%d.log";
        }
        else
        {
            $filepattern = $this->filenamebase."$perf_suffix.%d.log";
        }
        
        if($this->dbg_intern >= 1) echo "<pre style='padding: 0px; margin: 0px;'>".get_class($this)."->".__FUNCTION__." - Filepattern = $filepattern<br/></pre>\n";
        
        
        
        for($i=count($filelist)-$this->max_filenumber-1; $i >= 0; $i--)
        {
            // delete the file x
            if($this->dbg_intern >= 1) echo "<pre style='padding: 0px; margin: 0px;'>".get_class($this)."->".__FUNCTION__." - Delete file = ".$filelist[$i]."<br/></pre>\n";
            unlink($filelist[$i]);
        }
        
        
        
    }
    
    
    
    
    /**
     * Check if filename is enabled for logging
     * 
     * This method checks the complete filepath with the black-list and 
     * white-list regex and returns the result as bool. If the messages from 
     * should be logged, true is returned false otherwhise.
     * 
     * @see set_filepath_filter()
     * 
     * @param string $filename filename and file path to check
     * @return bool If logging is enabled for this filepath, true is returned
    **/
    private function allow_logging_filepath($filename)
    {
        if(count($this->filepath_whitelist) < 1 && count($this->filepath_blacklist) < 1 )
        {
            // there are no list entries - return OK for logging
            if($this->dbg_intern >= 1) echo "<pre style='padding: 0px; margin: 0px;'>".get_class($this)."->".__FUNCTION__." - File ".basename($filename)." no whitelisted / blacklist defined - return OK<br></pre>\n";
            return true;
        }
        
        
        // check for whitelistedfile or path name
        //foreach($this->file_whitelist as $white_fileregex)
        if($this->filepath_whitelist != null)
        {
            $white_regex = $this->filepath_whitelist;
            if(preg_match($white_regex, $filename))
            {
                if($this->dbg_intern >= 1) echo "<pre style='padding: 0px; margin: 0px;'>".get_class($this)."->".__FUNCTION__." - File ".basename($filename)." is whitelisted - return OK<br></pre>\n";
                return true;
            }
        }
        
        
        // check for blacklisted filename or path
        //foreach($this->file_blacklist as $black_fileregex)
        if($this->filepath_blacklist != null)
        {
            $black_regex = $this->filepath_blacklist;
            if(preg_match($black_regex, $filename))
            {
                if($this->dbg_intern >= 1) echo "<pre style='padding: 0px; margin: 0px;'>".get_class($this)."->".__FUNCTION__." - File ".basename($filename)." is blacklisted - return NOK<br></pre>\n";
                return false;
            }
        }
        
        
        if($this->dbg_intern >= 1) echo "<pre style='padding: 0px; margin: 0px;'>".get_class($this)."->".__FUNCTION__." - File ".basename($filename)." not listed - return OK<br></pre>\n";
        return true;
        
    }
    
    
    
    /**
     * The main logging method
     * 
     * This method is the main logging method. In this method all the log 
     * messages are stored to the defined logfile, sent to firephp or inserted 
     * into the sql database. The method takes the message text and the type. 
     * The rest of the information fields are generated with help of the 
     * debug_backtrace() functionality. The collected information fields will 
     * be formated and then sent to the configured log targets.
     * 
     * @link http://www.php.net/manual/en/function.debug-backtrace.php debug_backtrace()
     * @see set_log_target()
     * @see set_log_target_firephp()
     * @see set_log_target_db()
     * 
     * @param string $msg_text Message text.
     * @param string $type Type of log message
     * @param int $add_traceback_index Additional index to trace back
    **/
    private function logging($msg_text, $type, $add_traceback_index)
    {
        //
        // Check if logging is enabled for this log message
        //
        
        // Check if logging is enabled for browser of file
        if( $this->tobrowser == false && $this->tofile  == false &&
            $this->tofirephp == false && $this->tosqldb == false )
        {
            // do nothing
            if($this->dbg_intern >= 1) echo "<pre style='padding: 0px; margin: 0px;'>".get_class($this)."->".__FUNCTION__." - No target enabled - Do nothing and return.<br></pre>\n";
            return;
        }
        
        // Check if filename is set when onlx file logging is enabled
        if( $this->tobrowser == false && $this->tofile  == true  && 
            isset($this->filename[trim($type)]) == true && $this->filename[trim($type)] == null && 
            $this->tofirephp == false && $this->tosqldb == false )
        {
            // do nothing (missing filename)
            if($this->dbg_intern >= 1) echo "<pre style='padding: 0px; margin: 0px;'>".get_class($this)."->".__FUNCTION__." - Only file target enabled but no filename available - Do nothing and return.<br></pre>\n";
            return;
        }
        //
        // Check if logging is enabled for this log message - END
        //
        
        
        
        //
        // Get the micro time stamp and format it
        //
        $ts_temp = explode(' ',microtime());
        $msec = explode('.', $ts_temp[0]);
        $micro_ts = $ts_temp[1].".".$msec[1];
        //
        // Get the micro time stamp and format it - END
        //
        
        
        
        //
        // calculate and format time difference
        //
        if($type == "PERFORM")
        {
            $time_diff = $micro_ts - $this->micro_timestamp_perform;
        }
        else
        {
            $time_diff = $micro_ts - $this->micro_timestamp;
            
            // set new timestamp if option is set fir line diff
            if($this->timestamp_line_diff == true)
            {
                $this->micro_timestamp = $micro_ts;
            }
        }
        
        $time_diff = number_format($time_diff, 4, '.', '');
        if($time_diff < 10) "  ".$time_diff;
        if($time_diff < 100) " ".$time_diff;
        //
        // calculate and format time difference - END
        //
        
        
        
        //
        // Set the filename for logging based on the type
        //
        if(isset($this->filename[trim($type)]) == false)
        {
            $log_filename = $this->filename["*"];
        }
        else
        {
            $log_filename = $this->filename[$type];
        }
        //
        //
        //
        
        
        
        //
        // Check if this is the first log message and add an empty line
        //
        if($this->first_log == true)
        {
            $this->first_log = false;
            error_log("\n\n", 3, $log_filename);
        }
        //
        // Check if this is the first log message and add an empty line -END
        //
        
        
        
        //
        // get traceback informations
        //
        if(is_int($add_traceback_index) == false) $add_traceback_index = 0;
        $infoarray  = debug_backtrace();
        $tbi        = 1 + $add_traceback_index;
        
        // check if the requested backtrace object exists. 
        // If not count down till the next existsing is found.
        while(isset($infoarray[$tbi]) === false)
        {
            if(!isset($infoarray[$tbi])) $tbi--;
            if($tbi < 0) $tbi = 0;
        }
        //
        // get traceback informations - END
        //
        
        
        
        //
        // Set the content of the logging fields
        //
        $date_time      = date("Y-m-d@H:i:s").".".$this->msec();
        $session        = $this->sessid;
        $ip             = $this->remote_ip;
        // information from traceback
        if($type == "EXCEPT")
        {
            $file_complete = $infoarray[$tbi]["args"][0]->getFile();
            $file = basename($file_complete);
            $line = $infoarray[$tbi]["args"][0]->getLine();
            $trace = $infoarray[$tbi]["args"][0]->getTrace();
            $function       = "---";
            if(isset($trace[0]["function"]))
            {
                $function = $trace[0]["function"];
            }
        }
        else
        {
            // get file name of the logging source if available
            $file_complete  = "";
            if(isset($infoarray[$tbi]["file"])) $file_complete  = $infoarray[$tbi]["file"];
            $file = basename($file_complete);
            
            // get line number of the logging source if available
            $line  = "";
            if(isset($infoarray[$tbi]["file"])) $line  = $infoarray[$tbi]["line"];
            
            // get function name if available
            $function       = "---";
            if( count($infoarray) > ($tbi + 1) )
            {
                $function   = $infoarray[$tbi + 1]["function"];
            }
            if($function == "include" || $function == "include_once")
            {
                $function   = "---";
            }
        }
        //
        // Set the content of the logging fields - END
        //
        
        
        
        //
        // Check if logging is enabled for this filename / filepath
        //
        if($this->allow_logging_filepath($file_complete) === false)
        {
            return;
        }
        //
        // Check if logging is enabled for this filename / filepath - END
        //
        
        
        
        //
        // Format the content of the logging fields
        //
        $type       = substr($type.         "                                           ", 0, $this->strlen_type);
        $ip         = substr($ip.           "                                           ", 0, $this->strlen_ip);
        $session    = substr($session.      "                                           ", 0, $this->strlen_sess);
        $line       = substr("                                             ".$line       , 0 - $this->strlen_line);
        $time_diff  = substr("                                             ".$time_diff  , 0 - $this->strlen_diff);
        if(strlen($function) < $this->strlen_func)
        {
            $function = substr($function."                                                           ", 0, $this->strlen_func);
        }
        if(strlen($file) < $this->strlen_file)
        {
            $file = substr($file.       "                                                            ", 0, $this->strlen_file);
        }
        //
        // Format the content of the logging fields
        //
    	
        
        
        //
        // create logging line with all enabled fields
        //
        $fs = $this->field_seperator;
        $Log_message = "";
        $Log_message_fp = "";               // for the firebug log message
        $Log_message_sql = "";
        $Log_fields_sql = "";
        if( $this->log_field["datetime" ] == true )
        {
            // enable the date and time field
            $Log_message     .= "$date_time $fs ";
            //$Log_message_fp  .= "$time_diff $fs ";
            $Log_message_sql .= "'$date_time', ";
            $Log_fields_sql  .= "datetime, ";
        }
        if( $this->log_field["timediff" ] == true )
        {
            // enable the time difference field
            $Log_message     .= "$time_diff $fs ";
            $Log_message_fp  .= "$time_diff $fs ";
            $Log_message_sql .= "'$time_diff', ";
            $Log_fields_sql  .= "timediff, ";
        }
        if( $this->log_field["sessionid"] == true )
        {
            // enable the session-id field
            $Log_message     .= "$session $fs ";
            //$Log_message_fp  .= "$time_diff $fs ";
            $Log_message_sql .= "'$session', ";
            $Log_fields_sql  .= "sessionid, ";
        }
        if( $this->log_field["ip"       ] == true )
        {
            // enable the ip address field
            $Log_message     .= "$ip $fs ";
            //$Log_message_fp  .= "$time_diff $fs ";
            $Log_message_sql .= "'$ip', ";
            $Log_fields_sql  .= "ip, ";
        }
        if( $this->log_field["type"     ] == true )
        {
            // enable the log message type field
            $Log_message     .= "$type $fs ";
            $Log_message_fp  .= "$type $fs ";
            $Log_message_sql .= "'$type', ";
            $Log_fields_sql  .= "type, ";
        }
        if( $this->log_field["line"     ] == true )
        {
            // enable the line number field
            $Log_message     .= "$line $fs ";
            $Log_message_fp  .= "$line $fs ";
            $Log_message_sql .= "'$line', ";
            $Log_fields_sql  .= "line, ";
        }
        if( $this->log_field["file"     ] == true )
        {
            // enable the file name field
            $Log_message     .= "$file $fs ";
            $Log_message_fp  .= "$file $fs ";
            $Log_message_sql .= "'$file', ";
            $Log_fields_sql  .= "file, ";
        }
        if( $this->log_field["function" ] == true )
        {
            // enable the function name field
            $Log_message     .= "$function $fs ";
            $Log_message_fp  .= "$function $fs ";
            $Log_message_sql .= "'$function', ";
            $Log_fields_sql  .= "function, ";
        }
        //
        // create logging line with all enabled fields
        //
        
        
        
        //
        // Log message cach 
        //
        if( isset($this->log_cach[trim($type)]) == true && $this->log_cach[trim($type)]["enabled"] == true )
        {
            
            if(preg_match("/\n/", $msg_text) == false)
            {
                $this->log_cach_content[trim($type)][] = "$Log_message$msg_text";
            }
            else
            {
                $linestxt = preg_split("/\n/", $msg_text);
                foreach($linestxt as $linetxt)
                {
                    $this->log_cach_content[trim($type)][] = "$Log_message$linetxt";
                }
            }
        }
        if( isset($this->log_cach["*"]) == true && $this->log_cach["*"]["enabled"] == true )
        {
            
            if(preg_match("/\n/", $msg_text) == false)
            {
                $this->log_cach_content["*"][] = "$Log_message$msg_text";
            }
            else
            {
                $linestxt = preg_split("/\n/", $msg_text);
                foreach($linestxt as $linetxt)
                {
                    $this->log_cach_content["*"][] = "$Log_message$linetxt";
                }
            }
        }
        //
        // Log message cach - END
        //
        
        
        
        //
        // Save the log message to a file
        //     - Single-line to normal or performance log
        //     - Multi-line to normal or performance log
        //
        if($this->tofile == true)
        {
            if(preg_match("/\n/", $msg_text) == false)
            {
                //
                // safe single line log messages
                //
                // safe to normal log file
                if($this->dbg_intern >= 1) echo "<pre style='padding: 0px; margin: 0px;'>".get_class($this)."->".__FUNCTION__." - Log type=\"$type\" to file=".basename($log_filename)."<br></pre>\n";
                error_log("$Log_message$msg_text\n", 3, $log_filename);
                //
                // safe single line log messages - END
                //
            }
            else
            {
                //
                // safe multiline log messages
                //
                $linestxt = preg_split("/\n/", $msg_text);
                foreach($linestxt as $linetxt)
                {
                    $linetxt = preg_replace("/\r/", "", $linetxt);
                    
                    // ignore empty lines
                    if($linetxt == "") continue;
                    
                    // safe to normal log file
                    if($this->dbg_intern >= 1) echo "<pre style='padding: 0px; margin: 0px;'>".get_class($this)."->".__FUNCTION__." - Log type=\"$type\" to file=".basename($log_filename)."<br></pre>\n";
                    error_log("$Log_message$linetxt\n", 3, $log_filename);
                }
                //
                // safe multiline log messages - END
                //
            }
        }
        //
        // Save the log message to a file - END
        //
        
        
        
        //
        // Show the log message to the browser
        //     - Single-line or Multi-line
        //
        if($this->tobrowser == true)
        {
            $msg_text = htmlspecialchars($msg_text);
            if(preg_match("/\n/", $msg_text) == false)
            {
                //
                // show single line log messages
                //
                echo "<pre style='padding: 0px; margin: 0px;'>$Log_message$msg_text</pre>\n";
                //
                // show single line log messages - END
                //
            }
            else
            {
                //
                // show multiline log messages - END
                //
                $linestxt = preg_split("/\n/", $msg_text);
                foreach($linestxt as $linetxt)
                {
                    $linetxt = preg_replace("/\r/", "", $linetxt);
                    
                    // ignore empty lines
                    if($linetxt == "") continue;
                    
                    // send log message to the browser
                    echo "<pre style='padding: 0px; margin: 0px;'>$Log_message$linetxt</pre>\n";
                }
                //
                // show multiline log messages - END
                //
            }
        }
        //
        // Show the log message to the browser - END
        //
        
        
        
        //
        // Show the log message to the FirePHP browser-addon
        //     - Single-line or Multi-line
        //
        try
        {
            if($this->dbg_intern >= 1) echo "<pre style='padding: 0px; margin: 0px;'>".get_class($this)."->".__FUNCTION__." - Check for FirePHP debugging ...<br/></pre>\n";
            if($this->tofirephp != null)
            {
                // for array logging in FirePHP
                $this->Temp_Log_Fields = $Log_message;
                
                if($this->dbg_intern >= 1) echo "<pre style='padding: 0px; margin: 0px;'>".get_class($this)."->".__FUNCTION__." - Check for FirePHP debugging ... ENABLED<br/></pre>\n";
                
                // check for single or multiline message
                if(preg_match("/\n/", $msg_text) == false)
                {
                    if($this->dbg_intern >= 1) echo "<pre style='padding: 0px; margin: 0px;'>".get_class($this)."->".__FUNCTION__." - Check for FirePHP debugging ... type = $type ... SINGLE LINE<br/></pre>\n";
                    
                    //
                    // send single line log messages
                    //
                    $Log_message_fp = preg_replace("/  /", " .", $Log_message_fp);
                    switch(trim($type))
                    {
                    case "INFO":
                        $this->tofirephp->info($Log_message_fp.$msg_text);
                        break;
                    case "DEBUG":
                    case "DEBUG2":
                    //case "DEBUG_A":       // will be logged at the debug_array() function
                    //case "DEBUG2_A":      // will be logged at the debug2_array() function
                        $this->tofirephp->log($Log_message_fp.$msg_text);
                        break;
                    case "ERROR":
                        $this->tofirephp->error($Log_message_fp.$msg_text);
                        break;
                    case "PERFORM":
                        $this->tofirephp->warn($Log_message_fp.$msg_text);
                        break;
                    case "TRACE":
                    case "TRACE_I":
                    case "TRACE_C":
                        $this->tofirephp->log($Log_message_fp.$msg_text);
                        break;
                    }
                    //
                    // send single line log messages - END
                    //
                }
                else
                {
                    if($this->dbg_intern >= 1) echo "<pre style='padding: 0px; margin: 0px;'>".get_class($this)."->".__FUNCTION__." - Check for FirePHP debugging ... type = $type ... MULTIBLE LINE<br/></pre>\n";
                    
                    //
                    // send multiline log messages - END
                    //
                    $Log_message_fp = preg_replace("/  /", " .", $Log_message_fp);
                    $msg_text = preg_replace("/  /", " .", $msg_text);
                    $msg_text = preg_split("/\n/", $msg_text);
                    switch(trim($type))
                    {
                    case "INFO":
                        $this->tofirephp->info($Log_message_fp.$msg_text);
                        break;
                    case "DEBUG":
                    case "DEBUG2":
                    case "DEBUG_A":       // will be logged at the debug_array() function
                    case "DEBUG2_A":      // will be logged at the debug2_array() function
                        $this->tofirephp->log($msg_text, $Log_message_fp);
                        break;
                    case "ERROR":
                        $this->tofirephp->error($msg_text, $Log_message_fp);
                        break;
                    case "PERFORM":
                        $this->tofirephp->warn($msg_text, $Log_message_fp);
                        break;
                    case "TRACE":
                    case "TRACE_I":
                    case "TRACE_C":
                        $this->tofirephp->log($msg_text, $Log_message_fp);
                        break;
                    }
                    //
                    // send multiline log messages - END
                    //
                }
            }
        }
        catch (Exception $e) {
            $null_var = null;
            $this->set_log_target_firephp($null_var);
            $this->error("FirePHP object logging disabled due to exception: \n".$e->getMessage()."\n");
        }
        //
        // Show the log message to the browser - END
        //
        
        
        //
        // Send the log message to the sql database
        //     - Single-line or Multi-line
        //
        if($this->tosqldb == true)
        {
            // replace ' with " to avoid sql injections
            $msg_text = preg_replace("/'/", "\"", $msg_text);
            
            $Log_fields_sql  = preg_replace('/\,\s$/', "", $Log_fields_sql );
            $Log_message_sql = preg_replace('/\,\s$/', "", $Log_message_sql);
            
            // send the query to the datbase
            $errno = $errtext = null;
            $query = "INSERT INTO ".$this->tosqldb_table." \n    ($Log_fields_sql, msg_text)\nVALUES \n    ($Log_message_sql, '$msg_text')";
            if($this->dbg_intern >= 1) echo "<pre style='padding: 0px; margin: 0px;'>".get_class($this)."->".__FUNCTION__." - QUERY: $query<br/></pre>\n";
            
            $this->tosqldb->query($query, $errno, $errtext);
            if($errno != null)
            {
                if($this->dbg_intern >= 1) echo "<pre style='padding: 0px; margin: 0px;'>".get_class($this)."->".__FUNCTION__." - QUERY ERROR: $errno: $errtext<br/></pre>\n";
                $this->error("SQL object logging disabled due to error - $errno: $errtext\n");
                $this->set_log_target_db(null, null);
            }
        }
        //
        // Send the log message to the sql database - END
        //
        
    }
    
    
    
    /**
     * Prepare the backtrace informations
     * 
     * This method formats and prepares the backtrace informations to be used 
     * by other methods of the class. The backtrace infromations given will be 
     * formated for easy reading. The informations will be formated in 2 lines 
     * per backtrace information entry. The first line will contain the origin 
     * and the function/method called. The second line will contain the called 
     * function and the used parameters.
     * 
     * @link http://www.php.net/manual/en/function.debug-backtrace.php debug_backtrace()
     * 
     * @param mixed $infoarray The array of backtrace informations
     * @return string The formated backtrace informations
    **/
    private function prepare_backtrace($infoarray)
    {
        // get the path name to short the filename
        $bpath = dirname($GLOBALS['_SERVER']['SCRIPT_FILENAME']).'/';
        
        
        $Info_Lines = '';
        $j = 0;
        for($i=count($infoarray)-1; $i >= 0; $i--)
        {
            $j++;
            // get traceback informations
            if(isset($infoarray[$i]['file']))
            {
                $file = str_replace("$bpath", "", $infoarray[$i]['file']);
            }
            else
            {
                $file = '';
            }
            if(isset($infoarray[$i]['line']))
            {
                $line = $infoarray[$i]['line'];
            }
            else
            {
                $line = '';
            }
            
            // check if a function was called
            if(isset($infoarray[$i]['function']))
            {
                $function = $infoarray[$i]['function'];
                $args = $infoarray[$i]['args'];
                
                // format and prepare arguments of function calls
                $param = '';
                foreach($args as $arg)
                {
                    $arg = str_replace("$bpath", '', $arg);
                    $param = "'$arg', ";
                }
                $param = preg_replace('/, $/', '', $param);
                
                // get the class name is available
                $class_name = '';
                if(isset($infoarray[$i]['class']))
                {
                    $class = $infoarray[$i]['class'];
                    $class_name = $class.'->';
                }
                $Info_Lines .= "*** $j: File $file (Line $line): Call to function $class_name$function().\n";
                $Info_Lines .= "***         $class_name$function($param).\n";

            }
            else
            {
                $Info_Lines .= "*** $j: File $file (Line $line): Called.\n";
            }
        }
        
        return $Info_Lines;
    }
    
    
    
    /**
     * get the current micro time (milli seconds)
     * 
     * This method returns the current time as float including the microtime.
     * 
     * @return int The miliseconds within the current second
    **/
    private function msec()
    {
        // get the microtime in format "0.######## ##########"
        $m = explode(' ',microtime());
        // remove the "0." from the microseconds
        $msec = explode('.', $m[0]);
        // truncate to 4 digits
        $msec_str = substr($msec[1], 0, 4);
        return $msec_str;
    } 
    
    
    
    ////////////////////////////////////////////////////////////////////////////
    // PUBLIC methods of the class - STD
    // - Standard logging type methods
    ////////////////////////////////////////////////////////////////////////////
    
    
    
    /**
     * Log message from type INFO
     * 
     * This method initiates a log message of the type INFO. This log 
     * information is sent to the configured log target. Only the text message 
     * has to be provided. Providing a traceback index is only recommended if 
     * you want to get the backtrace information from a level lower then from 
     * the file and function where the log message was called from.
     * 
     * @link http://www.php.net/manual/en/function.debug-backtrace.php debug_backtrace()
     * 
     * @param string $msg_text Text message to be logged
     * @param int $add_traceback_index Additional index to trace back
    **/
    public function info($msg_text, $add_traceback_index=0)
    {
        if($this->log_types["info"] == true)
        {
            $this->logging($msg_text, "INFO", $add_traceback_index);
        }
    }
    
    
    
    /**
     * Log message from type DEBUG
     * 
     * This method initiates a log message of the type DEBUG. This log 
     * information is sent to the configured log target. Only the text message 
     * has to be provided. Providing a traceback index is only recommended if 
     * you want to get the backtrace information from a level lower then from 
     * the file and function where the log message was called from.
     * 
     * @link http://www.php.net/manual/en/function.debug-backtrace.php debug_backtrace()
     * 
     * @param string $msg_text Text message to be logged
     * @param int $add_traceback_index Additional index to trace back
    **/
    public function debug($msg_text, $add_traceback_index=0)
    {
        if($this->log_types["debug"] == true)
        {
            $this->logging($msg_text, "DEBUG", $add_traceback_index);
        }
    }
    
    
    
    /**
     * Log message from type ERROR
     * 
     * This method initiates a log message of the type ERROR. This log 
     * information is sent to the configured log target. Only the text message 
     * has to be provided. Providing a traceback index is only recommended if 
     * you want to get the backtrace information from a level lower then from 
     * the file and function where the log message was called from.
     * 
     * @link http://www.php.net/manual/en/function.debug-backtrace.php debug_backtrace()
     * 
     * @param string $msg_text Text message to be logged
     * @param int $add_traceback_index Additional index to trace back
    **/
    public function error($msg_text, $add_traceback_index=0)
    {
        if($this->log_types["error"] == true)
        {
            $this->logging($msg_text, "ERROR***", $add_traceback_index);
        }
    }
    
    
    
    /**
     * Log message from type DEBUG2
     * 
     * This method initiates a log message of the type DEBUG2. This log 
     * information is sent to the configured log target. Only the text message 
     * has to be provided. Providing a traceback index is only recommended if 
     * you want to get the backtrace information from a level lower then from 
     * the file and function where the log message was called from.
     * 
     * @link http://www.php.net/manual/en/function.debug-backtrace.php debug_backtrace()
     * 
     * @param string $msg_text Text message to be logged
     * @param int $add_traceback_index Additional index to trace back
    **/
    public function debug2($msg_text, $add_traceback_index=0)
    {
        if($this->log_types["debug2"] == true)
        {
            $this->logging($msg_text, "DEBUG2", $add_traceback_index);
        }
    }
    
    
    
    /**
     * Log message from type DEBUG_A (debug array)
     * 
     * This method initiates a log message of the type DEBUG_A. This log 
     * information is sent to the configured log target. Only the text message 
     * and the array/object to be logged has to be provided. Providing a 
     * traceback index is only recommended if you want to get the backtrace 
     * information from a level lower then from the file and function where 
     * the log message was called from.
     * 
     * @link http://www.php.net/manual/en/function.debug-backtrace.php debug_backtrace()
     * 
     * @param string $msg_text Text message to be logged
     * @param mixed $msg_array array to be logged
     * @param int $add_traceback_index Additional index to trace back
    **/
    public function debug_array($msg_text, $msg_array, $add_traceback_index=0)
    {
        if($this->log_types["debug_array"] == true)
        {
            // description of array
            $this->logging($msg_text."\n".print_r($msg_array, true), "DEBUG_A", $add_traceback_index);
            return;
            
        }
    }
    
    
    
    /**
     * Log message from type DEBUG2_A (debug2 array)
     * 
     * This method initiates a log message of the type DEBUG2_A. This log 
     * information is sent to the configured log target. Only the text message 
     * and the array/object to be logged has to be provided. Providing a 
     * traceback index is only recommended if you want to get the backtrace 
     * information from a level lower then from the file and function where 
     * the log message was called from.
     * 
     * @link http://www.php.net/manual/en/function.debug-backtrace.php debug_backtrace()
     * 
     * @param string $msg_text Text message to be logged
     * @param mixed $msg_array array to be logged
     * @param int $add_traceback_index Additional index to trace back
    **/
    public function debug2_array($msg_text, $msg_array, $add_traceback_index=0)
    {
        if($this->log_types["debug2_array"] == true)
        {
            // description of array
            $this->logging($msg_text."\n".print_r($msg_array, true), "DEBUG2_A", $add_traceback_index);
            
        }
    }
    
    
    
    /**
     * Log message from type CLIENT
     * 
     * This method initiates a log message of the type CLIENT. This log 
     * information is sent to the configured log target. 
     * The following informations are collected and send to the logtarteg. The 
     * current URL, the URL of the last page if available and the browser 
     * informations.
     * Providing a traceback index is only recommended if 
     * you want to get the backtrace information from a level lower then from 
     * the file and function where the log message was called from.
     * 
     * @link http://www.php.net/manual/en/function.debug-backtrace.php debug_backtrace()
     * 
     * @access public
     * 
     * @param int $add_traceback_index Additional index to trace back
    **/
    public function client_info($add_traceback_index=0)
    {
        if($this->log_types['client_info'] === TRUE)
        {
            //
            // Collect the client informations
            //
            
            
            // get the last visited URL
            $last_url = '';
            if(isset($GLOBALS['_SERVER']['HTTP_REFERER']) === TRUE)
            {
                $last_url = $GLOBALS['_SERVER']['HTTP_REFERER'];
            }
            
            
            // get the current URL
            $request_url = '(MAN) '.$GLOBALS['_SERVER']['SERVER_NAME'].$GLOBALS['_SERVER']['PHP_SELF'].'?'.$GLOBALS['_SERVER']['QUERY_STRING'];
            if(isset($GLOBALS['_SERVER']['SCRIPT_URI']) == TRUE)
            {
                $request_url = '(S-URI) '.$GLOBALS['_SERVER']['SCRIPT_URI'].'?'.$GLOBALS['_SERVER']['QUERY_STRING'];
            }
            
            
            // get the browser string (as it was sent by the browser
            $browser_string = '-';
            if(isset($GLOBALS['_SERVER']['HTTP_USER_AGENT']) === TRUE)
            {
                $browser_string = $GLOBALS['_SERVER']['HTTP_USER_AGENT'];
            }
            
            
            // Get the browser accepted languages
            $browser_lang = '-';
            if(isset($GLOBALS['_SERVER']['HTTP_ACCEPT_LANGUAGE']) === TRUE)
            {
                $browser_lang = $GLOBALS['_SERVER']['HTTP_ACCEPT_LANGUAGE'];
            }
            
            
            // get the browser accepted charset
            $browser_charset = '-';
            if(isset($GLOBALS['_SERVER']['HTTP_ACCEPT_CHARSET']) === TRUE)
            {
                $browser_charset = $GLOBALS['_SERVER']['HTTP_ACCEPT_CHARSET'];
            }
            
            
            // list the GET variables
            if(isset($GLOBALS['_GET']) === TRUE)
            {
                $get_list = '*** GET Variables ('.count($GLOBALS['_GET']).')'.PHP_EOL;
                foreach($GLOBALS['_GET'] as $key=>$value)
                {
                    $get_list .= '***    ['.$key.'] = '.$value.PHP_EOL;
                }
            }
            else
            {
                $get_list = '*** GET Variables (0)\n';
            }
            
            
            // list the POST variables
            if(isset($GLOBALS['_POST']) === TRUE)
            {
                $post_list = '*** POST Variables ('.count($GLOBALS['_POST']).')'.PHP_EOL;
                foreach($GLOBALS['_POST'] as $key=>$value)
                {
                    $post_list .= '***    ['.$key.'] = '.$value.PHP_EOL;
                }
            }
            else
            {
                $post_list = '*** POST Variables (0)\n';
            }
            
            
            // list the COOKIE variables
            if(isset($GLOBALS['_COOKIE']) === TRUE)
            {
                $cookie_list = '*** COOKIE Variables ('.count($GLOBALS['_COOKIE']).')'.PHP_EOL;
                foreach($GLOBALS['_COOKIE'] as $key=>$value)
                {
                    $cookie_list .= '***    ['.$key.'] = '.$value.PHP_EOL;
                }
            }
            else
            {
                $cookie_list = '*** COOKIE Variables (0)\n';
            }
            
            
            
            //
            // Collect the client informations - END
            //
            
            
            // format the client informations
            $msg_text = '';
            $msg_text .= '*** Last visited URL: '.$last_url.''.PHP_EOL;
            $msg_text .= '*** Current URL:      '.$request_url.''.PHP_EOL;
            $msg_text .= '*** Browser string:   '.$browser_string.''.PHP_EOL;
            $msg_text .= '***   Language:   '.$browser_lang.''.PHP_EOL;
            $msg_text .= '***   Charsets:   '.$browser_charset.''.PHP_EOL;
            $msg_text .= $get_list;
            $msg_text .= $post_list;
            $msg_text .= $cookie_list;
            $msg_text .= ' '.PHP_EOL;
            
            // log the informations
            $this->logging($msg_text, "CLIENT", $add_traceback_index);
        }
    }
    
    
    
    ////////////////////////////////////////////////////////////////////////////
    // PUBLIC methods of the class - PERF
    // - Performance logging type methods
    ////////////////////////////////////////////////////////////////////////////
    
    
    
    
    /**
     * Log message from type PERFORM (Performance)
     * 
     * This method initiates a log message of the type PERFORM. This log 
     * information is sent to the configured log target. With the start 
     * parameter set to 'start' the measurement can be started and with 'stop' 
     * the measurement is stopped and the log message is written to the log 
     * file. Only when the measurement is stopped a log message is written.
     * Providing a traceback index is only recommended if you want to get the 
     * backtrace information from a level lower then from the file and function 
     * where the log message was called from.
     * 
     * @link http://www.php.net/manual/en/function.debug-backtrace.php debug_backtrace()
     * 
     * Log message from type performance
     * 
     * @param string $msg_text Text message to be logged
     * @param sting $start The start or stop command
     * @param int $add_traceback_index Additional index to trace back
    **/
    public function performance($msg_text, $start="stop", $add_traceback_index=0)
    {
        
        // if start of measurement, only note the time
        if($start == "stop")
        {
            if($this->log_types["performance"] == true)
            {
                $this->logging($msg_text, "PERFORM", $add_traceback_index);
            }
        }
        else if($start == "start")
        {
            // Get the mocro time stamp and format it
            $ts_temp = explode(' ',microtime());
            $msec = explode('.', $ts_temp[0]);
            $micro_ts = $ts_temp[1].".".$msec[1];
            
            $this->micro_timestamp_perform = $micro_ts;
            return;
        }
        
        
    }
    
    
    
    
    ////////////////////////////////////////////////////////////////////////////
    // PUBLIC methods of the class - TRACE
    // - Trace logging type methods
    ////////////////////////////////////////////////////////////////////////////
    
    
    
    
    /**
     * Log message from type TRACE (backtrace)
     * 
     * This method initiates a complete backtrace to be sent to the logtarget. 
     * This log masseges are from the type TRACE. The backtrace informations 
     * are formated to be easy to read. Providing a traceback index is only 
     * recommended if you want to get the backtrace information from a level 
     * lower then from the file and function where the log message was called 
     * from.
     * 
     * @link http://www.php.net/manual/en/function.debug-backtrace.php debug_backtrace()
     * 
     * @param string $msg_text Text message to be logged
     * @param bool $trace_info Log additional backtrace information
     * @param int $add_traceback_index Additional index to trace back
    **/
    public function backtrace($msg_text, $trace_info=false, $add_traceback_index=0)
    {
        // first line with query and query parameters
        $First_Lines = $msg_text."\n";
        $First_Lines .= "*** REQUEST_URL=".$GLOBALS["_SERVER"]["SERVER_NAME"].$GLOBALS["_SERVER"]["PHP_SELF"]."?".$GLOBALS["_SERVER"]["QUERY_STRING"]."\n";
        
        
        // list the GET variables
        if(isset($GLOBALS['_GET']) === TRUE)
        {
            $First_Lines .= "*** GET Variables (".count($GLOBALS["_GET"]).")\n";
            foreach($GLOBALS["_GET"] as $key=>$value)
            {
                $First_Lines .= "***    [$key] = $value\n";
            }
        }
        else
        {
            $First_Lines .= "*** GET Variables (0)\n";
        }
        
        
        // list the POST variables
        if(isset($GLOBALS['_POST']) === TRUE)
        {
            $First_Lines .= "*** POST Variables (".count($GLOBALS["_POST"]).")\n";
            foreach($GLOBALS["_POST"] as $key=>$value)
            {
                $First_Lines .= "***    [$key] = $value\n";
            }
        }
        else
        {
            $First_Lines .= "*** POST Variables (0)\n";
        }
        
        
        // list the COOKIE variables
        if(isset($GLOBALS['_COOKIE']) === TRUE)
        {
            $First_Lines .= "*** COOKIE Variables (".count($GLOBALS["_COOKIE"]).")\n";
            foreach($GLOBALS["_COOKIE"] as $key=>$value)
            {
                $First_Lines .= "***    [$key] = $value\n";
            }
        }
        else
        {
            $First_Lines .= "*** COOKIE Variables (0)\n";
        }
        
        
        
        // send data to the log if enabled
        if($this->log_types["backtrace"] == true)
        {
            $this->logging(print_r($First_Lines, true), "TRACE", $add_traceback_index);
        }
        
        
        if($trace_info === true)
        {
            //Do for all elements of the array
            $infoarray = debug_backtrace();
            
            // format the backtrace informations
            $Info_Lines = $this->prepare_backtrace($infoarray);
            
            if($this->log_types["backtrace"] == true)
            {
                $this->logging($Info_Lines, "TRACE", $add_traceback_index);
                //$this->logging("--- DBG --- ".print_r($infoarray, true), "TRACE", $add_traceback_index);
            }
        }
    }
    
    
    
    
    ////////////////////////////////////////////////////////////////////////////
    // PUBLIC methods of the class - PHPERR
    // - PHP error catching methods
    ////////////////////////////////////////////////////////////////////////////
    
    
    
    /**
     * @ignore
     * Log message from type PHP_ERR (php generated errors)
     * 
     * This method is called for each php error. This method is used to get the 
     * catched php error messages and send them to the configured log target. 
     * The list of parameters are required for the php error handler. It is 
     * not designed to be called manually from within the php code.
     * 
     * ATTENTION: This method should not be called manually!
     * 
     * @param int $errno Code of the error messages as reported by php
     * @param string $errmsg Error string as reported from php
     * @param string $filename File name as reported from php
     * @param int $linenum Line number as reported from php
    **/
    public function catch_php_error($errno, $errmsg, $filename, $linenum) //, $errcontext=null)
    {
        // check if error shold be logged - if not return.
        if(isset($this->log_phptypes[$errno]) == false || $this->log_phptypes[$errno] === false)
        {
            if(isset($this->log_phptypes[$errno]) == false)
            {
                // log it with spezial informations
                $this->logging("Unknown PHP ERROR accoured in File $filename at Line $linenum ($errno: $errmsg)", "PHP_ERR*", 0);
                $this->logging("Unknown PHP ERROR accoured in File $filename at Line $linenum ($errno: $errmsg)", "PHP_ERR*", 1);
            }
            
            // return without logging
            return;
        }
        
        
        // Define an assoc array of error strings.
        // 
        // The following error types cannot be handled with a user defined function: 
        // E_ERROR, E_PARSE, E_CORE_ERROR, E_CORE_WARNING, E_COMPILE_ERROR, 
        // E_COMPILE_WARNING, and most of E_STRICT raised in the file where 
        // set_error_handler() is called.
        $errortype[E_ERROR            ] = "E_ERROR";              // Not possible to catch via user defined error handler
        $errortype[E_PARSE            ] = "E_PARSE";              // Not possible to catch via user defined error handler
        $errortype[E_CORE_ERROR       ] = "E_CORE_ERROR";         // Not possible to catch via user defined error handler
        $errortype[E_CORE_WARNING     ] = "E_CORE_WARNING";       // Not possible to catch via user defined error handler
        $errortype[E_COMPILE_ERROR    ] = "E_COMPILE_ERROR";      // Not possible to catch via user defined error handler
        $errortype[E_COMPILE_WARNING  ] = "E_COMPILE_WARNING";    // Not possible to catch via user defined error handler
        $errortype[0                  ] = "{@}ERROR";             // Statement that caused the error was prepended by the @ error-control operator.
        $errortype[E_WARNING          ] = "E_WARNING";
        $errortype[E_NOTICE           ] = "E_NOTICE";
        $errortype[E_USER_ERROR       ] = "E_USER_ERROR";
        $errortype[E_USER_WARNING     ] = "E_USER_WARNING";
        $errortype[E_USER_NOTICE      ] = "E_USER_NOTICE";
        $errortype[E_STRICT           ] = "E_STRICT";
        if (version_compare(PHP_VERSION, '5.2.0') === 1)
        {
            $errortype[E_RECOVERABLE_ERROR] = "E_RECOVERABLE_ERROR";  // available since PHP 5.2.0
        }
        if (version_compare(PHP_VERSION, '5.3.0') === 1)
        {
            $errortype[E_DEPRECATED       ] = "E_DEPRECATED";         // available since PHP 5.3.0
            $errortype[E_USER_DEPRECATED  ] = "E_USER_DEPRECATED";    // available since PHP 5.3.0
        }
        
        
        // do different backtrace_index for different types
        switch($errno)
        {
            case E_ERROR          :                               // Not possible to catch via user defined error handler
            case E_PARSE          :                               // Not possible to catch via user defined error handler
            case E_CORE_ERROR     :                               // Not possible to catch via user defined error handler
            case E_CORE_WARNING   :                               // Not possible to catch via user defined error handler
            case E_COMPILE_ERROR  :                               // Not possible to catch via user defined error handler
            case E_COMPILE_WARNING:                               // Not possible to catch via user defined error handler
                $backtrace_index = 0;
                break;
            case 0                :                               // Statement that caused the error was prepended by the @ error-control operator.
            case E_WARNING        :
                $backtrace_index = 1;
                break;
            case E_NOTICE         :
                $backtrace_index = 0;
                break;
            case E_USER_ERROR     :
            case E_USER_WARNING   :
            case E_USER_NOTICE    :
            case E_STRICT         :
                $backtrace_index = 1;
                break;
            default:
                $backtrace_index = 0;
        }
        
        
        // set the path to replace in file name
        $bpath = dirname($GLOBALS["_SERVER"]["SCRIPT_FILENAME"])."/";
        $filename = str_replace("$bpath", "", $filename);
        
        // define log text
        $Info_Line = $errortype[$errno].": [$filename (Line $linenum)] $errmsg";
        
        
        // send it to the logging if enabled
        if($this->log_types["php_errors"] == true)
        {
            $this->logging($Info_Line, "PHP_ERR", $backtrace_index);
            
            
            // check if context variables are available and print them out.
            //if($errcontext != null)
            //{
            //    $this->logging($Info_Line." - Context:\n".print_r($errcontext, true), "PHP_ERR", 0);
            //}
        }
    }
    
    
    
    
    /**
     * @ignore
     * Log message from type EXCEPT (unhandled exception)
     * 
     * This method is called for unhandled exceptions. This method is used to 
     * catch unhandled exceptions and send them to the configured log target. 
     * The list of parameters are required for the exception handler. It is 
     * not designed to be called manually from within the php code. The script 
     * will be terminated by php after the exception handling is finished.
     * 
     * ATTENTION: This method should not be called manually!
     * 
     * @param object $e The unhandled exception object
    **/
    public function catch_unhandled_exception(Exception $e)
    {
        // set the path to replace in file name
        $bpath = dirname($GLOBALS["_SERVER"]["SCRIPT_FILENAME"])."/";
        $filename = str_replace("$bpath", "", $e->getFile());
        
        // define log text
        $Info_Line = "Uncauht ".get_class($e)."(code ".$e->getCode()."): [".$filename." (Line ".$e->getLine().")] ".$e->getMessage();
        
        
        // send it to the logging if enabled
        if($this->log_types["php_errors"] == true)
        {
            $this->logging($Info_Line, "EXCEPT", 1);
            
            
            // Log the trace information as seperate line
            $Info_Trace = $this->prepare_backtrace($e->getTrace());
            
            $this->logging($Info_Trace, "EXCEPT", 1);
        }
    }
    
    
    
    ////////////////////////////////////////////////////////////////////////////
    // PUBLIC methods of the class - EXT
    // - Extended logging type methods to integrate new types
    ////////////////////////////////////////////////////////////////////////////
    
    
    
    /**
     * Log message from defied type
     * 
     * This method initiates a log message of the defined type. This log 
     * information is sent to the configured log target. Only the text message 
     * and the type has to be provided. Providing a traceback index is only 
     * recommended if you want to get the backtrace information from a level 
     * lower then from the file and function where the log message was called 
     * from. 
     * 
     * This is an internal method to integrate other classes to the 
     * logging functionality.
     * 
     * @link http://www.php.net/manual/en/function.debug-backtrace.php debug_backtrace()
     * 
     * @param string $type The type of the message
     * @param string $msg_text Text message to be logged
     * @param int $add_traceback_index Additional index to trace back
    **/
    public function log_as_type($type, $msg_text, $add_traceback_index=0)
    {
        if(isset($this->log_types[strtolower($type)]) == false || $this->log_types[strtolower($type)] == true)
        {
            $this->logging($msg_text, strtoupper($type), $add_traceback_index);
        }
    }
    
    
    
    ////////////////////////////////////////////////////////////////////////////
    // END OF METHODS
    ////////////////////////////////////////////////////////////////////////////
    
    
    
}




?>
