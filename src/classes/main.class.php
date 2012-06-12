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
 * @version 0.14
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 *
 * @todo remove workaround for WARN messages at method warning()
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
     * @access public
     * @var integer
    **/
    public $dbg_intern_main     = 0;

    /**
     * @ignore
     * To define the log path if no logging object is provided (autonomous logging)
     * @access public
     * @var string
    **/
    public $log_path            = "/../../log/";

    // the log level defined during initialisation
    protected $dbg_level        = -1;           // set to 1 to enable debug output function calls
    // to hold the logging object provided during initialisation
    protected $logging_object   = NULL;

    // The session variable for autonomous logging
    private $logging_sessid     = "";

    // To find out if the class was initialized or not
    private $initialized        = FALSE;

    // error reporting support
    public  $support_level = 0;
    private $_error_list = FALSE;

    ////////////////////////////////////////////////////////////////////////////
    // CONSTRUCTOR & DESCTRUCTOR methods of the class
    ////////////////////////////////////////////////////////////////////////////

    /**
     * Initialisation (constructor equivalent)
     *
     * In this method the initialisation is done. It handles the given
     * Debug_Logging object (when provided) and the log level.
     *
     * @param int $dbg_level Debug log level
     * @param object $debug_object Debug object to send log messages to
    ***/
    public function Main_init($dbg_level=-1, &$log_object=null)
    {
        $this->initialized = TRUE;

        if(is_object($log_object) === TRUE)
        {
            if($this->dbg_intern_main > 0) echo "<pre>(Line: ".__LINE__.") ".get_class($this)."(Main)->".__FUNCTION__." - log_object = ". (int) is_object($this->logging_object)."</pre>\n";
            $this->logging_object =& $log_object;

            if($this->dbg_intern_main > 0) echo "<pre>(Line: ".__LINE__.") ".get_class($this)."(Main)->".__FUNCTION__." - dbg_level = $dbg_level</pre>\n";
            $this->dbg_level = $dbg_level;

        }

        if($this->dbg_intern_main > 0) echo "<pre>(Line: ".__LINE__.") ".get_class($this)."(Main)->".__FUNCTION__." - check debug level setting.</pre>\n";
        if($dbg_level > -1)
        {
            if($this->dbg_intern_main > 0) echo "<pre>(Line: ".__LINE__.") ".get_class($this)."(Main)->".__FUNCTION__." - Set debug level to $dbg_level.</pre>\n";
            $this->dbg_level = $dbg_level;
        }

        if($this->dbg_level >= 1)
        {
            if($this->dbg_intern_main > 0) echo "<pre>(Line: ".__LINE__.") ".get_class($this)."(Main)->".__FUNCTION__." - send first log entry.</pre>\n";
            if($this->dbg_level > 1) $this->debug(get_class($this)." - Created Object");

            if(isset($GLOBALS["_COOKIE"]["PHPSESSID"]))
            {
                $this->logging_sessid = $GLOBALS["_COOKIE"]["PHPSESSID"];
            }
        }

        if($this->support_level < 1)
        {
            $this->report_error(0, "This class does not support error reporting");
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
     * @param array $function_list The list of required functions
     * @param array $class_list The list of required classes
     * @return mixed TRUE if all prerequisits are available or a list of missing functions and classes
    **/
    protected function check_prerequisites($function_list = NULL, $class_list = NULL)
    {
        $function_missing = $class_missing = array();

        if($this->dbg_intern_main > 0) echo "<pre>(Line: ".__LINE__.") ".get_class($this)."(Main)->".__FUNCTION__." - LIST FUNCTIONS: \n".print_r($function_list, TRUE)."</pre>\n";
        if($this->dbg_intern_main > 0) echo "<pre>(Line: ".__LINE__.") ".get_class($this)."(Main)->".__FUNCTION__." - LIST CLASSES: \n".print_r($class_list, TRUE)."</pre>\n";

        //
        // Check pre-requisit functions
        //
        $result = TRUE;
        if($function_list !== NULL)
        {
            if($this->dbg_intern_main > 0) echo "<pre>(Line: ".__LINE__.") ".get_class($this)."(Main)->".__FUNCTION__." - Check function list ...</pre>\n";

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
        if($this->dbg_intern_main > 0) echo "<pre>(Line: ".__LINE__.") ".get_class($this)."(Main)->".__FUNCTION__." - Check function list ... DONE</pre>\n";
        //
        // Check pre-requisit functions - END
        //

        //
        // Check pre-requisit classes
        //
        if($class_list !== NULL)
        {
            if($this->dbg_intern_main > 0) echo "<pre>(Line: ".__LINE__.") ".get_class($this)."(Main)->".__FUNCTION__." - Check class list ...</pre>\n";

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
        if($this->dbg_intern_main > 0) echo "<pre>(Line: ".__LINE__.") ".get_class($this)."(Main)->".__FUNCTION__." - Check class list ... DONE</pre>\n";
        //
        // Check pre-requisit classes - END
        //



        //
        // Check result and define error description if needed
        //
        if($result === FALSE)
        {
            if($this->dbg_intern_main > 0) echo "<pre>(Line: ".__LINE__.") ".get_class($this)."(Main)->".__FUNCTION__." - Check Failed (Missing pre-requisits)</pre>\n";

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

            if($this->dbg_intern_main > 0) echo "<pre>(Line: ".__LINE__.") ".get_class($this)."(Main)->".__FUNCTION__." - Warning message: ".$this->errtext."</pre>\n";
            $this->warning($this->errtext);

            // return missing pre-requisit
            return $missing_result;
        }
        //
        // Check result and define error description if needed - END
        //



        // return true to indicate sucessful check
        if($this->dbg_intern_main > 0) echo "<pre>(Line: ".__LINE__.") ".get_class($this)."(Main)->".__FUNCTION__." - Return Success (TRUE)</pre>\n";
        return TRUE;

    }

    /**
     * Logging functionality for type INFO
     *
     * The logging functionality that will pass the log message on to the
     * Debug_Logging object if available. If this is not available, the logging
     * will be done from the method itself.
     *
     * @see Debug_Logging::info()
     *
     * @param string $msg_text Text message to be logged
     * @param int $add_traceback_index Additional index to trace back
    **/
    protected function info($msg_text, $add_traceback_index=0)
    {
        if($this->initialized === FALSE) $this->Main_init();

        // check if debug is enabled
        if($this->dbg_level <= 0)
        {
            if($this->dbg_intern_main > 0) echo "<pre>(Line: ".__LINE__.") ".get_class($this)."(Main)->".__FUNCTION__." - \$this->dbg_level <= 0 (lower 1)</pre>\n";
            return;
        }


        // if method debug in class exists, call it for logging
        if($this->logging_object != null && method_exists(get_class($this->logging_object), "info"))
        {
            if($this->dbg_intern_main > 0) echo "<pre>(Line: ".__LINE__.") ".get_class($this)."(Main)->".__FUNCTION__." - Call \$this->logging_object->info()</pre>\n";
            $this->logging_object->info($msg_text, $add_traceback_index + 1);
            return;
        }


        if($this->dbg_intern_main > 0) echo "<pre>(Line: ".__LINE__.") ".get_class($this)."(Main)->".__FUNCTION__." - Log manually</pre>\n";
        // get traceback informations
        $infoarray = debug_backtrace();
        $tbi = 1;
        $file = basename($infoarray[$tbi]["file"]);
        $line = $infoarray[$tbi]["line"];
        $function = "---";
        if(count($infoarray) > ($tbi + 1)) $function = $infoarray[$tbi + 1]["function"];

        if($function === "include" || $function === "include_once")
        {
            $function = "---";
        }

        // write to the logfile
        error_log(date("Y-m-d@H:i:s").".".$this->msec()." |   -.---- | ".$this->logging_sessid." | INFO    | $line | $file | $function | $msg_text\n", 3, dirname(__FILE__).$this->log_path.$file.".log");
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
     * @param string $msg_text Text message to be logged
     * @param int $add_traceback_index Additional index to trace back
    **/
    protected function debug($msg_text, $add_traceback_index=0)
    {
        if($this->initialized === FALSE) $this->Main_init();

        // check if debug is enabled
        if($this->dbg_level <= 0)
        {
            if($this->dbg_intern_main > 0) echo "<pre>(Line: ".__LINE__.") ".get_class($this)."(Main)->".__FUNCTION__." - \$this->dbg_level <= 0 (lower 1)</pre>\n";
            return;
        }


        // if method debug in class exists, call it for logging
        if($this->logging_object != null && method_exists(get_class($this->logging_object), "debug"))
        {
            if($this->dbg_intern_main > 0) echo "<pre>(Line: ".__LINE__.") ".get_class($this)."(Main)->".__FUNCTION__." - Call \$this->logging_object->debug()</pre>\n";
            $this->logging_object->debug($msg_text, $add_traceback_index + 1);
            return;
        }


        if($this->dbg_intern_main > 0) echo "<pre>(Line: ".__LINE__.") ".get_class($this)."(Main)->".__FUNCTION__." - Log manually</pre>\n";
        // get traceback informations
        $infoarray = debug_backtrace();
        $tbi = 1;
        $file = basename($infoarray[$tbi]["file"]);
        $line = $infoarray[$tbi]["line"];
        $function = "---";
        if(count($infoarray) > ($tbi + 1)) $function = $infoarray[$tbi + 1]["function"];

        if($function === "include" || $function === "include_once")
        {
            $function = "---";
        }

        // write to the logfile
        error_log(date("Y-m-d@H:i:s").".".$this->msec()." |   -.---- | ".$this->logging_sessid." | DEBUG   | $line | $file | $function | $msg_text\n", 3, dirname(__FILE__).$this->log_path.$file.".log");
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
     * @param string $msg_text Text message to be logged
     * @param int $add_traceback_index Additional index to trace back
    **/
    protected function warning($msg_text, $add_traceback_index=0)
    {
        if($this->initialized === FALSE) $this->Main_init();

        // check if debug is enabled
        if($this->dbg_level <= 0)
        {
            if($this->dbg_intern_main > 0) echo "<pre>(Line: ".__LINE__.") ".get_class($this)."(Main)->".__FUNCTION__." - \$this->dbg_level <= 0 (lower 1)</pre>\n";
            return;
        }


        // if method debug in class exists, call it for logging
        //if($this->logging_object != null && method_exists(get_class($this->logging_object), "warning"))
        if($this->logging_object != null && method_exists(get_class($this->logging_object), "log_as_type"))
        {
            if($this->dbg_intern_main > 0) echo "<pre>(Line: ".__LINE__.") ".get_class($this)."(Main)->".__FUNCTION__." - Call \$this->logging_object->warning()</pre>\n";
            //$this->logging_object->warning($msg_text, $add_traceback_index + 1);
            $this->logging_object->log_as_type("WARN", $msg_text, $add_traceback_index + 1);
            return;
        }


        if($this->dbg_intern_main > 0) echo "<pre>(Line: ".__LINE__.") ".get_class($this)."(Main)->".__FUNCTION__." - Log manually</pre>\n";
        // get traceback informations
        $infoarray = debug_backtrace();
        $tbi = 1;
        $file = basename($infoarray[$tbi]["file"]);
        $line = $infoarray[$tbi]["line"];
        $function = "---";
        if(count($infoarray) > ($tbi + 1)) $function = $infoarray[$tbi + 1]["function"];

        if($function === "include" || $function === "include_once")
        {
            $function = "---";
        }

        // write to the logfile
        error_log(date("Y-m-d@H:i:s").".".$this->msec()." |   -.---- | ".$this->logging_sessid." | WARN    | $line | $file | $function | $msg_text\n", 3, dirname(__FILE__).$this->log_path.$file.".log");
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
     * @param string $msg_text Text message to be logged
     * @param int $add_traceback_index Additional index to trace back
    **/
    protected function debug2($msg_text, $add_traceback_index=0)
    {
        if($this->initialized === FALSE) $this->Main_init();

        // check if debug is enabled
        if($this->dbg_level <= 1)
        {
            if($this->dbg_intern_main > 0) echo "<pre>(Line: ".__LINE__.") ".get_class($this)."(Main)->".__FUNCTION__." - \$this->dbg_level <= 1 (lower 2) (value=".$this->dbg_level.")</pre>\n";
            return;
        }


        // if method debug in class exists, call it for logging
        if($this->logging_object != null && method_exists(get_class($this->logging_object), "debug2"))
        {
            if($this->dbg_intern_main > 0) echo "<pre>(Line: ".__LINE__.") ".get_class($this)."(Main)->".__FUNCTION__." - Call \$this->logging_object->debug2()</pre>\n";
            $this->logging_object->debug2($msg_text, $add_traceback_index + 1);
            return;
        }


        if($this->dbg_intern_main > 0) echo "<pre>(Line: ".__LINE__.") ".get_class($this)."(Main)->".__FUNCTION__." - Log manually</pre>\n";
        // get traceback informations
        $infoarray = debug_backtrace();
        $tbi = 1;
        $file = basename($infoarray[$tbi]["file"]);
        $line = $infoarray[$tbi]["line"];
        $function = "---";
        if(count($infoarray) > ($tbi + 1)) $function = $infoarray[$tbi + 1]["function"];

        if($function === "include" || $function === "include_once")
        {
            $function = "---";
        }

        // write to the logfile
        error_log(date("Y-m-d@H:i:s").".".$this->msec()." |   -.---- | ".$this->logging_sessid." | DEBUG2  | $line | $file | $function | $msg_text\n", 3, dirname(__FILE__).$this->log_path.$file.".log");
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
     * @param string $msg_text Text message to be logged
     * @param int $add_traceback_index Additional index to trace back
    **/
    protected function error($msg_text, $add_traceback_index=0)
    {
        if($this->initialized === FALSE) $this->Main_init();

        // check if debug is enabled
        if($this->dbg_level <= 0)
        {
            if($this->dbg_intern_main > 0) echo "<pre>(Line: ".__LINE__.") ".get_class($this)."(Main)->".__FUNCTION__." - \$this->dbg_level <= 0 (lower 1)</pre>\n";
            return;
        }


        // if method debug in class exists, call it for logging
        if($this->logging_object != null && method_exists(get_class($this->logging_object), "error"))
        {
            if($this->dbg_intern_main > 0) echo "<pre>(Line: ".__LINE__.") ".get_class($this)."(Main)->".__FUNCTION__." - Call \$this->logging_object->error()</pre>\n";
            $this->logging_object->error($msg_text, $add_traceback_index + 1);
            return;
        }


        if($this->dbg_intern_main > 0) echo "<pre>(Line: ".__LINE__.") ".get_class($this)."(Main)->".__FUNCTION__." - Log manually</pre>\n";
        // get traceback informations
        $infoarray = debug_backtrace();
        $tbi = 1;
        $file = basename($infoarray[$tbi]["file"]);
        $line = $infoarray[$tbi]["line"];
        $function = "---";
        if(count($infoarray) > ($tbi + 1)) $function = $infoarray[$tbi + 1]["function"];

        if($function === "include" || $function === "include_once")
        {
            $function = "---";
        }

        // write to the logfile
        error_log(date("Y-m-d@H:i:s").".".$this->msec()." |   -.---- | ".$this->logging_sessid." | ERROR   | $line | $file | $function | $msg_text\n", 3, dirname(__FILE__).$this->log_path.$file.".log");
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
     * @param string $msg_text Text message to be logged
     * @param string $start Defines if the time measurement should be "start" or "stop"
     * @param int $add_traceback_index Additional index to trace back
    **/
    protected function performance($msg_text, $start="stop", $add_traceback_index=0)
    {
        if($this->initialized === FALSE) $this->Main_init();

        // check if debug is enabled
        if($this->dbg_level <= 0)
        {
            if($this->dbg_intern_main > 0) echo "<pre>(Line: ".__LINE__.") ".get_class($this)."(Main)->".__FUNCTION__." - \$this->dbg_level <= 0 (lower 1)</pre>\n";
            return;
        }



        // if start of measurement, only note the time
        if($start === "stop")
        {
            // if method debug in class exists, call it for logging
            if($this->logging_object != null && method_exists(get_class($this->logging_object), "performance"))
            {
                if($this->dbg_intern_main > 0) echo "<pre>(Line: ".__LINE__.") ".get_class($this)."(Main)->".__FUNCTION__." - Call \$this->logging_object->performance()</pre>\n";
                $this->logging_object->performance($msg_text, $start, $add_traceback_index + 1);
                return;
            }


            if($this->dbg_intern_main > 0) echo "<pre>(Line: ".__LINE__.") ".get_class($this)."(Main)->".__FUNCTION__." - Log manually</pre>\n";

            // Get the mocro time stamp and format it
            $ts_temp = explode(' ', microtime());
            $msec = explode('.', $ts_temp[0]);
            $micro_ts = $ts_temp[1].".".$msec[1];

            // calculate and format time difference
            $time_diff = $micro_ts - $this->micro_timestamp_perform;
            $time_diff = number_format($time_diff, 4, '.', '');
            if($time_diff < 10) "  ".$time_diff;
            if($time_diff < 100) " ".$time_diff;

            // get traceback informations
            $infoarray = debug_backtrace();
            $tbi = 0;
            $file = basename($infoarray[$tbi]["file"]);
            $line = $infoarray[$tbi]["line"];
            $function = "---";
            if(count($infoarray) > ($tbi + 1)) $function = $infoarray[$tbi + 1]["function"];

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
            // if method debug in class exists, call it for logging
            if($this->logging_object != null && method_exists(get_class($this->logging_object), "performance"))
            {
                if($this->dbg_intern_main > 0) echo "<pre>(Line: ".__LINE__.") ".get_class($this)."(Main)->".__FUNCTION__." - Call \$this->logging_object->performance()</pre>\n";
                $this->logging_object->performance($msg_text, $start, 1);
                return;
            }


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
     * @param string $type Log type as it apears in the log
     * @param string $msg_text Text message to be logged
     * @param int $add_traceback_index Additional index to trace back
    **/
    protected function log_as_type($type, $msg_text, $add_traceback_index=0)
    {
        if($this->initialized === FALSE) $this->Main_init();

        // check if debug is enabled
        if($this->dbg_level <= 0)
        {
            if($this->dbg_intern_main > 0) echo "<pre>(Line: ".__LINE__.") ".get_class($this)."(Main)->".__FUNCTION__." - \$this->dbg_level <= 0 (lower 1)</pre>\n";
            return;
        }


        // if method debug in class exists, call it for logging
        if($this->logging_object != null && method_exists(get_class($this->logging_object), "$type"))
        {
            if($this->dbg_intern_main > 0) echo "<pre>(Line: ".__LINE__.") ".get_class($this)."(Main)->".__FUNCTION__." - Call \$this->logging_object->log_as_type()</pre>\n";
            $this->logging_object->log_as_type($type, $msg_text, $add_traceback_index + 1);
            return;
        }


        if($this->dbg_intern_main > 0) echo "<pre>(Line: ".__LINE__.") ".get_class($this)."(Main)->".__FUNCTION__." - Log manually</pre>\n";
        // get traceback informations
        $infoarray = debug_backtrace();
        $tbi = 1;
        $file = basename($infoarray[$tbi]["file"]);
        $line = $infoarray[$tbi]["line"];
        $function = "---";
        if(count($infoarray) > ($tbi + 1)) $function = $infoarray[$tbi + 1]["function"];

        if($function === "include" || $function === "include_once")
        {
            $function = "---";
        }

        // write to the logfile
        error_log(date("Y-m-d@H:i:s").".".$this->msec()." |   -.---- | ".$this->logging_sessid." | $type   | $line | $file | $function | $msg_text\n", 3, dirname(__FILE__).$this->log_path.$file.".log");
        return;
    }

    /**
     * Microtime seconds
     *
     * This method will return the current microtime. The microtime timestamp
     * contains the unix timestamp (seconds since 1970) and the micro seconds.
     * This timestamp is returned as float.
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
    // PUBLIC methods of the class
    ////////////////////////////////////////////////////////////////////////////

    /**
     * Adds a new error to the error list and reports to the debug log.
     *
     * @param integer $code
     * @param string $text
     * 
     * @todo complete long description
     * @todo return value must be documented
     * @todo remove unused code in the function
     */
    protected function report_error($code, $text)
    {
        // initially we assume that all parameters are ok, at the end we check if all went ok
        $input_is_ok = TRUE;

        if(is_int($code) === FALSE)
        {
            $this->error("error code is not of type int");
            $input_is_ok = FALSE;
        }

        if($code < 100 || $code > 999)
        {
            $this->error("error code is out of bounds, must be between 100 and 999 (inclusive)");
            $input_is_ok = FALSE;
        }

        if(is_string($text) === FALSE)
        {
            $this->error("error text is not of type string");
            $input_is_ok = FALSE;
        }

        if($input_is_ok === FALSE)
        {
            // if something went wrong so far we need to exit to protect the object
            $this->error("reported error not saved, parameters not valid. code:$code text:".print_r($text,TRUE));
            return FALSE;
        }

        // TODO: remove these 3 lines after checking that they are not used 
        // we are safe now for storage, these are used just for quick retrieval
        // $this->_error_code = $code;
        // $this->_error_text = $text;

        // push the current error to the stack
        $this->_error_list[] = array('code' => $code, 'text' => $text);

        // 1 means we want the caller function reported, not this one
        $this->debug("Error reported by class: ".$text, 1);
        return TRUE;
    }

    /**
     * Reports back if there was an error so far within the object.
     * If no errors happened returns FALSE else returns the last error code
     *
     * @return array|boolean last error code or FALSE
     *
     * @todo separate long and short description
     * @todo choose one return type (boolean is suggested)
     *
     */
    public function has_error()
    {
        // if an error exists
        if($this->_error_list != FALSE)
        {
            // get the last error pair from the error list
            $last_error = end($this->_error_list);

            // give back the code part of the pair
            return $last_error['code'];
        }
        return FALSE;
    }

    /**
     * Returns according to input different aspects of the error/error_list
     *
     * @param string $how what type of return the user requests
     * @param string $detail what type of detail the user wants
     * @return NULL|mixed <multitype: boolean, mixed>
     *
     * @todo complete long description
     * @todo return value must be documented
     * @todo remove unused code in the function
     * @todo include valid parameter values in long description for each parameter
     * @todo split this into more consise functions - e.g. get last error and get all errors... see example below function
     */
    public function get_error($how = NULL, $detail = NULL)
    {
        // we only care if the 'how' parameter is set
        if(isset($how) === TRUE && is_string($how) === TRUE)
        {
            switch ($how)
            {
                // if the user wants only the last error
                case 'last':
                    // if the detail flag is set, see what detail the user wants exactly
                    if(isset($detail) === TRUE )
                    {
                        // return NULL if there is no error available
                        if($this->_error_list === FALSE)
                        {
                            return NULL;
                        }
                        else
                        {
                            // there is an error, so return the correct detail
                            $last_error = end($this->_error_list);

                            if($detail === 'code')
                            {
                                return $last_error['code'];
                            }
                            else if($detail === 'text')
                            {
                                return $last_error['code'];
                            }
                        }
                    }
                    // else just return the last error pair
                    else
                    {
                        // just return the last error
                        return $this->_get_last_error();
                    }
                    break;

                // if the user wants all the errors in the list
                case 'all':
                    // if it's populated, then return the list itself
                    if($this->_error_list != FALSE)
                    {
                        return $this->_error_list;
                    }
                    else
                    {
                        return NULL;
                    }
                    break;

                // this returns the amount of errors logged so far
                // for use as a 

                case 'count':
                    return sizeof($this->_error_list);
                    break;
            }
        }
        else
        {
            return $this->_get_last_error();
        }

        return end($this->_error_list);
    }

    /**
     *
     *
     *
     * @param boolean $code include the code in the result
     * @param boolean $text include the text in the result
     * @result (should return a consistent result, boolean, structured array, etc...)
    **/
    public function get_last_error($code = TRUE, $text = TRUE)
    {}

    /**
     *
     *
     *
     * @param boolean $code include the code in the result
     * @param boolean $text include the text in the result
     * @result (should return a consistent result, boolean, structured array, etc...)
    **/
    public function get_all_errors($code = TRUE, $text = TRUE)
    {}

    /**
     * Clears/resets the errors list so as if nothing has happened so far.
     * If the subclass does not support errors (support level = 0) then
     * a request to get_last_error will always return an error. This is done
     * to enforce support for error reporting within the class.
     *
     */
    public function reset_errors()
    {
        if($this->support_level > 0)
        {
            $this->_error_list = FALSE;
        }
    }

    /**
     * Gets the last error pair from the errors list, else FALSE
     *
     * @return mixed
     */
    private function _get_last_error()
    {
        // return last error pair or FALSE if none
        if($this->_error_list === FALSE)
        {
            return FALSE;
        }
        else
        {
            return end($this->_error_list);
        }
    }
}


?>
