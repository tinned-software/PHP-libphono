<?php

namespace Tinned\Libphono\Service;
use Tinned\Libphono\DataProvider\DataProviderInterface;
use Tinned\Libphono\PhoneNumber;

/**
 * Class LibphonoService
 *
 * @package Tinned\Libphono\Service
 */
class LibphonoService
{

    /**
     * @var \Tinned\Libphono\DataProvider\DataProviderInterface
     */
    protected $dataProvider;

    public function __construct(DataProviderInterface $dataProvider)
    {
        $this->dataProvider = $dataProvider;
    }

    /**
     * @param string $number
     * @return \Tinned\Libphono\PhoneNumber
     */
    public function getPhoneNumber($number, $isoCode, $iso3166CodeType)
    {
        $ret = new PhoneNumber($this->dataProvider);
        $ret->set_input_number($number);
        $ret->set_normalized_country($isoCode, $iso3166CodeType);
        return $ret;
    }

    /**
     * @param $number
     * @param $error
     * @param $error_list
     *
     * @return bool|null|string
     */
    public function getNumberCountry($number)
    {
        if (isset($number) === FALSE) {
            throw new \LogicException('missing parameters, cannot continue to process number');
        }

//        $sql_db = new SQLite_3($GLOBALS['config_libphono_connection_string'], $GLOBALS['config_debug_level_class'], $GLOBALS['DBG']);

        //
        // attempt to find the country of the number using the number
//        $query_dialcode = "SELECT * FROM Country_Dialcodes WHERE '{$sql_db->escape_string($number)}' LIKE extended_dialcode || '%' ORDER BY LENGTH(extended_dialcode) DESC";
        $this->dataProvider->getCountryForNumber($number);

        // send query to db and get result
        $errno = $errtext = NULL;
//        $query_dialcode_result = $sql_db->get_query_result($query_dialcode, $errno, $errtext);

//        if($errno != NULL)
//        {
//            // Error while query execution
//            $error_list[] = array('error_text' => "Internal Error", 'error_code' => "300.$errno");
//            $error = TRUE;
//            $GLOBALS['DBG']->error("SQL: $errno, $errtext");
//            return FALSE;
//        }

        $return = 'ZZZ';

//        if($query_dialcode_result['count'] >= 1)
//        {
//            $GLOBALS['DBG']->debug2_array('matche(s) results found: ', $query_dialcode_result['data']);
//            $return = $query_dialcode_result['data'][0]['country_3_letter'];
//        }
//        else
//        {
//            $GLOBALS['DBG']->info("No country found for number:{$number}");
//        }

        return $return;
    }

}
