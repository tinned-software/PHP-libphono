<?php
/**
 * SQLite3 CONNECTION CLASS
 * 
 * The SQLite3 class is an abstraction class to abstract the differences 
 * between different sql type away. This class is abstracting away the sqlite3 
 * specific methods to a unified interface.
 * 
 * @author Gerhard STEINBEIS ( gerhard . steinbeis [at] tinned-software [.] net )
 * @version 0.27
 * 
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * 
 * @package framework
 * 
**/


include_once(dirname(__FILE__).'/main.class.php');
include_once(dirname(__FILE__).'/debug_logging.interface.php');


/**
 * SQLite3 CONNECTION CLASS
 * 
 * The SQLite3 class is an abstraction class to abstract the differences 
 * between different sql type away. This class is abstracting away the sqlite3 
 * specific methods to a unified interface.
 * 
 * 
 * 
 *     ERROR CODES (connect)
 * 100 ... "Missing connection informations (server, db, user or password)"
 * 101 ... "Connect to sql Server Error. ServerHost = $this->db_host"
 * 102 ... "Connect to DB Error. DBName = $this->db_name"
 *     ERROR CODES (insert)
 * 103 ... "Insert into $tb_name Error. TableName = $tb_name"
 *     ERROR CODES (update)
 * 104 ... "Update $tb_name Error. TableName = $tb_name"
 *     ERROR CODES (delete)
 * 105 ... "Delete From $tb_name Error. TableName = $tb_name"
 *     ERROR CODES (query)
 * 106 ... "Perform Query Error. Query = $string"
 *     ERROR CODES (nums)
 * 107 ... ---
 *     ERROR CODES (objects)
 * 108 ... ---
 *     ERROR CODES (escape_string)
 * 109 ... Parameter not of type string
 * 110 ... No connection to the database
 *     ERROR CODES (_convert_encoding)
 * 111 ... Invalid character encoding in result
 *     ERROR CODES (_check_prerequisits)
 * 201 ... Pre-requisits not fulfilled
 * 
 * 
 **/
class SQLite_3 extends Main
{
    ////////////////////////////////////////////////////////////////////////////
    // PROPERTIES of the class
    ////////////////////////////////////////////////////////////////////////////
    
    
    
    /**
     * This variabte is used to activate the internal debugging code. When set 
     * to 1 success codes will be returned on the same way as error codes. 
     * These codes start from 901 upwards. See class documentation.
     * @access public
     * @var integer
    **/
    public $sucess_debug                = "0";      // To get sucess codes as return codes (9xx codes)
    
    
    
    // Private class variables
    private $sql_handle;
    
    // variable for db connection resource
    private $db_link;
    
    // to store the query result id
    private $query_result;
    
    // to store the number of results (row count/ affected count)
    private $total;
    
    // to define if db connection should be persistant
    private $db_persistent;
    
    // to store last made query
    private $last_query;
    
    // to set the internal php character check of the result content
    private $charset                    = 'UTF-8';
    
    // to set the database character encoding of the result content
    private $charset_db                 = 'UTF8';
    
    // SQL logging variables containing log object and settings
    private $sql_log_object             = NULL;
    private $sql_log_as_type            = 'DEBUG';
    private $sql_log_success_only       = TRUE;
    private $sql_log_ignore_select      = TRUE;
    
    // variable for persistant error message
    private $errno                      = NULL;
    private $errstr                     = NULL;
    
    // SQL type dependent variables
    private $sql_error                  = "";
    
    
    
    ////////////////////////////////////////////////////////////////////////////
    // CONSTRUCTOR & DESCTRUCTOR methods of the class
    ////////////////////////////////////////////////////////////////////////////
    
    
    
    /**
     * Constructor
     * 
     * @param $connection_string - The sql connection string
     * @param $dbg_level Debug log level
     * @param $debug_object Debug object to send log messages to
    **/
    function __construct ($connection_string, $dbg_level = 0, &$debug_object = NULL)
    {
        // initialize parent class MainClass
        parent::Main_init($dbg_level, $debug_object);
        
        
        date_default_timezone_set("UTC");
        
        $this->set_connection_string($connection_string);
        
        $this->_check_prerequisits();
    }
    
    
    
    
    ////////////////////////////////////////////////////////////////////////////
    // SET methods to set class Options
    ////////////////////////////////////////////////////////////////////////////
    
    
    
    /**
     * Set and parse the connection string
     * 
     * This method is used to set the connection string. The connection string 
     * contains the server name as well as the port the username and the 
     * password for the connection. The connection string should be formated as 
     * follows:
     * sqlite3://path/to/database
     *  
     * 
     * @param $connection_string - The sql connection string
    **/
    public function set_connection_string($connection_string)
    {
        parent::debug2("Called method ".__FUNCTION__." with ... connection_string=$connection_string");
        if($this->dbg_intern_main > 0) echo "<pre>(Line: ".__LINE__.") ".get_class($this)."->".__FUNCTION__." - Called method ".__FUNCTION__." with ... connection_string=$connection_string.</pre>\n";
        
        // parse connection string of format 
        //     sqlite3://path/to/database
        //     
        // parse connection string of format (persistant)
        //     no persistant connection possible
        //     
        parent::debug2("Connection string = '$connection_string'");
        
        // pre-define the variables
        $this->db_type = '-';
        $this->db_host = '-';
        $this->db_port = '-';
        $this->db_user = '-';
        $this->db_pass = '-';
        $this->db_name = '-';
        
        // parse the connection string
        list($this->db_type, $this->db_host) = preg_split("/\:\/\//", $connection_string);
        
        
        // set the connection string components to the class if they exist
        //
        // Not required
        //
        //
        //
        //
        //
        //
        //
        //
        //
        //
        //
        //
        //
        //
        //
        //
        //
        //
        //
        //
        //
        //
        
        // check for persistent option
        $this->db_persistent = FALSE;
        //
        // Not required
        //
        //
        
        // check for given port
        //
        // Not required
        //
        //
        
        
        parent::debug2("Connection Informations: db_host=".$this->db_host." db_user=".$this->db_user." db_pass=".$this->db_pass." db=".$this->db_name." db_persistent=".$this->db_persistent);
        
        // create SQL object
        try
        {
            $this->sql_handle = new PDO("sqlite:".$this->db_host);
        }
        catch (exception $e)
        {
            parent::warning("Error while creating PDO object. Check available PDO Drivers. \n".print_r($e, TRUE));
            $this->sql_handle = NULL;
        }
    }
    
    
    
    /**
     * Set the sql query log object
     * 
     * This method is used to set the sql query log object and there settings. 
     * With the parameters you can define if only successful queries should be 
     * logged as well as if SELECT queries should be ignored.
     * 
     * @param object $log_object Set the logging object
     * @param string $log_as_type Set to type of logging desired, default is DEBUG
     * @param string $success_only Set to TRUE to log only successful queries
     * @param string $ignore_select Set to TRUE to ignore SELECT queries
    **/
    function set_sql_log_object($log_object, $log_as_type = 'DEBUG', $success_only = TRUE, $ignore_select = TRUE)
    {
        $this->sql_log_object           = $log_object;
        $this->sql_log_as_type          = $log_as_type;
        $this->sql_log_success_only     = $success_only;
        $this->sql_log_ignore_select    = $ignore_select;
    }
    
    
    
    ////////////////////////////////////////////////////////////////////////////
    // GET methods to get class Options
    ////////////////////////////////////////////////////////////////////////////
    
    
    
    ////////////////////////////////////////////////////////////////////////////
    // PUBLIC methods to set class Options
    ////////////////////////////////////////////////////////////////////////////
    
    
    
    /**
     * Connect to SQL server
     * 
     * Connect to the sql server with the configured settings of the class. The 
     * result of the connect will be returned as boolean value.
     * 
     * 
     * 
     * 
     * @param $errno - Error number if connection failed
     * @param $errstr - Error text if connection failed
     * @return bool - Sucess of connection
    **/
    public function connection(&$errno, &$errstr)
    {
        parent::debug2("Called method ".__FUNCTION__);
        if($this->dbg_intern_main > 0) echo "<pre>(Line: ".__LINE__.") ".get_class($this)."->".__FUNCTION__." - Called method ".__FUNCTION__.".</pre>\n";
        
        // check for a persistant class error
        if($this->errno != NULL)
        {
            $errno = $this->errno;
            $errstr = $this->errtext;
            parent::warning("$errno: $errstr");
            return FALSE;
        }
        
        parent::debug("Connect to db_type=".$this->db_type." db_host=".$this->db_host." db_user=".$this->db_user);
        $errno = $errstr = NULL;
        
        
        // Check if SQL is connect or not
        if(empty($this->sql_handle) === TRUE)
        {
            // Define SQL Variables
            if(isset($this->db_host) === FALSE || isset($this->db_name) === FALSE || isset($this->db_user) === FALSE || isset($this->db_pass) === FALSE)
            {
                $errno = 100;
                $errstr = "Missing connection informations (server, db, user or password)";
                parent::error("Connect ... Missing connection informations");
                return FALSE;
            }
            // SQL Connection
            //
            // Implemented via Object create in set_connection_string()
            //
            //
            //
            //
            //
            //
            //
            //
            
            // check for the sql connection
            if(empty($this->sql_handle) === TRUE)
            {
                $errno = 101;
                $errstr = "Connect to sqlite3 Error";
                $errcode = '';
                $errmsg[2]  = '';
                parent::error("Connect ... Connection to Server FAILED - Error = ".$errcode.": ".$errmsg[2]);
                return FALSE;
            }
            
            // Select Database
            // 
            // Not required
            //
            //
            //
            //
            //
            //
            //
            //
            //
            //
            //
            //
            //
            
            // set internal character for queries and results
            //
            // not applicable
            //
            //
            //
            //
            //
            //
            //
            //
            //
            //
            //
            //
            //
            //
            //
            //
            //
            //
            //
            //
            //
            //
            //
            //
            //
            //
            //
            //
            //
            
            parent::debug2("Connect ... SUCESS");
            return TRUE;
        }
        else
        {
            // already connected, return TRUE
            return TRUE;
        }
    }
    
    
    
    
    /**
     * Disconnect from SQL server
     * 
     * This method is used to disconnect from the configured server.
    **/
    public function disconnect()
    {
        parent::debug2("Called method ".__FUNCTION__);
        if($this->dbg_intern_main > 0) echo "<pre>(Line: ".__LINE__.") ".get_class($this)."->".__FUNCTION__." - Called method ".__FUNCTION__.".</pre>\n";
        
        // check for a persistant class error
        if($this->errno != NULL)
        {
            $errno = $this->errno;
            $errstr = $this->errtext;
            parent::warning("$errno: $errstr");
            return FALSE;
        }
        
        
        parent::debug2("Disconnect from ".$this->db_host);
        $this->sql_handle->close();
    }
    
    
    
    
    /**
     * Execute query on the SQL server
     * 
     * This method is used to send a query to the database. The query is sent 
     * to the database server configured for the class. The result-id is 
     * returned on success.
     * 
     * @param $string - Query string to send to the sql server
     * @param $errno - Error number if connection failed
     * @param $errstr - Error text if connection failed
     * @param $last_insert_id - get the last insert id right after the query is executed
     * @return mixed - FALSE on error or the result-id of the result
    **/
    public function query($string, &$errno, &$errstr, &$last_insert_id = FALSE)
    {
        parent::debug2("Called method ".__FUNCTION__." with ... last_insert_id=".($last_insert_id === TRUE ? "TRUE" : "FALSE").", query=$string");
        if($this->dbg_intern_main > 0) echo "<pre>(Line: ".__LINE__.") ".get_class($this)."->".__FUNCTION__." - Called method ".__FUNCTION__." with ... string=$string.</pre>\n";
        
        // check for a persistant class error
        if($this->errno != NULL)
        {
            $errno = $this->errno;
            $errstr = $this->errtext;
            parent::warning("$errno: $errstr");
            return FALSE;
        }
        
        // set default values
        $errno = $errstr = NULL;
        // query_type not needed
        
        
        
        
        
        if(empty($this->sql_handle))
        {
            if($this->connection($errno, $errstr) === FALSE)
            {
                $errcode = $this->sql_handle->errorCode();
                $errmsg  = $this->sql_handle->errorInfo();
                parent::warning("Perform Query Error. \nQUERY = $string \nError = ".$errcode.": ".$errmsg[2]);
                return FALSE;
            }
        }
        
        // send query to the database
        parent::performance("", "start");
        $this->query_result = $this->sql_handle->query($string, PDO::FETCH_ASSOC);
        if($last_insert_id !== FALSE)
        {
            // read the last insert ID
            $last_insert_id = $this->sql_handle->lastInsertId();
            parent::debug2("Requested last_insert_id ... id: $last_insert_id");
        }
        parent::performance(get_class($this)." - Send query ... $string", "stop");
        
        // check if result is available
        if(is_object($this->query_result) === TRUE) 
        {
            $this->sql_result = $this->query_result->fetchAll(PDO::FETCH_ASSOC);
        }
        
        // save the sent query
        $this->last_query = $string;
        
        // check for an error
        if($this->query_result === FALSE)
        {
            // write the sql log entry if the log object is set
            if(get_class($this->sql_log_object) === "Debug_Logging" && $this->sql_log_success_only === FALSE)
            {
                // only do the logging if SELECT should be logged or the query is not a SELECT
                if($this->sql_log_ignore_select === FALSE || ($this->sql_log_ignore_select === TRUE && strtoupper(substr(trim($string), 0, 6)) !== "SELECT"))
                {
                   // send the log message
                   $this->sql_log_object->log_as_type($this->sql_log_as_type, $string);
                }
            }
            $errno = 106;
            $errcode = $this->sql_handle->errorCode();
            $errmsg  = $this->sql_handle->errorInfo();
            $errstr = "Perform Query Error. \nQUERY = $string \nError = ".$errcode.": ".$errmsg[2];
            parent::warning("Perform Query Error. \nQUERY = $string \nError = ".$errcode.": ".$errmsg[2]);
            return FALSE;
        }
        else
        {
            // write the sql log entry if the log object is set
            if(get_class($this->sql_log_object) === "Debug_Logging")
            {
                // only do the logging if SELECT should be logged or the query is not a SELECT
                if($this->sql_log_ignore_select === FALSE || ($this->sql_log_ignore_select === TRUE && strtoupper(substr(trim($string), 0, 6)) !== "SELECT"))
                {
                   // send the log message
                   if(substr($string, -1) !== ';')
                   {
                       $string = $string.';';
                   }
                   $this->sql_log_object->log_as_type($this->sql_log_as_type, $string);
                }
            }
        }
        
        parent::debug2("Query ... SUCESS");
        return "1";
        
    }
    
    
    
    
    /**
     * Number of rows returned from Query
     * 
     * This method is used to get the number of rows effected or returned from 
     * the sent query. If no query-id is given, the id of the last sent query 
     * is used.
     * 
     * @param $string - Query string to send to the sql server [optional]
     * @param $qid - query id of a sent query from the sql server [optional]
     * @param $errno - Error number if connection failed
     * @param $errstr - Error text if connection failed
     * @return FALSE / int - number of returned rows
    **/
    function nums($string = "",$qid = "", &$errno, &$errstr)
    {
        parent::debug2("Called method ".__FUNCTION__." with ... string=$string, qid=$qid");
        if($this->dbg_intern_main > 0) echo "<pre>(Line: ".__LINE__.") ".get_class($this)."->".__FUNCTION__." - Called method ".__FUNCTION__." with ... string=$string, qid=$qid.</pre>\n";
        
        // check for a persistant class error
        if($this->errno != NULL)
        {
            $errno = $this->errno;
            $errstr = $this->errtext;
            parent::warning("$errno: $errstr");
            return FALSE;
        }
        
        parent::debug2("Affected Rows of Query");
        $errno = $errstr = NULL;
        $this->total = 0;
        
        
        // check if the query string is provided and send query and get the number of results
        if($string != "")
        {
            // retrive the number of results
            $this->query($string);
            if(is_object($this->query_result) === TRUE) 
            {
                $this->total = count($this->sql_result);
            }
        }
        
        // check if the query id is provided and get the number of results
        elseif($qid != "")
        {
            // retrive the number of results
            if(is_object($this->query_result) === TRUE) 
            {
                $this->total = count($this->sql_result);
            }
        }
        
        // check if no query info is provided and get the number of results from object query id
        elseif(empty($string) === TRUE && empty($qid) === TRUE)
        {
            // retrive the number of results
            if(is_object($this->query_result) === TRUE) 
            {
                $this->total = count($this->sql_result);
            }
        }
        
        
        // return the result
        parent::debug2("Affected Rows = ".$this->total);
        return $this->total;
    }
    
    
    
    
    /**
     * Returned data from query as object
     * 
     * This method will fetch one result row from the given result-id and 
     * returns it as an object. If the result-id is not given, the 
     * result-id of the last query is used.
     * 
     * @param $string - Query string to send to the sql server [optional]
     * @param $qid - query id of a sent query from the sql server [optional]
     * @param $errno - Error number if connection failed
     * @param $errstr - Error text if connection failed
     * @return bool - Sucess of connection
    **/
    function objects($string = "",$qid = "", &$errno, &$errstr)
    {
        parent::debug2("Called method ".__FUNCTION__." with ... string=$string, qid=$qid");
        if($this->dbg_intern_main > 0) echo "<pre>(Line: ".__LINE__.") ".get_class($this)."->".__FUNCTION__." - Called method ".__FUNCTION__." with ... string=$string, qid=$qid.</pre>\n";
        
        // check for a persistant class error
        if($this->errno != NULL)
        {
            $errno = $this->errno;
            $errstr = $this->errtext;
            parent::warning("$errno: $errstr");
            return FALSE;
        }
        
        // initialize variable
        $object = NULL;
        
        // if query type is an "affected"
        //
        // Not required
        //
        //
        
        parent::debug2("Objects (".$this->query_result.")");
        $errno = $errstr = NULL;
        if($string != "")
        {
            $this->query($string, $errno, $errstr);
            if(is_object($this->query_result) === TRUE) 
            {
                $objects = (object) $this->sql_result;
            }
        }
        elseif($qid != "")
        {
            if(is_object($this->query_result) === TRUE) 
            {
                $objects = (object) $this->sql_result;
            }
        }
        elseif(empty($string) === TRUE && empty($qid) === TRUE)
        {
            if(is_object($this->query_result) === TRUE) 
            {
                $objects = (object) $this->sql_result;
            }
        }
        
        // check and convert result strings
        //foreach($objects as $key => $value)
        //{
        //    $this->_convert_charset($objects[$key], $errno, $errstr);
        //}
        
        //parent::debug2("Objects: \n".print_r($objects, TRUE));
        return $objects;
    }
    
    
    
    
    /**
     * Returned data from query as associated array
     * 
     * This method will fetch one result row from the given result-id and 
     * returns it as an assoc. array. If the result-id is not given, the 
     * result-id of the last query is used.
     * 
     * @param $string - Query string to send to the sql server [optional]
     * @param $qid - query id of a sent query from the sql server [optional]
     * @param $errno - Error number if connection failed
     * @param $errstr - Error text if connection failed
     * @return bool - Sucess of connection
     **/
    function row_array_assoc($string = "",$qid = "", &$errno, &$errstr)
    {
        parent::debug2("Called method ".__FUNCTION__." with ... string=$string, qid=$qid");
        if($this->dbg_intern_main > 0) echo "<pre>(Line: ".__LINE__.") ".get_class($this)."->".__FUNCTION__." - Called method ".__FUNCTION__." with ... string=$string, qid=$qid.</pre>\n";
        
        // check for a persistant class error
        if($this->errno != NULL)
        {
            $errno = $this->errno;
            $errstr = $this->errtext;
            parent::warning("$errno: $errstr");
            return FALSE;
        }
        
        // initialize variable
        $object = NULL;
        
        
        // if query type is an "affected"
        //
        // Not required
        //
        //
        
        parent::debug2("Assoc. Array");
        $errno = $errstr = NULL;
        if($string != "")
        {
            $this->query($string, $errno, $errstr);
            if(is_object($this->query_result) === TRUE) 
            {
                $objects = $this->sql_result;
            }
        }
        elseif($qid != "")
        {
            if(is_object($this->query_result) === TRUE) 
            {
                $objects = $this->sql_result;
            }
        }
        elseif(empty($string) === TRUE && empty($qid) === TRUE)
        {
            if(is_object($this->query_result) === TRUE) 
            {
                $objects = $this->sql_result;
            }
        }
        
        // check and convert result strings
        //foreach($objects as $key => $value)
        //{
        //    $this->_convert_charset($objects[$key], $errno, $errstr);
        //}
        
        //parent::debug2("Assoc. Array: " . print_r($objects, TRUE));
        return $objects;
    }
    
    
    
    /**
     * Send query and return data
     * 
     * This method is used to retrive a result set for a given sql query. This 
     * method will send the query, get the number of results as well as all 
     * result rows and returns it as one assoc. array. This array will contain 
     * the element "count" which contains the number of rows fetched or 
     * affected by the query. The element "data" or the returned array will 
     * contain a list of all result rows.
     * 
     * @param $string - Query string to send to the sql server [optional]
     * @param $errno - Error number if connection failed
     * @param $errstr - Error text if connection failed
     * @param $last_insert_id - get the last insert id right after the query is executed
     * @return array - The result array containing "data" and "count"
     **/
    public function get_query_result($query, &$errno = NULL, &$errstr = NULL, &$last_insert_id = FALSE)
    {
        parent::debug2("Called method ".__FUNCTION__." with ... last_insert_id=".(($last_insert_id === TRUE ? "TRUE" : "FALSE")).", query=$query");
        if($this->dbg_intern_main > 0) echo "<pre>(Line: ".__LINE__.") ".get_class($this)."->".__FUNCTION__." - Called method ".__FUNCTION__." with ... query=$query.</pre>\n";
        
        // check for a persistant class error
        if($this->errno != NULL)
        {
            $errno = $this->errno;
            $errstr = $this->errtext;
            parent::warning("$errno: $errstr");
            return FALSE;
        }
        
        // if not already connected, connect to the database
        if(empty($this->sql_handle) === TRUE)
        {
            parent::debug2('No sql_handle found, attempting to connect');
            if($this->connection($errno, $errstr) == FALSE)
            {
                parent::error("Connect ... Error = $errno: $errstr");
                return FALSE;
            }
        }
        
        
        // send query to db server
        $query_result_id = $this->query($query, $errno, $errstr, $last_insert_id);
        if($errno != NULL)
        {
            parent::error("Query ... Error = $errno: $errstr");
            return FALSE;
        }
        
        
        // get number of results
        $query_result_count = $this->nums("", $query_result_id, $errno, $errstr);
        if($errno != NULL)
        {
            parent::error("Nums ... Error = $errno: $errstr");
            return FALSE;
        }
        
        
        
        // get all results from the query
        //
        // Implemented different ... see below
        //
        //
        //
        //
        //
        //
        //
        //
        //
        //
        //
        $query_result["data"] = $this->row_array_assoc("", $query_result_id, $errno, $errstr);
        
        $query_result["count"] = $query_result_count;
        parent::debug2("Data Row Count: ".$query_result["count"]);
        
        
        if(isset($query_result["data"]) == TRUE)
        {
            parent::debug2("Data Row (".$query_result["count"]."):" . print_r($query_result["data"], TRUE));
        }
        
        return $query_result;
        
        
    }
    
    
    
    /**
     * Escape string for the database
     * 
     * This method is used to escape all not allowed charatcters of the string.
     * This escape method is database dependent.
     * 
     * @param $string - The string to be escaped
     * @param $errno - Error number if failed
     * @param $errstr - Error text if failed
     * @return string - escaped string
    **/
    function escape_string($string, &$errno = NULL, &$errstr = NULL)
    {
        parent::debug2("Called method ".__FUNCTION__." with ... string=$string");
        if($this->dbg_intern_main > 0) echo "<pre>(Line: ".__LINE__.") ".get_class($this)."->".__FUNCTION__." - Called method ".__FUNCTION__." with ... string=$string.</pre>\n";
        
        // Check if parameter is a string
        if(is_string($string) === FALSE)
        {
            $errno = 109;
            $errstr = "Parameter not of type string";
            return $string;
        }
        
        
        // Check if sqlite2 function exists
        if(function_exists('sqlite_escape_string') === TRUE)
        {
            $result = sqlite_escape_string($string);
        }
        else
        {
            $result = str_replace("'", "''", $string);
            
        }
        
        
        // Escape the string
        return $result;
    }
    
    
    
    ////////////////////////////////////////////////////////////////////////////
    // PRIVATE methods of the class
    ////////////////////////////////////////////////////////////////////////////
    
    
    
    /**
     * Set and parse the connection string
     * 
     * @param string $connection_string The SQL connection string
     * @return boolean TRUE on success FALSE otherwhise
    **/
    private function _check_prerequisits()
    {
        parent::debug2("Called method ".__FUNCTION__." with ... db_type=".$this->db_type);
        if($this->dbg_intern_main > 0) echo "<pre>(Line: ".__LINE__.") ".get_class($this)."->".__FUNCTION__." - Called method ".__FUNCTION__." with ... db_type=".$this->db_type.".</pre>\n";
        
        // initialize list of missing pre-requisits
        $this->prerequisits_missing = '';
        
        // initialize function and class list
        $function_list = $class_list = NULL;
        
        // define pre-requisits
        $class_list = array('PDO');
        // 
        // 
        parent::debug2("List of classes:".print_r($class_list, TRUE));
        
        // call the pre-requisit  check method
        $result = parent::check_prerequisites($function_list, $class_list);
        
        
        //
        // Check pre-requisits of db class
        //
        if($result !==  TRUE)
        {
            $class_missing    = join(", ", $result['classes']);
            $function_missing = join("(), ", $result['functions'])."()";
            
            $class_missing = preg_replace('/, $/', '', $class_missing);
            $function_missing = preg_replace('/, $/', '', $function_missing);
            
            $this->prerequisits_missing = $class_missing.", ".$function_missing;
            
            $result = FALSE;
        }
        
        $this->prerequisits_missing = preg_replace('/, $/', '', $this->prerequisits_missing);
        $this->prerequisits_missing = preg_replace('/,\s+\(\)$/', '', $this->prerequisits_missing);
        $this->prerequisits_missing = preg_replace('/^[:\(\)]*, /', '', $this->prerequisits_missing);
        
        //
        // Check pre-requisits of db class - END
        //
        
        
        
        //
        // Check result and define error description if needed
        //
        
        // check result
        if($result === FALSE)
        {
                parent::warning("Checking pre-requisits: Result = $result");
                parent::warning("Checking pre-requisits: Missing= ".$this->prerequisits_missing);
                
                // return with error
                $this->errno = 201;
                $this->errtext = "Pre-requisits not fulfilled.\n";
                $this->errtext .= "Missing: ".$this->prerequisits_missing;
                return FALSE;
        }
        
        //
        // Check result and define error description if needed - END
        //
        
        
        
        // return TRUE to indicate sucessful check
        return TRUE;
    }
    
    
    
    /**
     * Checks and convert string encoding
     *
     * This method is used to detectt the encoding of the given string. If the 
     * given string is already from the expected encoding, TRUE is returned. If 
     * the string is from another encoding, it is tried to find the encoding 
     * and convert it to the extected encoding. If this failes, a error is 
     * returned. Expected encoding: UTF8
     *
     * @param string $string The string to be checked and converted
     * @return mixed The string in expected encoding or FALSE on error.
    **/
    private function _convert_charset(&$string, &$errno = NULL, &$errstr = NULL)
    {
        parent::debug2("Called method ".__FUNCTION__." with ... string=$string");
        if($this->dbg_intern_main > 0) echo "<pre>(Line: ".__LINE__.") ".get_class($this)."->".__FUNCTION__." - Called method ".__FUNCTION__." with ... string=$string.</pre>\n";
        
        // array containing valid encodings, in order of priority
        $valid_encoding_array = array($this->charset);
        
        // returns encoding string from array above if the string is valid, false otherwise
        parent::debug2("checking string encoding of ... string='$string'");
        parent::debug2('hex representation of string before conversion ... '.bin2hex($string));
        
        $valid_encoding = $this->charset;
        if(function_exists('mb_detect_encoding'))
        {
            // check if string is already a allowed encoding
            // NOTE: mb_detect_encoding will not function correctly if the last character in the string is the wrong encoding
            // as of PHP 5.1.6, therefore the extra x ASCII character is added to the end so it functions correctly
            $valid_encoding = mb_detect_encoding($string.'x', $valid_encoding_array, TRUE);
        }
        else
        {
            parent::error('mb_detect_encoding() function not available, continuing without checking if string is '.$this->charset);
        }
        
        // if it was not a valid encoding ...
        if($valid_encoding === FALSE)
        {
            // Check if it is a convertable encoding ... (0x00 - 0xFF => ASCII)
            $can_be_converted = preg_match( '/^[\\x00-\\xFF]*$/', "$string" );
            
            // concert the convertable string and key
            if($can_be_converted === 1)
            {
                // string and key encoding to UTF8
                parent::debug2("ISO-8859-1 string detected, converting to utf8");
                $string = utf8_encode($string);
                parent::debug2('hex representation of string after conversion ... '.bin2hex($string));
                return TRUE;
            }
            else
            {
                // string contains not convertable characters, return an error
                parent::debug2('Encoding not possible, non convertable characters found in string');
                $errno = 111;
                $errstr = 'Invalid character encoding in result';
                parent::debug2('hex representation of string after conversion ... '.bin2hex($string));
                return FALSE;
            }
        }
        else
        {
            parent::debug2("Detected encoding is '$valid_encoding'");
            parent::debug2('hex representation of string after conversion ... '.bin2hex($string));
            return TRUE;
        }
    }
    
    
    
}
?>