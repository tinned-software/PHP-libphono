<?php

namespace Tinned\Libphono;

use Tinned\Libphono\DataProvider\DataProviderInterface;

/**
 *
 * @author     Apostolos Karakousis ktolis@ktolis [dot] gr
 * @author     Tyler Ashton tdashton@gmail [dot] com
 * @version    1.3.8
 *
 * @package    general_scripts
 * @subpackage mobile_service
 * @copyright  http://opensource.org/licenses/gpl-license.php GNU Public License
 *
 * Phone Number representation class
 *
**/

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
 * 101 ... "iso_3166_code was not set"
 * 102 ... "iso_3166_code was not string"
 * 103 ... "parameter iso_3166_code was not a 3 letter string"
 * 104 ... "input_number was not set"
 * 105 ... "input_number was not string"
 * 150 ... "sql object not specified in costructor"
 * 201 ... "iso_3166_code was set to NULL"
 * 202 ... "input_number was NOT set"
 * 301 ... "Fetching data failed. Internal sql error ".$errno
 * 302 ... "_iso_3166_code type not recognized, see class constants for allowed values"
 * 401 ... "missing parameters, cannot continue to process number"
 * 402 ... "false exit dialcode array, cannot continue to process number"
 * 403 ... "tried to compare two non-phone number objects"
**/
class PhoneNumber
{
    ////////////////////////////////////////////////////////////////////////////
    // PROPERTIES of the class
    ////////////////////////////////////////////////////////////////////////////

    const INPUT_ISO_3166_ALPHA2 = 'alpha2';
    const INPUT_ISO_3166_ALPHA3 = 'alpha3';

    /**
     * @var \Tinned\Libphono\DataProvider\DataProviderInterface
     */
    protected $dataProvider;

    /**
     * Internal counter used to track how many times the object accessed the datasource.
     *
     * @access private
     *
     * @var integer
    **/
    private $_database_hits = 0;

    // normalization input parameters
    
    /**
     * Internal ISO 3611 country name. Used for normalization.
     *
     * @access private
     *
     * @var string
    **/
    private $_iso_3166_code = null;

    /**
     * Internal ISO 3611 country code type.
     * See class constants for possible values. Default is 'unknown';
     *
     * @access private
     *
     * @var string
    **/
    private $_iso_3166_code_type = 'unknown';
    
    /**
     * Internal input number parameter. Used for the normalization.
     *
     * @access private
     *
     * @var string
    **/
    private $_input_number = null;
    
    // database state information
    
    /**
     * Indicates whether information has been fetched from the database.
     *
     * @access private
     *
     * @var boolean
     **/
    private $_db_dialcodes_fetched = false;
    
    // normalized formats
    
    /**
     * The input number in the validated format will be stored in the variable.
     *
     * @access private
     *
     * @var string
    **/
    private $_validated_number = null;
    
    /**
     * The input number in the international format will be stored in the variable.
     *
     * @access private
     *
     * @var string
    **/
    private $_international_number = null;
    
    /**
     * The input number in the international normalized format will be stored in the variable.
     *
     * @access private
     *
     * @var string
    **/
    private $_international_number_normalized = null;
    
    /**
     * The input number in the normalized format will be stored in the variable.
     *
     * @access private
     *
     * @var string
    **/
    private $_number_normalized = null;
    
    /**
     * The input number in the local format will be stored in the variable.
     *
     * @access private
     *
     * @var string
    **/
    private $_local_number = null;
    
    // internal properties for normalization control
    
    /**
     * This holds all the processed formats. Used for debugging purposes.
     *
     * @access private
     *
     * @var array
    **/
    private $_all_formats = null;
    
    /**
     * Internal flag, signifies if the normalization was successful or not.
     *
     * @access private
     *
     * @var string
    **/
    private $_normalize_success = false;
    
    // database caches
    
    /**
     * Storage cache for the database-fetched country code field. Needed for the normaliztion.
     *
     * @access private
     *
     * @var string
    **/
    private $_country_code = null;
    
    /**
     * Storage cache for the database-fetched trunk code field. Needed for the normaliztion.
     *
     * @access private
     *
     * @var string
    **/
    private $_trunk_code = null;
    
    /**
     * Storage cache for the database-fetched exit dialcodes code field. Needed for the normaliztion.
     *
     * @access private
     *
     * @var array
    **/
    private $_exit_dialcode = null;
    
    // normalization process parameters (internal)
    
    /**
     * Internal array used for recognizing pause symbols in input number.
     * http://en.wikipedia.org/wiki/E.123
     *
     * @access private
     *
     * @var array
    **/
    private $_pause_characters = array(',','p','w');
    
    /**
     * Internal replacement value for the paus character
     *
     * @access private
     *
     * @var string
    **/
    private $_pause_character_internal = 'p'; // replace all pause characters with this value
    // private $_formatting_characters = array('(',')','-','/',' ', '+', '.', '~');
    // private $_dialable_characters = array('0','1','2','3','4','5','6','7','8','9','*','#','p');
    
    /**
     * Internal value representing the maximum number of characters an input number may contain
     *
     * @access private
     *
     * @var integer
    **/
    private $_max_input_length = 32;
    
    // error handling
    
    /**
     * Internal variable used for error handling.
     *
     * @access private
     *
     * @var string
    **/
    private $_error = null;
    
    /**
     * Support level parameter. Used for error reporting/handling.
     *
     * @access private
     *
     * @var integer
    **/
    public $support_level = 1;
    
    // Variables to hold last error code and text
    //    private $_errno = NULL;
    //    private $_api_error_code = NULL;
    //    private $_errtext = NULL;
    
    
    
    ////////////////////////////////////////////////////////////////////////////
    // CONSTRUCTOR & DESCTRUCTOR methods of the class
    ////////////////////////////////////////////////////////////////////////////

    /**
     * PhoneNumber constructor.
     *
     * @param \Tinned\Libphono\DataProvider\DataProviderInterface $dataProvider
     */
    public function __construct(DataProviderInterface $dataProvider)
    {
        $this->dataProvider = $dataProvider;
    }



    ////////////////////////////////////////////////////////////////////////////
    // SET methods to set class Options
    ////////////////////////////////////////////////////////////////////////////
    
    
    
    /**
     * Set normalized country property.
     *
     * This method is used to set the 3 letter country code that is used to normalize
     * the number. If for some reason the parameters are not correctly set a boolean FALSE
     * is returned and an error is reported within the class.
     *
     * Currently supported input formats are: ISO 3166 Alpha2 or Alpha3 codes, e.g.
     * US, or USA, DE or DEU.
     *
     * @access public
     *
     * @see    get_normalized_country()
     *
     * @param  string $iso_code ISO 3166 conform code
     * @param  string $type optional ISO 3166 type, default is ISO 3166 Alpha3. Use class constants.
     * @return boolean
    **/
    public function set_normalized_country($iso_3166_code = null, $type = self::INPUT_ISO_3166_ALPHA3)
    {
        // must be string
        if (isset($iso_3166_code) === false) {
            throw new \LogicException(101, "iso_3166_code was not set");
        }
        
        if (is_string($iso_3166_code) === false) {
            throw new \LogicException(102, "iso_3166_code was not string, it was : '$iso_3166_code'");
        }

        $validate_regex_list = array(
            self::INPUT_ISO_3166_ALPHA3 => "/^[a-zA-Z]{3}$/",
            self::INPUT_ISO_3166_ALPHA2 => "/^[a-zA-Z]{2}$/",
            'unknown' => '/^$/'
            );

        if (array_key_exists($type, $validate_regex_list) == true) {
            $validate_regex = $validate_regex_list[$type];
        } else {
            $validate_regex = $validate_regex_list['unknown'];
        }

        // check the $iso_3166_code using the appropriate regex set above
        if (preg_match($validate_regex, $iso_3166_code) !== 1) {
            throw new \LogicException(103, "parameter iso_3166_code did not conform to the given input parameter");
        }

//        parent::debug2("Called with parameters: iso_3166_code = '".$iso_3166_code."'");
        
        // clear cache for old iso_3166_code (both if was set or was set to NULL)
        if ($this->_db_dialcodes_fetched === true && $this->_iso_3166_code !== $iso_3166_code) {
//            parent::debug2("iso_3166_code was modified: forcing a flush on all calculations and cached database information");
            $this->_unset_all();
            $this->_unset_all_db();
        }
        
        $this->_iso_3166_code = $iso_3166_code;
        $this->_iso_3166_code_type = $type;
        
        return true;
    }
    
    
    
    /**
     * Set the input number property.
     *
     * This method is used to set the input_number property that is used to normalize
     * the number. If for some reason the parameters are not correctly set a boolean FALSE
     * is returned and an error is reported within the class.
     *
     * @access public
     *
     * @see    get_input_number()
     *
     * @param  string  input_number the input number
     * @return boolean
    **/
    public function set_input_number($input_number = null)
    {
        // must be string
        if (isset($input_number) === false) {
            throw new \LogicException(104, "input_number was not set");
        }
        
        if (is_string($input_number) === false) {
            throw new \LogicException(105, "input_number was not string");
        }
        
        if (strlen($input_number) > $this->_max_input_length) {
            $input_number = substr($input_number, 0, $this->_max_input_length);
//            parent::debug("WARNING: input number:$input_number longer than {$this->_max_input_length}, truncated");
        }
//        parent::debug2("called with parameters: input_number = '".$input_number."'");
        
        // reset ALL variables if was not null to reset the normalization
        if ($this->_db_dialcodes_fetched === true && strcmp($this->_input_number, $input_number) !== 0) {
//            parent::debug2("input_number was modified, forcing a flush on all calculations");
            $this->_unset_all();
        }
        
        $this->_input_number = $input_number;
        
        return true;
    }
    
    
    
    ////////////////////////////////////////////////////////////////////////////
    // GET methods to get class options for normalization procedure
    ////////////////////////////////////////////////////////////////////////////
    
    
    
    /**
     * Get country property.
     *
     * This method is used to get the 3 letter country code that is used to normalize
     * the number. Will trigger an error if not set first.
     *
     * @access public
     *
     * @see    set_normalized_country()
     *
     * @param  void
     * @return string
    **/
    public function get_normalized_country()
    {
        if (isset($this->_iso_3166_code) === false) {
            throw new \LogicException(201, "iso_3166_code was set to NULL");
        }
        
//        parent::debug2("returning iso_3166_code: '".$this->_iso_3166_code."', iso_3166_code_type: '".$this->_iso_3166_code_type."'");
        
        return $this->_iso_3166_code;
    }
    
    
    
    /**
     * Get input_number property.
     *
     * This method is used to get the original input_number that was used for
     * the normalization. Will trigger an error if not set first.
     *
     * @access public
     *
     * @see set_input_number()
     *
     * @param  void
     * @return string
    **/
    public function get_input_number()
    {
        if (isset($this->_input_number) === false) {
            throw new \LogicException(202, "input_number was NOT set");
        }
//        parent::debug2("returning input_number: '".$this->_input_number."'");
        
        return $this->_input_number;
    }
    
    
    
    /**
     * Get validated input_number property.
     *
     * This method is used to get the validated input_number that was used for
     * the normalization. Will trigger calculation if not already set.
     *
     * @access public
     *
     * @see get_input_number()
     * @see set_input_number()
     *
     * @param  void
     * @return string
    **/
    public function get_validated_input_number()
    {
        if (isset($this->_validated_number) === false) {
//            parent::debug2("_validated_number was set to NULL, recalculating");
            
            $tmp_number = $this->get_input_number();
            
            // replace *known pause character with an internal representation
            $pause_replace_preg = '/[' . implode('', $this->_pause_characters) . ']/';
            $tmp_number = preg_replace($pause_replace_preg, $this->_pause_character_internal, $tmp_number);
            // finish replacements
            $tmp_number = preg_replace("/[^\d^p\+]/", '', $tmp_number);
            
            $this->_validated_number = $tmp_number;
        }
        
//        parent::debug2("returning new validated_number: '".$this->_validated_number."'");
        
        return $this->_validated_number;
    }
    
    
    
    /**
     * Get "international" format of the input_number.
     *
     * This method is used to get the number in the "international" format.
     * Will trigger calculation if not already set.
     *
     * @access public
     *
     * @param  void
     * @return string
    **/
    public function get_international_number()
    {
        if (isset($this->_international_number) === false) {
//            parent::debug2("_international_number was set to NULL, recalculating");
            
            // make sure we got the validated number and the country code
            $this->_iso_3166_code = $this->get_normalized_country();
            $this->_validated_number = $this->get_validated_input_number();
            
            // make sure we have the exit code for this country
            if (isset($this->_exit_dialcode) === false) {
                $this->_fetch_dialcodes();
            }
            
            $this->_international_number = $this->_exit_dialcode[0].$this->get_normalized_number();
        }
        
//        parent::debug2("international number = '".$this->_international_number."'");
        
        return $this->_international_number;
    }
    
    
    
    /**
     * Get "normalized international" format of the input_number.
     *
     * This method is used to get the number in the "normalized international" format.
     * Will trigger calculation if not already set.
     *
     * @access public
     *
     * @param  void
     * @return string
    **/
    public function get_normalized_international_number()
    {
        if (isset($this->_international_number_normalized) === false) {
//            parent::debug2("_international_number_normalized was set to NULL, recalculating");
            
            // make sure we got the validated number and the country code
            $this->_iso_3166_code = $this->get_normalized_country();
            $this->_validated_number = $this->get_validated_input_number();
            $this->_international_number_normalized = "+".$this->get_normalized_number();
        }
        
//        parent::debug2("returning normalized international number: '$this->_international_number_normalized'");
        
        return $this->_international_number_normalized;
    }
    
    
    
    /**
     * Get "normalized" format of the input_number.
     *
     * This method is used to get the number in the "normalized" (E.164) format.
     * Will trigger calculation if not already set.
     *
     * @access public
     *
     * @param  void
     * @return string
    **/
    public function get_normalized_number()
    {
        if (isset($this->_number_normalized) === false) {
//            parent::debug2("_number_normalized was set to NULL, recalculating");
            
            // make sure we got the validated number AND the country code
            $this->_iso_3166_code = $this->get_normalized_country();
            $this->_validated_number = $this->get_validated_input_number();
            
            if (isset($this->_exit_dialcode) === false) {
                $success_fetching = $this->_fetch_dialcodes();
            }
            
            $this->_number_normalized = $this->_normalize_number($this->_validated_number, $this->_trunk_code, $this->_country_code, $this->_exit_dialcode);
        }
        
//        parent::debug2("returning '".$this->_number_normalized."'");
        
        return $this->_number_normalized;
    }
    
    
    
    /**
     * This method is used to get the number in "normalized" E.164 format if successful
     *
     * This method serves the same function as the get_normalized_number() method, with
     * the additional feature that it only returns a number if the normalization was
     * deemed as a success in the class. Otherwise it returns an empty string.
     *
     * @access public
     *
     * @see    get_normalized_number()
     *
     * @param  void
     * @return string the normalized number in string format, can be empty if normalization failed
    **/
    public function get_normalized_number_only()
    {
        $number_return = $this->get_normalized_number();
        
        if ($this->_normalize_success === true) {
            return $number_return;
        } else {
            return "";
        }
    }
    
    
    
    /**
     * Get "local" format of the input_number.
     *
     * This method is used to get the number in the "local" format.
     * Will trigger calculation if not already set.
     *
     * @access public
     *
     * @param  void
     * @return string
    **/
    public function get_local_number()
    {
        if (isset($this->local_number) === false) {
//            parent::debug2("local_number was set to NULL, recalculating");
            
            // make sure we got the validated number and the country code
            $this->_iso_3166_code = $this->get_normalized_country();
            $this->_validated_number = $this->get_validated_input_number();
            
            // make sure we have the exit code for this country
            if (isset($this->_exit_dialcode) === false) {
                $this->_fetch_dialcodes();
            }
            
            $this->_local_number = $this->_trunk_code[0].substr($this->get_normalized_number(), strlen($this->_country_code));
        }
        
        return $this->_local_number;
    }
    
    
    
    /**
     * Dump all possible formats in the log and return them as an array.
     *
     * This method invokes calculation of all possible formats in the class and
     * returns them as an associative array.
     *
     * @access public
     *
     * @param  void
     * @return array
    **/
    public function dump_formats()
    {
        if (isset($this->_all_formats) === false) {
//            parent::debug2("_all_formats variable was set to NULL, recalculating");
            $this->_process_all_formats();
        }
        
        return $this->_all_formats;
    }
    
    
    
    ////////////////////////////////////////////////////////////////////////////
    // PRIVATE methods of the class
    ////////////////////////////////////////////////////////////////////////////
    
    
    
    /**
     * This method processes the input_number and stores in valid format.
     *
     * This method accesses the following tables:
     *  Country_Exit_Dialcode
     *  Country_Dialcodes
     *  Country_Trunk_Code
     * to fetch the required information for the country specified in the
     * parameter $this->_iso_3166_code and $this->_iso_3166_code_type.
     *
     * If the correct fields could be retrieved then we can continue
     * processing the validated input number and normalize it.
     *
     * @access private
     *
     * @param  void
     * @return boolean
    **/
    private function _fetch_dialcodes()
    {
        if ($this->_db_dialcodes_fetched === true) {
//            parent::debug2('database information already fetched, returning');
            return true;
        }
        
        // step 1: make sure we have the iso 3166 code AND the validated version of the number
        $this->_iso_3166_code = $this->get_normalized_country();
        
        if (isset($this->_iso_3166_code) === true /*&& isset($this->_validated_number) === TRUE*/ && (isset($this->_country_code) === false)) {
            // get array of information from the datasource
            $dialcode_info = $this->_fetch_info_sql();

//                parent::debug2("Found CLI Country information: ". print_r($dialcode_info['data'], TRUE));
//                parent::debug2("trunk code = '".$dialcode_info['data'][0]['trunk_dialcode']."'");
//                parent::debug2("trunk type= '".gettype($dialcode_info['data'][0]['trunk_dialcode'])."'");

            // can only be one international dialcode (i.e. 43 for Austria, 1 for USA)
            $this->_country_code = $dialcode_info[0]['international_dialcode'];
            // can be multiple trunk and exit codes for each dialplan
            $this->_exit_dialcode = array();
            $this->_trunk_code = array();

            // interate over the results to extract all trunk and exit codes
            for ($i = 0; $i < count($dialcode_info); $i++) {
                if (in_array($dialcode_info[$i]['exit_dialcode'], $this->_exit_dialcode) === false) {
                    $this->_exit_dialcode[] = $dialcode_info[$i]['exit_dialcode'];
                }
                if (in_array($dialcode_info[$i]['trunk_dialcode'], $this->_trunk_code) === false) {
                    $this->_trunk_code[] = $dialcode_info[$i]['trunk_dialcode'];
                }
            }
            // sort array (order is important for normalization)
            usort($this->_trunk_code, array($this, '_sort_dialcode'));

            $this->_db_dialcodes_fetched = true;
            
            return true;
        } else {
//            parent::debug2("could not find country code in countries table! !!!");
            return false;
        }
    }
    

    /**
     * Generate query to get the data using the ISO input given.
     *
     * @access private
     *
     * @param void
     * @return array()
    **/
    private function _fetch_info_sql()
    {
        return $this->dataProvider->fetchDataForISOCode($this->_iso_3166_code, $this->_iso_3166_code_type);
    }
    
    
    
    /**
     * Force process all formats.
     *
     * This method forces the generation of all formats possible in the class
     * and stores them accordingly.
     *
     * @access private
     *
     * @param void
    **/
    private function _process_all_formats()
    {
        $this->_all_formats = array(
                "INPUT_NUMBER"                    => $this->get_input_number(),
                "INPUT_NUMBER_VALIDATED"          => $this->get_validated_input_number(),
                "INTERNATIONAL_NUMBER"            => $this->get_international_number(),
                "INTERNATIONAL_NUMBER_NORMALIZED" => $this->get_normalized_international_number(),
                "NORMALIZED_NUMBER"               => $this->get_normalized_number(),
                "LOCAL_NUMBER"                    => $this->get_local_number()
        );
    }
    
    
    
    /**
     * Reset all class properties, non database related
     *
     * This method unsets all internal variables in order to do a recalculation
     * on them on the next run of any processing function.
     *
     * @access private
     *
     * @param void
    **/
    private function _unset_all()
    {
//        parent::debug2("setting all internal variables to NULL");
        
        $this->_validated_number = null;
        $this->_international_number = null;
        $this->_international_number_normalized = null;
        $this->_number_normalized = null;
        $this->_local_number = null;
        $this->_normalize_success = false;
        
        $this->_all_formats = null;
    }
    
    
        
    /**
     * Reset all class properties which were fetched from the database
     *
     * This method unsets all internal variables fetched from the database
     *
     * @access private
     *
     * @param void
    **/
    private function _unset_all_db()
    {
//        parent::debug2("setting all internal database variables to NULL");
        
        $this->_db_dialcodes_fetched = false;
        
        $this->_country_code = null;
        $this->_trunk_code = null;
        $this->_exit_dialcode = null;
    }
    
    
    
    /**
     * Convert an input number to a valida input number format
     *
     * This method is used to convert an unprocessed input number to a validated number format.
     * We have two separate methods. The first one uses the _normalize_number method and the second
     * one is simply using a replacement table (is work in progress).
     *
     * @access private
     *
     * @param void
     * @return string
    **/
    private function _input_to_valid($number, $method = 0)
    {
//        parent::debug2("called with parameters: number = [".$number."] method=".$method."]");
        
        if (isset($method) === false) {
            $method = 0;
        }
        
        switch ($method) {
            case 0:
                $success_fetching = $this->_fetch_dialcodes();
                if ($success_fetching) {
//                    parent::debug2("fetched dialcodes");
                    // trunk code for now is not used, so it's set to NULL
                    $number = $this->_normalize_number($number, null, $this->_country_code, $this->_exit_dialcode);
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
                    'p' => '7','q' => '7','r' => '7', 's' => '7','Q' => '7','R' => '2','S' => '7',
                    't' => '8','u' => '8','v' => '8', 'T' => '8','U' => '8','V' => '8',
                    'x' => '9','y' => '9','z' => '9', 'X' => '9','Y' => '9','Z' => '9'
                );
                
                // Common Phone Keypads for Alpha code translation (we only use the international standard)
                //                           1   2   3   4   5   6    7    8   9    0
                // International Standard        ABC DEF GHI JKL MNO PQRS TUV WXYZ
                // North American Classic        ABC DEF GHI JKL MN  PRS  TUV WXY
                // Australian Classic        QZ  ABC DEF GHI JKL MNO PRS  TUV WXY
                // UK Classic                    ABC DEF GHI JKL MN  PRS  TUV WXY    OQ
                // Mobile 1                      ABC DEF GHI JKL MN  PRS  TUV WXY    OQZ

                // fetch search and replace arrays
                $search = array_keys($search_replace_map);
                $replace = array_values($search_replace_map);
                
                //  first pass: simple converter
                $number = str_replace($search, $replace, $number);
                
                // second pass: keep only digits, after the "+" sign if it exists
                if (substr($number, 0, 1) !== '+') {
                    $number = preg_replace('~[^\d]~', '', $number);
                } else {
                    $number = "+".preg_replace('[^\d]', '', substr($number, 1));
                }
            break;
        }
        
//        parent::debug2("exiting");
        
        return $number;
    }
    
    
    
    /**
     * Internal logic for normalizing numbers
     *
     * This method returns normalized telephone numbers based on the given information.
     * e.g., for Austria (with trunk code zero and 43 country code:
     *      06761111111 is converted to 436761111111 (i.e., the trunk code is
     *          replaced with country code)
     *
     * @access private
     *
     * @param  string     $the_number          the number to normalize
     * @param  array      $trunk_code_array    trunk code to use
     * @param  string     $country_code        country code to use
     * @param  array$     exit_dialcode_array  exit dialcode of country to use
     *
     * @return string                          the normalized number
     */
    private function _normalize_number($the_number = null, $trunk_code_array = null, $country_code = null, $exit_dialcode_array = null)
    {
//        parent::debug2("the_number:$the_number trunk_code:$trunk_code_array country_code:$country_code exit_dialcode_array:$exit_dialcode_array");
        
        if (isset($the_number) === false || (isset($trunk_code_array) === false && is_null($trunk_code_array) !== true) || isset($country_code) === false || isset($exit_dialcode_array) === false) {
            throw new \LogicException(401, "missing parameters, cannot continue to process number");
        }
        
        if (is_array($exit_dialcode_array) === false || is_array($trunk_code_array) === false) {
//            parent::debug2("the_number:$the_number trunk_code:$trunk_code_array country_code:$country_code exit_dialcode_array:$exit_dialcode_array");
            throw new \LogicException(402, "false exit or trunk dialcode array, cannot continue to process number");
        }
        
        // sort the array from lowest to highest dialcode length
        //   (NULLs must be at the highest index in the array)
        usort($trunk_code_array, array($this, '_sort_dialcode'));
        
//        parent::debug2("exit_dialcode_array\n".print_r($exit_dialcode_array, TRUE));
//        parent::debug2("trunk_code_array\n".print_r($trunk_code_array, TRUE));
        // save the number for logging
        $the_number_original = $the_number;
        
//        parent::debug2("number before normalization: '$the_number_original'");
        // remove international exit dialcodes such as + or 00
        foreach ($exit_dialcode_array as $exit_dialcode) {
            $the_number = preg_replace("/^([+]|$exit_dialcode)/", '+', $the_number);
        }
        
        // if the country has a trunk code remove it
        if (substr($the_number, 0, 1) !== '+') {
            // loop through valid trunk codes, exiting immediately when one matches.
            foreach ($trunk_code_array as $trunk_code) {
                if (is_null($trunk_code) === true) {
                    // add plus and the country's international dialcode
                    $the_number = '+'.$country_code.$the_number;
                    // parent::debug2("local number: trunk_code:NULL, the_number:$the_number");
                    break;
                } else {
                    // replace the trunk code for the country with plus and the country's international dialcode
                    $count_repl = 0;
                    $the_number = preg_replace("/^$trunk_code/", "+$country_code", $the_number, 1, $count_repl);
                    // parent::debug2("local number: trunk_code:$trunk_code, the_number:$the_number");
                    if ($count_repl === 1) {
                        break;
                    }
                }
            }
        }
        // check to see if a plus is at the beginning of the number, if not report a warning
        if (substr($the_number, 0, 1) !== '+') {
            // the above logic could not locate any country info in the number (trunk_code, coutnry_code, exit_dialcode)
            // this indicates that the nomalization was not successful
            $this->_normalize_success = false;
//            parent::debug("WARNING: Number normalization failed: original = '$the_number_original' normalized = '$the_number'");
//            parent::debug("WARNING: the_number:'$the_number' trunk_code:'$trunk_code' country_code:'$country_code' exit_dialcode_array:'".str_replace("    ", "", str_replace("\n", " ", print_r($exit_dialcode_array, 1)))."'");
            
            if (strcmp($the_number_original, $the_number) === 0) {
//                parent::debug("WARNING: number after normalization seems to be identical, maybe the number is already normalized");
            }
        } else {
            $this->_normalize_success = true;
            $the_number = substr($the_number, 1);
        }
//        parent::debug2("returning e.164 normalized number: '$the_number'");
        
        return $the_number;
    }
    
    
    
    /**
     * A custom method for sorting dialcodes according to length.
     *
     * Longest ones (from a string length perspective) end up at the beginning of the
     * array and shorter ones end up higher.
     * Beginning = lowest index ($array[0]), End = highest index ($array[0 + x])
     * NULL values are always at the end (highest index)
     *
     * @access private
     *
     **/
    private function _sort_dialcode($nr_a, $nr_b)
    {
        if (is_null($nr_a)) {
            return 1;
        }
        
        $len_a = strlen($nr_a);
        $len_b = strlen($nr_b);
        
        if ($len_a < $len_b) {
            return 1;
        } elseif ($len_a > $len_a) {
            return -1;
        } else {
            return 0;
        }
    }
    
    
    
    ////////////////////////////////////////////////////////////////////////////
    // PUBLIC methods of the class
    ////////////////////////////////////////////////////////////////////////////
    
    
    
    /**
     * Helper function to compare two objects.
     *
     * This method returns the object as a string in order to achieve comparison
     * this should work: $is_equal = ((string)$d === $b); // true
     *
     * @access public
     *
     * @param  void
     * @return string
    **/
    public function __toString()
    {
        return (string) $this->get_normalized_international_number();
    }
    
    
    
    /**
     * Compare this object with another Phone_Number object
     *
     * This method compares the normalized number of this object against the
     * normalized number of another object. If both are equal they are considered
     * equal.
     *
     * @todo finish / test this implementation !!
     *
     * @access public
     *
     * @param  PhoneNumber &$number a phone number object with which to compare
     * @return boolean               TRUE if matched, FALSE if the comparison failed or numbers do not match
    **/
    public function is_equal_to_number(&$number)
    {
        if (is_object($number) === true && get_class($this) !== get_class($number)) {
            throw new \LogicException(403, "class of input parameter does not match: ".get_class($this).' !== '.get_class($number));
        }
        if (strcmp($this->get_normalized_number(), $number->get_normalized_number()) === 0) {
//            parent::debug2("returning TRUE");
            return true;
        } else {
//            parent::debug2("returning FALSE");
            return false;
        }
    }
    
    
    
    /**
     * Returns a string representing the state of the object
     *
     * Calling this method will return to the user a string with all internal properties
     * if set. It will NOT trigger recalculation of any properties that would occur by
     * using the get_* methods.
     *
     * @access public
     *
     * @param  boolean return_result     (deprecated) a flag stating if the result should be returned as (default FALSE but will always return the result)
     * @param  boolean force_calculation a flag which forces the object to calculate all number formats before explaining itself (default FALSE)
     * @return array                     array containing an explanation of the object's variables
     */
    public function explain($return_result = false, $force_calculation = false)
    {
        if ($force_calculation === true) {
            $this->_process_all_formats();
        }

        $explanation = array();
        $explanation["_iso_3166_code"]                   = $this->_iso_3166_code;
        $explanation["_iso_3166_code_type"]              = $this->_iso_3166_code_type;
        $explanation["_input_number"]                    = $this->_input_number;
        $explanation["_validated_number"]                = $this->_validated_number;
        $explanation["_international_number"]            = $this->_international_number;
        $explanation["_international_number_normalized"] = $this->_international_number_normalized;
        $explanation["_number_normalized"]               = $this->_number_normalized;
        if ($this->_normalize_success === true) {
            $explanation['_normalize_success']           = 'TRUE';
        } else {
            $explanation['_normalize_success']           = 'FALSE';
        }
        $explanation["_local_number"]                    = $this->_local_number;
        $explanation["_all_formats"]                     = $this->_all_formats;
        $explanation["_country_code"]                    = $this->_country_code;
        $explanation["_trunk_code"]                      = $this->_trunk_code;
        $explanation["_exit_dialcode"]                   = $this->_exit_dialcode;
        $explanation["_database_hits"]                   = $this->_database_hits;
        $explanation["_error_list"]                      = parent::get_all_errors();
        
//        parent::debug2("Object: Phone Number -> ".preg_replace("/\n/", "\n ", print_r($explanation, TRUE)));
        return $explanation;
    }
}
