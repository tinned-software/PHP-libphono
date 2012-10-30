<?php
/**
 * 
 * MainClass class
 * 
 * Class to prived a general set of methods for classes to avoid redundant
 * code for reusable functions
 * 
 * @package framework
 * @subpackage core
 * @author Gerhard Steinbeis (info [at] tinned-software [dot] net)
 * @copyright Copyright (c) 2010
 * @version 0.21
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * 
 * @todo remove workaround for WARN messages at method warning()
 * @todo define proper error codes for main class errors (constructor, timer_start, timer_stop)
**/



/**
 * The Main class provides general set of methods for other classes
 * 
 * The Main class provides a set of methods to get access to often used
 * functionalities within a class. Most of the methods are protected to be
 * used only from within the extending class.
 * 
 * The main methods that are provided are related to the logging. A log object
 * can be handed over to the main class to allow logging to that Debug_Logging
 * object. If no logging object is provided, this class will write log files on
 * its own. The logging functionalities are limited in this case.
 * 
 * Additionally this class provides a method to check a list of functions and
 * classes to be able to verify the extended class prerequisites. as well as a
 * method to get the current microseconds timestamp.
 * 
 * @package framework
 * @subpackage core
 * 
**/
class Main
{
    ////////////////////////////////////////////////////////////////////////////
    // PROPERTIES of the class
    ////////////////////////////////////////////////////////////////////////////
    
    /**
     * @ignore
     * To enable internal logging. This will send log messages of the class to
     * the browser. Used to debug the class.
     * 
     * @access public
     * 
     * @var integer
    **/
    public $dbg_intern_main     = 0;
    
    /**
     * @ignore
     * To define the log path if no logging object is provided (autonomous logging).
     * 
     * @access public
     * 
     * @var string
    **/
    public $log_path            = "/../../log/";
    
    /**
     * The log level defined during initialisation.
     * 
     * @access protected
     * 
     * @var integer
    **/
    protected $dbg_level        = -1;
    
    /**
     * Holds the logging object provided during initialisation.
     * 
     * @access protected
     * 
     * @var object
    **/
    protected $logging_object   = NULL;

    /**
     * Holds the profiler object provided during initialisation.
     * 
     * @access protected
     * 
     * @var object
    **/
    protected $profiler_object   = NULL;
    
    /**
     * The session variable for autonomous logging.
     * 
     * @access private
     * 
     * @var string
    **/
    private $logging_sessid     = "";
    
    /**
     * Flag to find out if the class was initialized or not.
     * 
     * @access private
     * 
     * @var boolean
    **/
    private $initialized        = FALSE;
    
    /**
     * Error reporting flag.
     * [0] -> An initial error will be added to the list, reset_errors() will not reset.
     * [1] -> Error reporting set to on.
     * 
     * @access private
     * 
     * @var integer
    **/
    private $support_level = 0;
    
    /**
     * A variable to indicate whether the class has output a compatibility warning to the 
     * log if necessary
     * @access private
     * @var boolean
     **/
    private $support_level_logged = FALSE;
    
    /**
     * Error reporting structure.
     * 
     * When a new error is added, it gets appended at the end of this list.
     * The current structure is as follows:
     * {{ 'code' => 100, 'text' => 'This class does not support error reporting' }}
     * 
     * @access private
     * 
     * @var array
    **/
    private $_error_list = array();
    
    
    
    ////////////////////////////////////////////////////////////////////////////
    // CONSTRUCTOR & DESCTRUCTOR methods of the class
    ////////////////////////////////////////////////////////////////////////////
    
    
    
    /**
     * The default constructor for a class object.
     * 
     * This constructor will add an initial error if the class hasn't enabled
     * error support.
     * 
     * @access public
    **/
    private function __construct ()
    {
        if($this->support_level < 1)
        {
            $this->report_error(100, 'This class does not support error reporting');
        }
    }
    
    
    /**
     * Initialisation (constructor equivalent)
     * 
     * In this method the initialisation is done. It handles the given
     * Debug_Logging object (when provided) and the log level.
     * 
     * Debug Levels are defined as follows: <br/>
     * 0 ... No logging except for ERROR and WARNing <br/>
     * 1 ... same as 0 plus DEBUG and INFO <br/>
     * 2 ... same as 0,1 plus DEBUG2 and any type not explicitly listed here. <br/>
     * 3 ... same as 0,1,2 plus DEBUG_ARRAY, DEBUG2_ARRAY <br/>
     * 
     * Types not explicitily listed here are treated as level 2.
     * 
     * @access public
     * 
     * @param int    $dbg_level         Debug log level
     * @param object &$log_object       Debug_Logging object to send log messages to
     * @param int    $support_level     flag to define support level
     * @param object &$profiler_object  Debug_Profiler object to profile with
    **/
    public function Main_init($dbg_level = -1, &$log_object = NULL, $support_level = NULL, &$profiler_object = NULL)
    {
        $this->initialized = TRUE;
        
        if(is_object($profiler_object) === TRUE)
        {
            $this->profiler_object = $profiler_object;
        }
        
        if(is_int($dbg_level) === FALSE)
        {
            $dbg_level = -1;
        }
        
        if(is_object($log_object) === TRUE)
        {
            if($this->dbg_intern_main > 0)
            {
                echo "<pre>(Line: ".__LINE__.") ".get_class($this)."(Main)->".__FUNCTION__." - log_object = ". (int) is_object($this->logging_object)."</pre>\n";
            }
            
            $this->logging_object =& $log_object;
            
            if($this->dbg_intern_main > 0)
            {
                echo "<pre>(Line: ".__LINE__.") ".get_class($this)."(Main)->".__FUNCTION__." - dbg_level = $dbg_level</pre>\n";
            }
            
            $this->dbg_level = $dbg_level;
            
        }
        
        
        if($this->dbg_intern_main > 0)
        {
            echo "<pre>(Line: ".__LINE__.") ".get_class($this)."(Main)->".__FUNCTION__." - check debug level setting.</pre>\n";
        }
        
        if($dbg_level > -1)
        {
            if($this->dbg_intern_main > 0)
            {
                echo "<pre>(Line: ".__LINE__.") ".get_class($this)."(Main)->".__FUNCTION__." - Set debug level to $dbg_level.</pre>\n";
            }
            $this->dbg_level = $dbg_level;
        }
        
        
        if($this->dbg_level >= 1)
        {
            if($this->dbg_intern_main > 0)
            {
                echo "<pre>(Line: ".__LINE__.") ".get_class($this)."(Main)->".__FUNCTION__." - send first log entry.</pre>\n";
            }
            
            if($this->dbg_level > 1)
            {
                $this->debug(get_class($this)." - Created Object");
            }
            
            if(isset($GLOBALS["_COOKIE"]["PHPSESSID"]))
            {
                $this->logging_sessid = $GLOBALS["_COOKIE"]["PHPSESSID"];
            }
        }
        
        // if we got an integer and it's not the same as it was already set
        if(is_int($support_level) === TRUE && $this->support_level !== $support_level)
        {
            $this->support_level = $support_level;
        }
        
        if($this->support_level >= 1 && $this->error_occured(100) === TRUE)
        {
            // if the class supports error reporting (level >= 1) remove error
            $this->delete_error(100);
        }
        
        
        // get the timestamp
        $this->Performance_timestamp = $this->msec();
        
    }
    
    
    
    ////////////////////////////////////////////////////////////////////////////
    // SET methods to set class Options
    ////////////////////////////////////////////////////////////////////////////
    
    
    
    ////////////////////////////////////////////////////////////////////////////
    // GET methods to get class Options
    ////////////////////////////////////////////////////////////////////////////
    
    
    
    ////////////////////////////////////////////////////////////////////////////
    // PRIVATE methods of the class
    ////////////////////////////////////////////////////////////////////////////
    
    
    
    ////////////////////////////////////////////////////////////////////////////
    // PROTECTED methods of the class
    ////////////////////////////////////////////////////////////////////////////
    
    
    
    /**
     * Check prerequisite classes and functions for the class
     * 
     * This method is used to check the prerequisits according to a list of
     * required functions and a list of required classes. If all the classes
     * and functions are available, the method returnes TRUE. If one or more
     * required functions or classes is not available while this method is
     * called, The method returns a list with all classes and functions that
     * are not available.
     * 
     * @access protected
     * 
     * @array  param $function_list The list of required functions
     * @param  array $class_list    The list of required classes
     * 
     * @return mixed TRUE           if all prerequisits are available or a list of missing functions and classes
    **/
    protected function check_prerequisites($function_list = NULL, $class_list = NULL)
    {
        $function_missing = array();
        $class_missing = array();
        
        if($this->dbg_intern_main > 0)
        {
            echo "<pre>(Line: ".__LINE__.") ".get_class($this)."(Main)->".__FUNCTION__." - LIST FUNCTIONS: \n".print_r($function_list, TRUE)."</pre>\n";
        }
        
        if($this->dbg_intern_main > 0)
        {
            echo "<pre>(Line: ".__LINE__.") ".get_class($this)."(Main)->".__FUNCTION__." - LIST CLASSES: \n".print_r($class_list, TRUE)."</pre>\n";
        }
        
        //
        // Check pre-requisit functions
        //
        $result = TRUE;
        if($function_list !== NULL)
        {
            if($this->dbg_intern_main > 0)
            {
                echo "<pre>(Line: ".__LINE__.") ".get_class($this)."(Main)->".__FUNCTION__." - Check function list ...</pre>\n";
            }
            
            //
            // Checking function requirements
            //
            foreach($function_list as $function)
            {
                // if function does not exist
                if(function_exists($function) === FALSE)
                {
                    // add it to the list of missing functions and set result to NOK
                    $function_missing[] = $function;
                    $result = FALSE;
                }
            }
        }
        if($this->dbg_intern_main > 0)
        {
            echo "<pre>(Line: ".__LINE__.") ".get_class($this)."(Main)->".__FUNCTION__." - Check function list ... DONE</pre>\n";
        }
        //
        // Check pre-requisit functions - END
        //
        
        
        
        //
        // Check pre-requisit classes
        //
        if($class_list !== NULL)
        {
            if($this->dbg_intern_main > 0)
            {
                echo "<pre>(Line: ".__LINE__.") ".get_class($this)."(Main)->".__FUNCTION__." - Check class list ...</pre>\n";
            }
            
            //
            // Checking class requirements
            //
            foreach($class_list as $class)
            {
                // check if the class exists
                if(class_exists($class) === FALSE)
                {
                    $class_missing[] = $class;
                    $result = FALSE;
                }
            }
        }
        if($this->dbg_intern_main > 0)
        {
            echo "<pre>(Line: ".__LINE__.") ".get_class($this)."(Main)->".__FUNCTION__." - Check class list ... DONE</pre>\n";
        }
        //
        // Check pre-requisit classes - END
        //
        
        
        
        //
        // Check result and define error description if needed
        //
        if($result === FALSE)
        {
            if($this->dbg_intern_main > 0)
            {
                echo "<pre>(Line: ".__LINE__.") ".get_class($this)."(Main)->".__FUNCTION__." - Check Failed (Missing pre-requisits)</pre>\n";
            }
            
            $missing_result['functions'] = array();
            $missing_result['classes'] = array();
            
            // return with error
            $this->errtext = "Pre-requisits check ... FAILED.\n";
            if($function_list !== NULL)
            {
                $this->errtext .= "   Missing functions: ".join(", ", $function_missing)."\n";
                $missing_result['functions'] = $function_missing;
            }
            
            if($class_list !== NULL)
            {
                $this->errtext .= "   Missing classes: ".join(", ", $class_missing)."\n";
                $missing_result['classes'] = $class_missing;
            }
            
            if($this->dbg_intern_main > 0)
            {
                echo "<pre>(Line: ".__LINE__.") ".get_class($this)."(Main)->".__FUNCTION__." - Warning message: ".$this->errtext."</pre>\n";
            }

            $this->warning($this->errtext);
            
            // return missing pre-requisit
            return $missing_result;
        }
        //
        // Check result and define error description if needed - END
        //
        
        
        
        // return true to indicate sucessful check
        if($this->dbg_intern_main > 0)
        {
            echo "<pre>(Line: ".__LINE__.") ".get_class($this)."(Main)->".__FUNCTION__." - Return Success (TRUE)</pre>\n";
        }
        
        return TRUE;
        
    }
    
    
    ////////////////////////////////////////////////////////////////////////////
    // PROTECTED methods of the class - for Debug_Logging
    ////////////////////////////////////////////////////////////////////////////
    
    /**
     * Logging functionality for type INFO
     * 
     * The logging functionality that will pass the log message on to the
     * Debug_Logging object if available. If this is not available, the logging
     * will be done from the method itself.
     * 
     * @see Debug_Logging::info()
     * 
     * @access protected
     * 
     * @param string $msg_text            Text message to be logged
     * @param int    $add_traceback_index Additional index to trace back
    **/
    protected function info($msg_text, $add_traceback_index = 0)
    {
        // pass it on to the log_as_type method for processing
        $this->log_as_type('INFO', $msg_text, $add_traceback_index + 1);
        
        return;
    }
    
    
    
    /**
     * Logging functionality for type DEBUG
     * 
     * The logging functionality that will pass the log message on to the
     * Debug_Logging object if available. If this is not available, the logging
     * will be done from the method itself.
     * 
     * @see Debug_Logging::debug()
     * 
     * @access protected
     * 
     * @param string $msg_text            Text message to be logged
     * @param int    $add_traceback_index Additional index to trace back
    **/
    protected function debug($msg_text, $add_traceback_index = 0)
    {
        // pass it on to the log_as_type method for processing
        $this->log_as_type('DEBUG', $msg_text, $add_traceback_index + 1);
        
        return;
    }
    
    
    
    /**
     * Logging functionality for type WARN
     * 
     * The logging functionality that will pass the log message on to the
     * Debug_Logging object if available. If this is not available, the logging
     * will be done from the method itself.
     * 
     * @see Debug_Logging::warning()
     * 
     * @access protected
     * 
     * @param string $msg_text            Text message to be logged
     * @param int    $add_traceback_index Additional index to trace back
    **/
    protected function warning($msg_text, $add_traceback_index = 0)
    {
        // pass it on to the log_as_type method for processing
        $this->log_as_type('WARN', $msg_text, $add_traceback_index + 1);
        
        return;
    }
    
    
    
    /**
     * Logging functionality for type DEBUG2
     * 
     * The logging functionality that will pass the log message on to the
     * Debug_Logging object if available. If this is not available, the logging
     * will be done from the method itself.
     * 
     * @see Debug_Logging::debug2()
     * 
     * @access protected
     * 
     * @param string $msg_text            Text message to be logged
     * @param int    $add_traceback_index Additional index to trace back
    **/
    protected function debug2($msg_text, $add_traceback_index = 0)
    {
        // pass it on to the log_as_type method for processing
        $this->log_as_type('DEBUG2', $msg_text, $add_traceback_index + 1);
        
        return;
    }
    
    
    
    /**
     * Logging functionality for type ERROR
     * 
     * The logging functionality that will pass the log message on to the
     * Debug_Logging object if available. If this is not available, the logging
     * will be done from the method itself.
     * 
     * @see Debug_Logging::error()
     * 
     * @access protected
     * 
     * @param string $msg_text            Text message to be logged
     * @param int    $add_traceback_index Additional index to trace back
    **/
    protected function error($msg_text, $add_traceback_index = 0)
    {
        // pass it on to the log_as_type method for processing
        $this->log_as_type('ERROR', $msg_text, $add_traceback_index + 1);
        
        return;
    }
    
    
    
    /**
     * Logging functionality for type PERFORM
     * 
     * The logging functionality that will pass the log message on to the
     * Debug_Logging object if available. If this is not available, the logging
     * will be done from the method itself.
     * 
     * @see Debug_Logging::performance()
     * 
     * @access protected
     * 
     * @param string $msg_text            Text message to be logged
     * @param string $start               Defines if the time measurement should be "start" or "stop"
     * @param int    $add_traceback_index Additional index to trace back
    **/
    protected function performance($msg_text, $start="stop", $add_traceback_index = 0)
    {
        if($this->initialized === FALSE)
        {
            $this->Main_init();
        }
        
        // check if debug is enabled
        if($this->dbg_level <= 0)
        {
            if($this->dbg_intern_main > 0)
            {
                echo "<pre>(Line: ".__LINE__.") ".get_class($this)."(Main)->".__FUNCTION__." - \$this->dbg_level <= 0 (lower 1)</pre>\n";
            }
            
            return;
        }
        
        
        // if method debug in class exists, call it for logging
        if($this->logging_object !== NULL && method_exists(get_class($this->logging_object), "performance"))
        {
            if($this->dbg_intern_main > 0)
            {
                echo "<pre>(Line: ".__LINE__.") ".get_class($this)."(Main)->".__FUNCTION__." - Call \$this->logging_object->performance()</pre>\n";
            }
            $this->logging_object->performance($msg_text, $start, $add_traceback_index + 1);
            
            return;
        }
            
        
        // if start of measurement, only note the time
        if($start === "stop")
        {
            if($this->dbg_intern_main > 0)
            {
                echo "<pre>(Line: ".__LINE__.") ".get_class($this)."(Main)->".__FUNCTION__." - Log manually</pre>\n";
            }

            // Get the mocro time stamp and format it
            $ts_temp = explode(' ', microtime());
            $msec = explode('.', $ts_temp[0]);
            $micro_ts = $ts_temp[1].".".$msec[1];
            
            // calculate and format time difference
            $time_diff = $micro_ts - $this->micro_timestamp_perform;
            $time_diff = number_format($time_diff, 4, '.', '');
            if($time_diff < 10)
            {
                $time_diff = '  '.$time_diff;
            }
            if($time_diff < 100)
            {
                $time_diff = ' '.$time_diff;
            }
            
            // get traceback informations
            $infoarray = debug_backtrace();
            $tbi = 0;
            $file = basename($infoarray[$tbi]["file"]);
            $line = $infoarray[$tbi]["line"];
            $function = "---";
            if(count($infoarray) > ($tbi + 1))
            {
                $function = $infoarray[$tbi + 1]["function"];
            }
            
            if($function === "include" || $function === "include_once")
            {
                $function = "---";
            }
            
            // write to the logfile
            error_log(date("Y-m-d@H:i:s").".".$this->msec()." | $time_diff | ".$this->logging_sessid." | PERFORM | $line | $file | $function | $msg_text\n", 3, dirname(__FILE__).$this->log_path.$file."_perf.log");
            
            return;
        }
        else if($start === "start")
        {
            // Get the mocro time stamp and format it
            $ts_temp = explode(' ', microtime());
            $msec = explode('.', $ts_temp[0]);
            $micro_ts = $ts_temp[1].".".$msec[1];
            
            $this->micro_timestamp_perform = $micro_ts;
            
            return;
        }
    }
    
    
    
    /**
     * Logging functionality for additional types
     * 
     * The logging functionality that will pass the log message on to the
     * Debug_Logging object if available. If this is not available, the logging
     * will be done from the method itself.
     * 
     * @see Debug_Logging::log_as_type()
     * 
     * @access protected
     * 
     * @param string $type                Log type as it apears in the log
     * @param string $msg_text            Text message to be logged
     * @param int    $add_traceback_index Additional index to trace back
    **/
    protected function log_as_type($type, $msg_text, $add_traceback_index = 0)
    {
        if($this->initialized === FALSE)
        {
            $this->Main_init();
        }
        
        // check if debug is enabled
        if($this->dbg_level <= 0)
        {
            if($this->dbg_intern_main > 0)
            {
                echo "<pre>(Line: ".__LINE__.") ".get_class($this)."(Main)->".__FUNCTION__." - \$this->dbg_level <= 0 (lower 1)</pre>\n";
            }
            
            return;
        }
        
        // check the current debug level and return without logging if
        // the message is not included in the given level.
        switch($type)
        {
            case 'INFO':
            case 'DEBUG':
                if($this->dbg_level < 1)
                {
                    return;
                }
                break;

            case 'DEBUG2':
                if($this->dbg_level < 2)
                {
                    return;
                }
                break;

            case 'DEBUG_ARRAY':
            case 'DEBUG2_ARRAY':
                if($this->dbg_level < 3)
                {
                    return;
                }
                break;

            case 'WARN':
            case 'ERROR':
                break;

            default:
                // catch all not defined types
                if($this->dbg_level < 2)
                {
                    return;
                }
                break;
        }
        
        // if method debug in class exists, call it for logging
        if($this->logging_object !== NULL && method_exists(get_class($this->logging_object), "$type"))
        {
            if($this->dbg_intern_main > 0)
            {
                echo "<pre>(Line: ".__LINE__.") ".get_class($this)."(Main)->".__FUNCTION__." - Call \$this->logging_object->log_as_type()</pre>\n";
            }
            $this->logging_object->log_as_type($type, $msg_text, $add_traceback_index + 1);
            
            return;
        }
        
        
        if($this->dbg_intern_main > 0)
        {
            echo "<pre>(Line: ".__LINE__.") ".get_class($this)."(Main)->".__FUNCTION__." - Log manually</pre>\n";
        }
        // get traceback informations
        $infoarray = debug_backtrace();
        $tbi = 1;
        $file = basename($infoarray[$tbi]["file"]);
        $line = $infoarray[$tbi]["line"];
        $function = "---";
        if(count($infoarray) > ($tbi + 1))
        {
            $function = $infoarray[$tbi + 1]["function"];
        }
        
        if($function === "include" || $function === "include_once")
        {
            $function = "---";
        }
        
        // write to the logfile
        $type_length = strlen($type);
        if($type_length<6)
        {
            for($i=0; $i<6-$type_length; ++$i)
            {
                $type = $type.' ';
            }
        }

        $log_fields = date("Y-m-d@H:i:s").".".$this->msec()." |   -.---- | ".$this->logging_sessid." | $type   | $line | $file | $function | ";
        $log_content = '';
        if(preg_match("/\n/", $msg_text) <= 0)
        {
            $log_content .= $log_fields.$msg_text."\n";
        }
        else
        {
            $msg_lines = preg_split("/\n/", $msg_text);
            foreach($msg_lines as $line_text)
            {
                $log_content .= $log_fields.$line_text."\n";
            }
        }
        
        error_log($log_content, 3, dirname(__FILE__).$this->log_path.$file.".log");
        
        return;
    }
    
    
    
    /**
     * Microtime seconds
     * 
     * This method will return the current microtime. The microtime timestamp
     * contains the unix timestamp (seconds since 1970) and the micro seconds.
     * This timestamp is returned as float.
     * 
     * @access protected
     * 
     * @return float Microseconds timestamp as float.
    **/
    protected function msec()
    {
        // get the microtime in format "0.######## ##########"
        $timestamp = explode(' ', microtime());
        // remove the "0." from the microseconds
        $msec = explode('.', $timestamp[0]);
        // truncate to 4 digits
        $msec_str = substr($msec[1], 0, 4);
        
        return $msec_str;
    }
    
    

    ////////////////////////////////////////////////////////////////////////////
    // PROTECTED methods of the class - for Debug_Profiler
    ////////////////////////////////////////////////////////////////////////////



    /**
     * Start time measurement and log the event
     * 
     * This method starts a named time measurement. During the time measurement 
     * start, a log message is written to the configured log target. To stop 
     * the named measurement, the timer_stop() must be called.
     * 
     * @see timer_stop()
     * 
     * @param string $timer_name The name for this time measurement
     * @param string $message The text message to be logged to the start of the measurment
     * @param int    $add_traceback_index Additional index to trace back
    **/
    protected function timer_start($timer_name = '*MAIN*', $message='-', $add_traceback_index = 0)
    {
        if($this->initialized === FALSE)
        {
            $this->Main_init();
        }
        
        if($this->profiler_object !== NULL && method_exists(get_class($this->profiler_object), "timer_start") === TRUE)
        {
            $this->profiler_object->timer_start($timer_name, $message, $add_traceback_index + 1);
        }
        else
        {
            $this->warning(__CLASS__.':'.__FUNCTION__.' - Debug_Profiler object missing');
        }
        return;
    }
    
    

    /**
     * Stop time measurement and log the event
     * 
     * This method stops a named time measurement. During the time measurement 
     * stop, a log message is written to the configured log target. Only a 
     * started time measurement, can be stopped. To start a measurement, 
     * timer_start() must be called.
     * 
     * @see timer_start()
     * 
     * @param string $timer_name The name for this time measurement
     * @param string $message The text message to be logged to the start of the measurment
     * @param int    $add_traceback_index Additional index to trace back
    **/
    protected function timer_stop($timer_name = '*MAIN*', $message = '-', $add_traceback_index = 0)
    {
        if($this->initialized === FALSE)
        {
            $this->Main_init();
        }
        
        if($this->profiler_object !== NULL && method_exists(get_class($this->profiler_object), "timer_stop") === TRUE)
        {
            return $this->profiler_object->timer_stop($timer_name, $message, $add_traceback_index + 1);
        }
        else
        {
            $this->warning(__CLASS__.':'.__FUNCTION__.' - Debug_Profiler object missing');
        }
        return FALSE;
    }
    
    

    /**
     * Start memory measurement and log the event
     * 
     * This method starts a named memory measurement. During the memory 
     * measurement start, a log message is written to the configured log 
     * target. To stop the named measurement, the memory_stop() must be called.
     * 
     * @see memory_stop()
     * 
     * @param string $timer_name The name for this time measurement
     * @param string $message The text message to be logged to the start of the measurment
     * @param int    $add_traceback_index Additional index to trace back
    **/
    protected function memory_start($timer_name = '*MAIN*', $message='-', $add_traceback_index = 0)
    {
        if($this->initialized === FALSE)
        {
            $this->Main_init();
        }
        
        if($this->profiler_object !== NULL && method_exists(get_class($this->profiler_object), "memory_start") === TRUE)
        {
            $this->profiler_object->memory_start($timer_name, $message, $add_traceback_index + 1);
        }
        else
        {
            $this->warning(__CLASS__.':'.__FUNCTION__.' - Debug_Profiler object missing');
        }
        return;
    }
    
    

    /**
     * Stop memory measurement and log the event
     * 
     * This method stops a named memory measurement. During the memory 
     * measurement stop, a log message is written to the configured log 
     * target. Only a started time measurement, can be stopped. To start a 
     * measurement, memory_start() must be called.
     * 
     * @see memory_start()
     * 
     * @param string $timer_name The name for this time measurement
     * @param string $message The text message to be logged to the start of the measurment
     * @param int    $add_traceback_index Additional index to trace back
    **/
    protected function memory_stop($timer_name = '*MAIN*', $message = '-', $add_traceback_index = 0)
    {
        if($this->initialized === FALSE)
        {
            $this->Main_init();
        }
        
        if($this->profiler_object !== NULL && method_exists(get_class($this->profiler_object), "memory_stop") === TRUE)
        {
            return $this->profiler_object->memory_stop($timer_name, $message, $add_traceback_index + 1);
        }
        else
        {
            $this->warning(__CLASS__.':'.__FUNCTION__.' - Debug_Profiler object missing');
        }
        return FALSE;
    }
    
    

    /**
     * Log memory usage
     * 
     * This method log the current memory usage. The log message is written to 
     * the configured log target.
     * 
     * @param string $message Text message to be logged with the timer information
     * @param int    $add_traceback_index Additional index to trace back
    **/
    public function memory_show($message = "-", $add_traceback_index = 0)
    {
        if($this->initialized === FALSE)
        {
            $this->Main_init();
        }
        
        if($this->profiler_object !== NULL && method_exists(get_class($this->profiler_object), "memory_show") === TRUE)
        {
            return $this->profiler_object->memory_show($message, $add_traceback_index + 1);
        }
        else
        {
            $this->warning(__CLASS__.':'.__FUNCTION__.' - Debug_Profiler object missing');
        }
        return FALSE;
    }



    ////////////////////////////////////////////////////////////////////////////
    // PROTECTED methods of the class - for Error_Reporting
    ////////////////////////////////////////////////////////////////////////////



    /**
     * Add new error to list of errors
     * 
     * This method will append a new error to the error list of the object. At the same
     * time it will be reported to the defined debug log. This method is protected since
     * only the object itself is allowed to generate errors.
     * 
     * @access protected
     * 
     * @param integer $code the error code to report
     * @param string  $text the explanation text this error code has
     * 
     * @return boolean returns true if the parameters were correctly set, false if not
    **/
    protected function report_error($code, $text)
    {
        // initially we assume that all parameters are ok, at the end we check if all went ok
        $input_is_ok = TRUE;
        $reason = NULL;
        
        // the error code has to be of type integer and within the bounds 100...999 (3 digits)
        if(is_int($code) === FALSE)
        {
            $reason = 'error code is not of type int';
            $input_is_ok = FALSE;
        }
        
        if($code < 100 || $code > 999)
        {
            $reason = 'error code is out of bounds, must be between 100 and 999 (inclusive)';
            $input_is_ok = FALSE;
        }
        
        // the text has to be of type string, we have no other restrictions for it
        if(is_string($text) === FALSE)
        {
            $reason = 'error text is not of type string';
            $input_is_ok = FALSE;
        }
        
        if($input_is_ok === FALSE)
        {
            // if something went wrong so far we need to exit to protect the object
            $this->error('reported error not saved, invalid parameters! reason: '.$reason.' code:'.print_r($code, TRUE).' text:'.print_r($text, TRUE));
            return FALSE;
        }
        
        // push the current error and the end of the stack
        $this->_error_list[] = array('code' => $code, 'text' => $text);
        
        // now report the valid error to the log facility, 1 means we want the caller function reported, not this one
        // $this->warning($code.':'.$text, 1);
        $this->debug('ERROR WARNING: '.$code.':'.$text, 1);
        
        // all went well, report back the fact as "TRUE"
        return TRUE;
    }
    
    
    
    ////////////////////////////////////////////////////////////////////////////
    // PUBLIC methods of the class
    ////////////////////////////////////////////////////////////////////////////
    
    
    
    /**
     * Check if an error occured so far
     * 
     * Reports back if there was an error so far within the object.
     * If no errors happened returns FALSE else returns TRUE.
     * 
     * @access public
     * 
     * @return boolean TRUE or FALSE depending on if an error occured or not
    **/
    final public function has_error()
    {
        if($this->support_level_logged === FALSE)
        {
            // if someone overrided the constructor AND forgot to add support for error reporting...
            if($this->support_level < 1 && $this->error_occured(100) === FALSE)
            {
                $this->report_error(100, 'This class does not support error reporting: '.get_class($this));
                $this->support_level_logged = TRUE;
            }
        }
        
        // if an error exists
        if(count($this->_error_list) > 0)
        {
            return TRUE;
        }
        else
        {
           return FALSE;
        }
    }
    
    
    
    /**
     * Get the last error code that occured
     * 
     * This method returns to the caller the last error code that occured if the list
     * has errors else it will return NULL.
     * 
     * @access public
     * 
     * @return integer|NULL last error code
    **/
    final public function get_last_error_code()
    {
        if(count($this->_error_list) > 0)
        {
            $last_error = end($this->_error_list);
            
            return $last_error['code'];
        }
        else
        {
            return NULL;
        }
    }
    
    
    
    /**
     * Get all errors
     * 
     * Returns the array of errors that occured so far if any. If none occured the array will
     * be simply empty.
     * 
     * @access public
     * 
     * @return array an array of errors. empty or populated if there are any existing only
    **/
    final public function get_all_errors()
    {
        return $this->_error_list;
    }
    
    
    
    /**
     * Reset errors
     * 
     * Clears/resets the errors list so as if nothing has happened so far.
     * Resetting is only done if the support flag is set.
     * 
     * @access public
    **/
    final public function reset_errors()
    {
        if($this->support_level > 0)
        {
            $this->_error_list = array();
        }
    }
    
    
    
    /**
     * Finds out if a specific error occured
     * 
     * This method iterates through the _error_list and tells
     * us if the specific error_code is there or not.
     * 
     * @access public
     * 
     * @param  integer $code The error code to search for.
     * 
     * @return boolean       TRUE if found, false if not.
    **/
    final public function error_occured($code)
    {
        $error_count = count($this->_error_list);
        $found = FALSE;
        for($i=0; $i<$error_count; ++$i)
        {
            if($this->_error_list[$i]['code'] === $code)
            {
                $found = TRUE;
            }
        }
        
        return $found;
    }
    
    
    
    /**
     * Delete an error from the error list.
     * 
     * This method deletes from the _error_list all occurencies
     * of the specific error code.
     * 
     * @access public
     * 
     * @param  integer $code The error code to delete.
    **/
    final public function delete_error($code)
    {
        // iterates through the _error_list and deletes
        // all occurencies of this error code
        $error_count = count($this->_error_list);
        for($i=0; $i<$error_count; ++$i)
        {
            if($this->_error_list[$i]['code'] === $code)
            {
                unset($this->_error_list[$i]);
            }
        }
    }
    
    
    
    /**
     * Get the last error that occured
     * 
     * This method returns to the caller the last error that occured if the list
     * has errors. If the list has no errors then an empty array is returned.
     * 
     * @access public
     * 
     * @return array last error or empty array
    **/
    public function get_last_error()
    {
        if($this->support_level_logged === FALSE)
        {
            // if someone overrided the constructor AND forgot to add support for error reporting...
            if($this->support_level < 1 && $this->error_occured(100) === FALSE)
            {
                $this->report_error(100, 'This class does not support error reporting: '.get_class($this));
                $this->support_level_logged = TRUE;
            }
        }
        
        if(count($this->_error_list) > 0)
        {
            return end($this->_error_list);
        }
        else
        {
            $this->warning("Please make sure that there is an error before accessing get_last_error()");
            
            return array();
        }
    }



}



?>