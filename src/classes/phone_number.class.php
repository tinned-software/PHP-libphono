<?php
/**
 * Phone Number representation class
 *
 * This class holds the number input and its alternative representation. The following
 * representations should be available:
 *     international number,
 *     input number,
 *     international normalized number,
 *     normalized number.
 * The information will only be requested from the databases when they are requested the
 * first time from the class.
 *
 * @version    1.2.5
 * @package    framework
 * @subpackage phone_number
 * @author     Apostolos Karakousis <apostolos.karakousis@tinned-software.net>
 * @copyright  2011 Tinned Software
 *
 * @todo       database is hardcoded in the queries for now, should use the constructor parameters
 */


/*
 * 0030            2310            205 385
 * COUNTRY CODE    REGION CODE     SUBSCRIBER NUMBER
 *
 * COUNTRY CODE:
 * The country code is the part of an international number that identifies the country.
 * REGION CODE:
 * The region code is the part of the number that identifies the geographical location or the network where the subscriber number is located.
 * SUBSCRIBER NUMBER:
 * The subscriber number is the part of the phone number that identifies the subscriber. It does not include the region code.
 *
 * EXIT DIGITS:
 * The exit digits are country specific digits that are required to place an international call. (The international representation of the exit digit is the leading '+')
 *
 * TRUNK CODE:
 * The trunk code is the part of the number that defines if the following digits are part of the region code or part of the subscriber number.
 *
 * LOCAL NUMBER:
 * A local number is the phone number that contains the phone number that is used to call within a country. It must not contain the country dial code.
 * It will contain any region code and if applicable (depending on the country) the trunk code.
 *
 * INTERNATIONAL NUMBER:
 * A international number is the representation of a number including the region code, country code and the country specific exit-digits (the alternative to '+").
 * This numbers are international but can not be dialed from all countries.
 *
 * INTERNATIONAL NORMALIZED NUMBER:
 * A international normalized number is the representation of the phone number that allows it to call across countries. It must contain the country code and the leading "+".
 *
 * NORMALIZED NUMBER:
 * A normalized number is the international number including the country dial code but without the leading exit digit or "+".
 *
 *
 * Error codes:
 * 101 ... "country_3_letter was not set"
 * 102 ... "country_3_letter was not string"
 * 103 ... "parameter country_3_letter was not a 3 letter string"
 * 104 ... "input_number was not set"
 * 105 ... "input_number was not string"
 * 201 ... "country_3_letter was set to NULL"
 * 202 ... "input_number was NOT set"
 * 301 ... "Fetching data failed. Internal SQL error ".$errno
 * 401 ... "missing parameters, cannot continue to process number"
 * 402 ... "false exit dialcode array, cannot continue to process number"
 * 403 ... "tried to compare two non-phone number objects"
 *
 * @package framework
 * @subpackage classes
**/

require_once dirname(__FILE__).'/main.class.php';

class Phone_Number extends Main
{

    ////////////////////////////////////////////////////////////////////////////
    // PROPERTIES of the class
    ////////////////////////////////////////////////////////////////////////////

    /**
     * Define the complete length of the string
    **/

    // basic stuff
    private $_debug_level = NULL; // should probably be discarded
    private $_debug_object = NULL; // should probably be discarded
    private $_sql_obj = NULL;
    private $_sql_table = NULL;
    private $_db_name = NULL;

    // class variables that are set externally
    private $_country_3_letter = NULL;
    private $_input_number = NULL;

    // internal class variables
    private $_validated_number = NULL;
    private $_international_number = NULL;
    private $_international_number_normalized = NULL;
    private $_number_normalized = NULL;
    private $_local_number = NULL;
    private $_destination = NULL;
    private $_all_formats = NULL;
    private $_normalize_success = FALSE;

    private $_country_code = NULL;
    private $_region_code = NULL;
    private $_subscriber_number = NULL;
    private $_trunk_code = NULL;
    private $_exit_dialcode = NULL;

    private $_error = NULL;
    private $_error_list = NULL;

    // http://en.wikipedia.org/wiki/E.123
    // private $_formatting_characters = array('(',')','-','/',' ', '+', '.', '~');
    // private $_dialable_characters = array('0','1','2','3','4','5','6','7','8','9','*','#','p');
    private $_pause_characters = array(',','p','w');
    private $_pause_character_internal = 'p'; // replace all pause characters with this value

    // maximum number of characters an input can contain.
    private $_max_input_length = 32;

    // for main class error reporting
    public $support_level = 1;

    // Variables to hold last error code and text
    //    private $_errno = NULL;
    //    private $_api_error_code = NULL;
    //    private $_errtext = NULL;

    ////////////////////////////////////////////////////////////////////////////
    // CONSTRUCTOR & DESCTRUCTOR methods of the class
    ////////////////////////////////////////////////////////////////////////////

    /**
     * Constructor to set the initial values for the class
     *
     * @param integer debug_level    Debugging level
     * @param object  &debug_object  The Debugging object
     * @param object  &sql_obj       The SQL object
     * @param string  sql_table      The sql table for the class queries
     * @return void
    **/
    public function __construct($debug_level = 0, &$debug_object = NULL, &$sql_obj = NULL, $sql_table = NULL)
    {
        // copy over the variable contents
        $this->_debug_level     = $debug_level;
        $this->_debug_object    = $debug_object;
        $this->_sql_obj         = $sql_obj;
        $this->_sql_table       = $sql_table;

        // initialize parent class MainClass
        parent::Main_init($debug_level, $debug_object);
    }

    // ===========================
    //            METHODS
    // ===========================

    ////////////////////////////////////////////////////////////////////////////
    // SET methods to set class Options
    ////////////////////////////////////////////////////////////////////////////

    /**
     * This method is used to set the 3 letter country code that is used to normalize
     * the number
     *
     * @param string country_3_letter 3-letter ISO code
     * @return boolean
    **/
    public function set_normalized_country($country_3_letter = NULL)
    {
        parent::debug2(">");

        // must be string
        if (isset($country_3_letter) === FALSE)
        {
            parent::report_error(101, "country_3_letter was not set");

            return FALSE;
        }

        if (is_string($country_3_letter) === FALSE)
        {
            parent::report_error(102, "country_3_letter was not string, it was : '$country_3_letter'");

            return FALSE;
        }

        // $country_3_letter should have characters a-z, A-Z and should be exactly 3 characters
        if (preg_match("/[a-zA-Z]{3}/", $country_3_letter) === FALSE)
        {
            parent::report_error(103, "parameter country_3_letter was not a 3 letter string, it was '$country_3_letter");

            return FALSE;
        }

        parent::debug2("Called with parameters: country_3_letter = '".$country_3_letter."'");

        // clear cache for old country_3_letter (both if was set or was set to NULL)
        if ($this->_country_3_letter !== $country_3_letter)
        {
            parent::debug2("country_3_letter was modified, forcing a flush on all calculations");

            $this->_unset_all();
            $this->_country_3_letter = $country_3_letter;
        }

        parent::debug2("<");

        return TRUE;
    }

    /**
     * This method is used set the input number
     *
     * @param string input_number the input number
     * @return boolean
    **/
    public function set_input_number($input_number = NULL)
    {
        parent::debug2(">");

        // must be string
        if (isset($input_number) === FALSE)
        {
            parent::report_error(104, "input_number was not set");

            return FALSE;
        }

        if (is_string($input_number) === FALSE)
        {
            parent::report_error(105, "input_number was not string");

            return FALSE;
        }

        if (strlen($input_number) > $this->_max_input_length)
        {
            $input_number = substr($input_number, 0, $this->_max_input_length);
            parent::debug("WARNING: input number:$input_number longer than {$this->_max_input_length}, truncated");
        }

        parent::debug2("called with parameters: input_number = '".$input_number."'");

        // reset ALL variables if was not null to reset the normalization
        if (strcmp($this->_input_number, $input_number) != 0)
        {
            parent::debug2("input_number was modified, forcing a flush on all calculations");

            $this->_unset_all();
            $this->_input_number = $input_number;
        }

        parent::debug2("<");

        return TRUE;
    }

    ////////////////////////////////////////////////////////////////////////////
    // GET methods to get class Options
    ////////////////////////////////////////////////////////////////////////////

    /**
     * This method is used to get the 3 letter country code that is used to normalize
     * the number
     *
     * @param void
     * @return string
    **/
    public function get_normalized_country()
    {
        parent::debug2(">");

        if (isset($this->_country_3_letter) === FALSE)
        {
            parent::report_error(201, "country_3_letter was set to NULL");

            return FALSE;
        }

        parent::debug2("< returning country_3_letter: '".$this->_country_3_letter."'");

        return $this->_country_3_letter;
    }

    /**
     * This method is used to get input number
     *
     * @param void
     * @return string
    **/
    public function get_input_number()
    {
        parent::debug2(">");

        if (isset($this->_input_number) === FALSE)
        {
            parent::report_error(202, "input_number was NOT set");

            return FALSE;
        }

        parent::debug2("< returning input_number: '".$this->_input_number."'");

        return $this->_input_number;
    }

    /**
     * This method is used to get validated input number
     *
     * @param void
     * @return string
    **/
    public function get_validated_input_number()
    {
        parent::debug2(">");

        if (isset($this->_validated_number) === FALSE)
        {
            parent::debug2("_validated_number was set to NULL, recalculating");

            $tmp_number = $this->get_input_number();

            // replace *known pause character with an internal representation
            $pause_replace_preg = '/[' . implode('', $this->_pause_characters) . ']/';
            $tmp_number = preg_replace($pause_replace_preg, $this->_pause_character_internal, $tmp_number);
            // finish replacements
            $tmp_number = preg_replace("/[^\d^p\+]/", '', $tmp_number);

            $this->_validated_number = $tmp_number;
        }

        parent::debug2("< returning new validated_number: '".$this->_validated_number."'");

        return $this->_validated_number;
    }

    /**
     * This method is used to get the number in "international" format
     *
     * @param void
     * @return string
    **/
    public function get_international_number()
    {
        parent::debug2(">");

        if (isset($this->_international_number) === FALSE)
        {
            parent::debug2("_international_number was set to NULL, recalculating");

            // make sure we got the validated number and the country code
            $this->_country_3_letter = $this->get_normalized_country();
            $this->_validated_number = $this->get_validated_input_number();

            // make sure we have the exit code for this country
            if (isset($this->_exit_dialcode) === FALSE)
            {
                $this->_fetch_dialcodes();
            }

            $this->_international_number = $this->_exit_dialcode[0].$this->get_normalized_number();
        }

        parent::debug2("< international number = '".$this->_international_number."'");

        return $this->_international_number;
    }

    /**
     * This method is used to get the number in "normalized international" format
     *
     * @param void
     * @return string
    **/
    public function get_normalized_international_number()
    {
        parent::debug2(">");

        if (isset($this->_international_number_normalized) === FALSE)
        {
            parent::debug2("_international_number_normalized was set to NULL, recalculating");

            // make sure we got the validated number and the country code
            $this->_country_3_letter = $this->get_normalized_country();
            $this->_validated_number = $this->get_validated_input_number();
            $this->_international_number_normalized = "+".$this->get_normalized_number();
        }

        parent::debug2("< returning normalized international number: '$this->_international_number_normalized'");

        return $this->_international_number_normalized;
    }

    /**
     * This method is used to get the number in "normalized" E.164 format
     *
     * @param void
     * @return string
    **/
    public function get_normalized_number()
    {
        parent::debug2(">");

        if (isset($this->_number_normalized) === FALSE)
        {
            parent::debug2("_number_normalized was set to NULL, recalculating");

            // make sure we got the validated number AND the country code
            $this->_country_3_letter = $this->get_normalized_country();
            $this->_validated_number = $this->get_validated_input_number();

            if (isset($this->_exit_dialcode) === FALSE)
            {
                $success_fetching = $this->_fetch_dialcodes();
            }

            $this->_number_normalized = $this->_normalize_number($this->_validated_number, $this->_trunk_code, $this->_country_code, $this->_exit_dialcode, $this->_error, $this->_error_list);
        }

        parent::debug2("< returning '".$this->_number_normalized."'");

        return $this->_number_normalized;
    }

    /**
     * This method is used to get the number in "normalized" E.164 format if successful
     *
     * This method serves the same function as the get_normalized_number() method, with
     * the additional feature that it only returns a number if the normalization was
     * deemed as a success in the class. Otherwise it returns an empty string.
     *
     * @see get_normalized_number()
     * @param void
     * @return string the normalized number in string format, can be empty if normalization failed
    **/
    public function get_normalized_number_only()
    {
        $number_return = $this->get_normalized_number();
        if ($this->_normalize_success === TRUE)
        {
            return $number_return;
        }
        else
        {
            return "";
        }
    }

    /**
     * This method is used to get the number in "local" format
     *
     * @param void
     * @return string
    **/
    public function get_local_number()
    {
        parent::debug2(">");

        if (isset($this->local_number) === FALSE)
        {
            parent::debug2(" local_number was set to NULL, recalculating");

            // make sure we got the validated number and the country code
            $this->_country_3_letter = $this->get_normalized_country();
            $this->_validated_number = $this->get_validated_input_number();

            // make sure we have the exit code for this country
            if (isset($this->_exit_dialcode) === FALSE)
            {
                $this->_fetch_dialcodes();
            }

            $this->_local_number = $this->_trunk_code[0].substr($this->get_normalized_number(), strlen($this->_country_code));
        }
        parent::debug2("<");

        return $this->_local_number;
    }

    /**
     * This method generates all formats available in the class and returns them as an
     * associative array
     *
     * @param void
     * @return string
    **/
    public function dump_formats()
    {
        parent::debug2(">");

        if (isset($this->_all_formats) === FALSE)
        {
            parent::debug2(" _all_formats variable was set to NULL, recalculating");

            $this->_process_all_formats();
        }

        parent::debug2("<");

        return $this->_all_formats;
    }

    ////////////////////////////////////////////////////////////////////////////
    // PRIVATE methods of the class
    ////////////////////////////////////////////////////////////////////////////

    /**
     * This method processes the input_number and stores in valid format
     *
     * @param void
     * @return string
    **/
    private function _fetch_dialcodes()
    {
        parent::debug2(">");

        // step 1: make sure we have the country_3_letter code AND the validated version of the number
        $this->_country_3_letter = $this->get_normalized_country();

        if (    isset($this->_country_3_letter) === TRUE /*&& isset($this->_validated_number) === TRUE*/ &&
                // if any of the following is not set, we ned to go fetch
                (isset($this->_country_code) === FALSE || isset($this->_region_code) === FALSE || isset($this->_subscriber_number) === FALSE)
            )
        {
            // step 2: fetch from the database the exit_dialcode, international_dialcode, extended_dialcode, trunk_dialcode for this specific country
            parent::debug2("trying to fetch data from SQL data source");

            $db_name = NULL;
            if(isset($this->_db_name) === TRUE && is_null($this->_db_name) === FALSE)
            {
                $db_name = $this->_db_name . '.xx';
            }

            $query = 'SELECT '.$db_name."Country_Exit_Dialcode.country_3_letter, \n"
                    .'       '.$db_name."Country_Exit_Dialcode.exit_dialcode, \n"
                    .'       '.$db_name."Country_Dialcodes.international_dialcode, \n"
                    .'       '.$db_name."Country_Dialcodes.extended_dialcode, \n"
                    .'       '.$db_name."Country_Trunk_Code.trunk_dialcode \n"
                    .'FROM   '.$db_name."Country_Exit_Dialcode, \n"
                    .'       '.$db_name."Country_Dialcodes, \n"
                    .'       '.$db_name."Country_Trunk_Code \n"
                    .'WHERE  '.$db_name."Country_Exit_Dialcode.country_3_letter = '".$this->_country_3_letter."' AND \n"
                    .'       '.$db_name."Country_Dialcodes.country_3_letter = '".$this->_country_3_letter."' AND \n"
                    .'       '.$db_name."Country_Trunk_Code.country_3_letter = '".$this->_country_3_letter."'";

            // send query to db and get result
            $errno = $errtext = NULL;
            $query_result = $this->_sql_obj->get_query_result($query, $errno, $errtext);

            if ($errno != NULL)
            {
                // Error while query execution
//                parent::debug2(" fetching data FAILED !!!");
//                $this->_error_list[] = array('error_text' => "Internal Error", 'error_code' => "300.$errno");
//                $this->_error = TRUE;
//                parent::error("SQL: $errno, $errtext");
//                return FALSE;

                parent::report_error(301, "Fetching data failed. Internal SQL error ".$errno.': '.$errtext);
                $this->_error = TRUE;
            }

            if ($query_result['count'] > 0)
            {
                parent::debug2("Found CLI Country information: ". print_r($query_result['data'], TRUE));
                parent::debug2("trunk code = '".$query_result['data'][0]['trunk_dialcode']."'");
                parent::debug2("trunk type= '".gettype($query_result['data'][0]['trunk_dialcode'])."'");

                $this->_country_code = $query_result['data'][0]['international_dialcode'];
                $this->_exit_dialcode = array();
                $this->_trunk_code = array();

                for ($i = 0; $i < $query_result['count']; $i++)
                {
                    if (in_array($query_result['data'][$i]['exit_dialcode'], $this->_exit_dialcode) === FALSE)
                    {
                        $this->_exit_dialcode[] = $query_result['data'][$i]['exit_dialcode'];
                    }
                    if (in_array($query_result['data'][$i]['trunk_dialcode'], $this->_trunk_code) === FALSE)
                    {
                        $this->_trunk_code[] = $query_result['data'][$i]['trunk_dialcode'];
                    }
                }
                // sort array (order is important for normalization)
                usort($this->_trunk_code, array($this, '_sort_dialcode'));

                //parent::debug2('exit_dialcode'.print_r($this->_exit_dialcode, TRUE));
                //parent::debug2('trunk_dialcode'.print_r($this->_trunk_code, TRUE));
            }
            else
            {
                parent::debug2(" no exit dialcode / country code information found in DB, cannot normalize number, EXIT NOW !!!");
            }

            return TRUE;
        }
        else
        {
            parent::debug2("could not find country code in countries table! !!!");
            return FALSE;
        }

        // TODO: needs to be done
        parent::debug2("<");
    }

    /**
     * This method generates all formats available in the class and stores them accordingly
     *
     * @param void
     * @return string
    **/
    private function _process_all_formats()
    {
        parent::debug2(">");

        $this->_all_formats = array (
                "INPUT_NUMBER"                    => $this->get_input_number(),
                "INPUT_NUMBER_VALIDATED"          => $this->get_validated_input_number(),
                "INTERNATIONAL_NUMBER"            => $this->get_international_number(),
                "INTERNATIONAL_NUMBER_NORMALIZED" => $this->get_normalized_international_number(),
                "NORMALIZED_NUMBER"               => $this->get_normalized_number(),
                "LOCAL_NUMBER"                    => $this->get_local_number()
        );

        parent::debug2("<");
    }

    /**
     * This method unsets all internal variables in order to do a recalculation on them
     *
     * @param void
     * @return string
    **/
    private function _unset_all()
    {
        parent::debug2("");
        parent::debug2("setting all internal variables to NULL");

        $this->_validated_number = NULL;
        $this->_international_number = NULL;
        $this->_international_number_normalized = NULL;
        $this->_number_normalized = NULL;
        $this->_local_number = NULL;
        $this->_destination = NULL;
        $this->_normalize_success = FALSE;

        $this->_country_code = NULL;
        $this->_region_code = NULL;
        $this->_subscriber_number = NULL;
        $this->_trunk_code = NULL;
        $this->_exit_dialcode = NULL;

        $this->_all_formats = NULL;
    }

    ////////////////////////////////////////////////////////////////////////////
    // PUBLIC methods of the class
    ////////////////////////////////////////////////////////////////////////////

    /**
     * This method returns the object as a string in order to achieve comparison
     * this should work: $is_equal = ((string)$d === $b);          // true
     *
     * @param void
     * @return string
    **/
    public function __toString()
    {
        parent::debug2("called");

        return (string) $this->get_normalized_international_number();
    }

    /**
     * This method returns the object as a string in order to achieve comparison
     * this should work: $is_equal = ((string)$d === $b);          // true
     *
     * @todo finish / test this implementation!!
     *
     * @param Phone_Number $number a phone number object with which to compare
     * @return boolean TRUE if matched, FALSE if the comparison failed or numbers do not match
    **/
    public function is_equal_to_number($number)
    {
        parent::debug2("");
        parent::debug2("called");
        if (get_class($this) !== get_class($number))
        {
            parent::report_error(403, "class of input parameter does not match: ".get_class($this).' !== '.get_class($number));
            return FALSE;
        }

        // TODO: $number should be first in normalized international format
        if (strcmp($this->get_normalized_number(), $number->get_normalized_number()) === 0)
        {
            parent::debug2("returning TRUE");

            return TRUE;
        }
        else
        {
            parent::debug2("returning FALSE");

            return FALSE;
        }
    }

    /**
     * This method is used to convert an input number to a validated number
     *
     * @param void
     * @return string
    **/
    private function _input_to_valid($number, $method = 0)
    {
        parent::debug2(">");
        parent::debug2("called with parameters: number = [".$number."] method=".$method."]");

        if (isset($method) === FALSE)
        {
            $method = 0;
        }

        switch ($method)
        {
            case 0:
                // _normalize_number($the_number = NULL, $trunk_code = NULL, $country_code = NULL, $exit_dialcode_array = NULL, &$error, &$error_list);
                $success_fetching = $this->_fetch_dialcodes();
                if ($success_fetching)
                {
                    parent::debug2("fetched dialcodes");
                    //    $number = $this->_normalize_number($number, NULL, $country_code = NULL, $exit_dialcode_array = NULL, $this->_error, $this->_error_list);
                    $number = $this->_normalize_number($number, NULL, $this->_country_code, $this->_exit_dialcode, $this->_error, $this->_error_list);
                }
                break;
            case 1:
                // we define a table of possible replacements
                $search_replace_map = array(
                    // country prefix normalization
                    //    '+00' => '+', '++' => '+',
                    // country prefix is always 00
                    //    '+' => '00',
                    // ...brackets
                    '(' => '', ')' => '',
                    '[' => '', ']' => '',
                    '[' => '', ']' => '',
                    // slashes
                    '/' => '', '\\\\' => '',
                    '\\' => '', '\\\\' => '', '' => '',
                    // dashes
                    '-' => '', '_' => '',
                    // whitespaces
                    ' ' => '',
                    // 123CALLME
                    'a' => '2','b' => '2','c' => '2', 'A' => '2','B' => '2','C' => '2',
                    'd' => '3','e' => '3','f' => '3', 'D' => '3','E' => '3','F' => '3',
                    'g' => '4','h' => '4','i' => '4', 'G' => '4','H' => '4','I' => '4',
                    'j' => '5','k' => '5','l' => '5', 'J' => '5','K' => '5','L' => '5',
                    'm' => '6','n' => '6','o' => '6', 'M' => '6','N' => '6','O' => '6',
                    'p' => '7','q' => '7','r' => '7', 's' => '7','Q' => '7','Q' => '7','R' => '2','S' => '7',
                    't' => '8','u' => '8','v' => '8', 'T' => '8','U' => '8','V' => '8',
                    'x' => '9','y' => '9','z' => '9', 'X' => '9','Y' => '9','Z' => '9'
                );

                /**
                 *        Common Phone Keypads for Alpha code translation (we only use the international standard)
                 *                                 1    2    3    4    5    6    7    8    9    0
                 *        International Standard        ABC    DEF    GHI    JKL    MNO    PQRS    TUV    WXYZ
                 *        North American Classic        ABC    DEF    GHI    JKL    MN    PRS    TUV    WXY
                 *        Australian Classic        QZ  ABC    DEF    GHI    JKL    MNO    PRS    TUV    WXY
                 *        UK Classic                    ABC    DEF    GHI    JKL    MN    PRS    TUV    WXY    OQ
                 *        Mobile 1                      ABC    DEF    GHI    JKL    MN    PRS    TUV    WXY    OQZ
                **/

                // fetch search and replace arrays
                $search = array_keys($search_replace_map);
                $replace = array_values($search_replace_map);

                //  first pass: simple converter
                $number = str_replace($search, $replace, $number);

                // second pass: keep only digits, after the "+" sign if it exists
                if (substr($number, 0, 1) !== '+')
                {
                    $number = preg_replace('~[^\d]~', '', $number);
                }
                else
                {
                    $number = "+".preg_replace('[^\d]', '', substr($number, 1));
                }
                break;
        }

        parent::debug2("exiting");

        return $number;
    }



    /**
     * Internal logic for normalizing numbers
     *
     * ***direct copy from tyler's code to normalize number
     *
     * @param $the_number string
     * @param $trunk_code_array array
     * @param $country_code string
     * @param $exit_dialcode_array array
     * @param &$error boolean
     * @param &$error_list array
     **/
    private function _normalize_number($the_number = NULL, $trunk_code_array = NULL, $country_code = NULL, $exit_dialcode_array = NULL, &$error, &$error_list)
    {
        parent::debug2(">");
        parent::debug2("the_number:$the_number trunk_code:$trunk_code_array country_code:$country_code exit_dialcode_array:$exit_dialcode_array");

        if (isset($the_number) === FALSE || (isset($trunk_code_array) === FALSE && is_null($trunk_code_array) !== TRUE) || isset($country_code) === FALSE || isset($exit_dialcode_array) === FALSE)
        {
            parent::debug2(" xxxxx the_number:$the_number trunk_code:$trunk_code_array country_code:$country_code exit_dialcode_array:$exit_dialcode_array");
            parent::report_error(401, "missing parameters, cannot continue to process number");

            $error = TRUE;
            $error_list[] = array('error_number' => '100', 'error_text' => 'missing parameters');

            return NULL;
        }

        if (is_array($exit_dialcode_array) === FALSE || is_array($trunk_code_array) === FALSE)
        {
            parent::debug2("the_number:$the_number trunk_code:$trunk_code_array country_code:$country_code exit_dialcode_array:$exit_dialcode_array");
            parent::report_error(402, "false exit or trunk dialcode array, cannot continue to process number");

            /*
            parent::error('false exit dialcode array, cannot continue to process number');
            */
            $error = TRUE;
            $error_list[] = array('error_number' => '101', 'error_text' => 'false exit or trunk dialcode parameter');

            return NULL;
        }

        // sort the array from lowest to highest dialcode length
        //   (NULLs must be at the highest index in the array)
        usort($trunk_code_array, array($this, '_sort_dialcode'));

        parent::debug2("exit_dialcode_array\n".print_r($exit_dialcode_array, TRUE));
        parent::debug2("trunk_code_array\n".print_r($trunk_code_array, TRUE));
        // save the number for logging
        $the_number_original = $the_number;

        parent::debug2("number before normalization: '$the_number_original'");
        // remove international exit dialcodes such as + or 00
        foreach ($exit_dialcode_array as $exit_dialcode)
        {
            $the_number = preg_replace("/^([+]|$exit_dialcode)/", '+', $the_number);
        }

        parent::debug2("number after exit dialcode preg: '$the_number'");

        // if the country has a trunk code remove it
        if (substr($the_number, 0, 1) !== '+')
        {
            // loop through valid trunk codes, exiting immediately when one matches.
            foreach ($trunk_code_array as $trunk_code)
            {
                if (is_null($trunk_code) === TRUE)
                {
                    // add plus and the country's international dialcode
                    $the_number = '+'.$country_code.$the_number;
                    // parent::debug2("local number: trunk_code:NULL, the_number:$the_number");
                    break;
                }
                else
                {
                    // replace the trunk code for the country with plus and the country's international dialcode
                    $count_repl = 0;
                    $the_number = preg_replace("/^$trunk_code/", "+$country_code", $the_number, 1, $count_repl);
                    // parent::debug2("local number: trunk_code:$trunk_code, the_number:$the_number");
                    if ($count_repl == 1)
                    {
                        break;
                    }
                }
            }
        }

        parent::debug2("number after first if: '$the_number'");

        // check to see if a plus is at the beginning of the number, if not report a warning
        if (substr($the_number, 0, 1) !== '+')
        {
            // the above logic could not locate any country info in the number (trunk_code, coutnry_code, exit_dialcode)
            // this indicates that the nomalization was not successful
            $this->_normalize_success = FALSE;
            parent::info("WARNING: Number normalization failed: original = '$the_number_original' normalized = '$the_number'");
            parent::info("WARNING: the_number:'$the_number' trunk_code:'$trunk_code' country_code:'$country_code' exit_dialcode_array:'".str_replace("    ", "", str_replace("\n", " ", print_r($exit_dialcode_array, 1)))."'");

            if (strcmp($the_number_original, $the_number) === 0)
            {
                parent::info("WARNING: number after normalization seems to be identical, maybe the number is already normalized");
            }
            //parent::debug2('exit_dialcode_array', print_r($exit_dialcode_array,TRUE));
        }
        else
        {
            $this->_normalize_success = TRUE;
            $the_number = substr($the_number, 1);
        }

        parent::debug2("returning e.164 normalized number: '$the_number'");
        parent::debug2("<");

        return $the_number;
    }



    /**
     * a custom function for sorting dialcodes according to length.
     *
     * Longest ones (from a string length perspective) end up at the beginning of the
     * array and shorter ones end up higher.
     * Beginning = lowest index ($array[0]), End = highest index ($array[0 + x])
     *
     * NULL values are always at the end (highest index)
     **/
    private function _sort_dialcode($nr_a, $nr_b)
    {
        if (is_null($nr_a))
        {
            return 1;
        }

        $len_a = strlen($nr_a);
        $len_b = strlen($nr_b);

        if ($len_a < $len_b)
        {
            return 1;
        }
        elseif ($len_a > $len_a)
        {
            return -1;
        }
        else
        {
            return 0;
        }
    }


    /**
     * Returns a string representing the state of the object
     *
     * @param boolean return_result deprecated! a flag stating if the result should be returned as (default FALSE)
     * @param boolean force_calculation a flag which forces the object to calculate all number formats before explaining itself (default FALSE)
     * @return array array containing an explanation of the object's variables
     */
    public function explain($return_result = FALSE, $force_calculation = FALSE)
    {
        parent::debug2("");

        if ($force_calculation === TRUE)
        {
            $this->_process_all_formats();
        }

        $explanation = array ();
        $explanation["_country_3_letter"]                = $this->_country_3_letter;
        $explanation["_input_number"]                    = $this->_input_number;
        $explanation["_validated_number"]                = $this->_validated_number;
        $explanation["_international_number"]            = $this->_international_number;
        $explanation["_international_number_normalized"] = $this->_international_number_normalized;
        $explanation["_number_normalized"]               = $this->_number_normalized;
        if ($this->_normalize_success === TRUE)
        {
            $explanation['_normalize_success']           = 'TRUE';
        }
        else
        {
            $explanation['_normalize_success']           = 'FALSE';
        }
        $explanation["_local_number"]                    = $this->_local_number;
        $explanation["_all_formats"]                     = $this->_all_formats;
        $explanation["_country_code"]                    = $this->_country_code;
        $explanation["_region_code"]                     = $this->_region_code;
        $explanation["_subscriber_number"]               = $this->_subscriber_number;
        $explanation["_trunk_code"]                      = $this->_trunk_code;
        $explanation["_exit_dialcode"]                   = $this->_exit_dialcode;
        $explanation["_error_list"]                      = $this->get_error("all");

        parent::debug2("Object: Phone Number -> ".preg_replace("/\n/", "\n ", print_r($explanation, TRUE)));
        return $explanation;
    }

}
?>
