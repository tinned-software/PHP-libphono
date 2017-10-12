<?php

namespace Tinned\Libphono\DataProvider;

/**
 * Class ArrayDataProvider
 *
 * @package Tinned\Libphono\DataProvider
 */
class ArrayDataProvider implements DataProviderInterface
{
    /**
     * @param string $isoCode
     *
     * @return array
     */
    public function fetchDataForISOCode($isoCode)
    {
        // step 2: fetch from the database the exit_dialcode, international_dialcode, extended_dialcode, trunk_dialcode for this specific country
//        parent::debug2("trying to fetch data from data source");

        // just in case someone forgot to pass us a database name we use a default value here
        $db_name = '';
        if(isset($this->_sql_db) === TRUE && is_null($this->_sql_db) === FALSE)
        {
            $db_name = $this->_sql_db . '.';
        }
        // get the country_iso, country exit_dialcode, country international_dialcode, country extended_dialcode
        // trunk dialcode for a specific country to normalize the received number into international format before
        // further processing is possible

        $select_field_list = array(
            self::INPUT_ISO_3166_ALPHA2 => 'country_2_letter',
            self::INPUT_ISO_3166_ALPHA3 => 'country_3_letter',
        );

        if(array_key_exists($this->_iso_3166_code_type, $select_field_list) === TRUE)
        {
            $select_field = $select_field_list[$this->_iso_3166_code_type];
        }
        else
        {
            throw new \Exception(302, "Fetching data failed, _iso_3166_code given to class is unknown");
            $this->_error = TRUE;
            return $return_array;
        }

        $query = "SELECT ".$db_name."Country_Exit_Dialcode.country_3_letter, \n"
            ."       ".$db_name."Country_Exit_Dialcode.exit_dialcode, \n"
            ."       ".$db_name."Country_Dialcodes.international_dialcode, \n"
            ."       ".$db_name."Country_Dialcodes.extended_dialcode, \n"
            ."       ".$db_name."Country_Trunk_Code.trunk_dialcode \n"
            ."FROM   ".$db_name."Country_Exit_Dialcode, \n"
            ."       ".$db_name."Country_Dialcodes, \n"
            ."       ".$db_name."Country_Trunk_Code, \n"
            ."       ".$db_name."Country_Codes \n"
            ."WHERE  ".$db_name."Country_Exit_Dialcode.country_3_letter = Country_Codes.country_3_letter AND \n"
            ."       ".$db_name."Country_Dialcodes.country_3_letter = Country_Codes.country_3_letter AND \n"
            ."       ".$db_name."Country_Trunk_Code.country_3_letter = Country_Codes.country_3_letter AND \n"
            ."       ".$db_name."Country_Codes.{$select_field} = '{}'";
        //." -- object_id:".spl_object_hash($this).' '.microtime(). ' '.$this->_input_number;

        //parent::debug($query);
        // send query to db and get result
        $errno = NULL;
        $errtext = NULL;

        $query_result = $this->_sql_obj->get_query_result($query, $errno, $errtext);
        $this->_database_hits++;

        if($errno !== NULL)
        {
            throw new \Exception(301, "Fetching data failed. Internal sql error ".$errno.': '.$errtext);
//            $this->_error = TRUE;
        }

        if(isset($query_result['data']) === TRUE)
        {
            $return_array = $query_result;
        }
    }
}
