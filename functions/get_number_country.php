<?php
/**
 * This script's functionality will be moved into the Phone_Number class itself
 * in the next release of libphono. Relying on it is discouraged.
 * 
 * A script to find the country code for a particular telephone number
 * 
 * 
 * @author Tyler Ashton
 * @version 1.0.3
 * 
 * @package    deprecated
 * @subpackage phone_number
 * 
 * @todo move this functionality into the phone number class (as a static method?)
 * 
 **/



require_once(dirname(__FILE__).'/../../PHP-Tinned-SQL/classes/sqlite3.class.php');




$GLOBALS['DBG']->info('*** Starting file '.basename(__FILE__));

/**
 * A function to determine the three letter country code of a particular telephone number
 * 
 * This function will initiate a CLI verification check for a particular user CLI.
 * The function will generate and send the verification code using the appropriate
 * method ( via call or sms ) and return the status of the call or sms.
 * 
 * 
 * @param $number string the full telephone number to check 
 * @param &$error boolean TRUE or FALSE depending on whether an error was encountered or not 
 * @param &$error_list array an array containing the error which was reported by the function 
 * @return mixed ZZZ when no country found, three letter country code of the given phone number, or FALSE if an error ocurred
 **/
function get_number_country($number, &$error, &$error_list)
{
    if(isset($number) === FALSE)
    {
        $GLOBALS['DBG']->error('missing parameters, cannot continue to process number');
        return NULL;
    }
    $GLOBALS['DBG']->debug2("input parameters: $number");

    $sql_db = new SQLite_3($GLOBALS['config_libphono_connection_string'], $GLOBALS['config_debug_level_class'], $GLOBALS['DBG']);

    //
    // attempt to find the country of the number using the number
    $query_dialcode = "SELECT * FROM Country_Dialcodes WHERE '{$sql_db->escape_string($number)}' LIKE extended_dialcode || '%' ORDER BY LENGTH(extended_dialcode) DESC";

    // send query to db and get result
    $errno = $errtext = NULL;
    $query_dialcode_result = $sql_db->get_query_result($query_dialcode, $errno, $errtext);

    if($errno != NULL)
    {
        // Error while query execution
        $error_list[] = array('error_text' => "Internal Error", 'error_code' => "300.$errno");
        $error = TRUE;
        $GLOBALS['DBG']->error("SQL: $errno, $errtext");
        return FALSE;
    }

    $return = 'ZZZ';

    if($query_dialcode_result['count'] >= 1)
    {
        $GLOBALS['DBG']->debug2_array('matche(s) results found: ', $query_dialcode_result['data']);
        $return = $query_dialcode_result['data'][0]['country_3_letter'];
    }
    else
    {
        $GLOBALS['DBG']->info("No country found for number:{$number}");
    }
    
    return $return; 
}
?>
