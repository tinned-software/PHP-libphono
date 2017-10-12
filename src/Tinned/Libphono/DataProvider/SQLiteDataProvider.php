<?php

namespace Tinned\Libphono\DataProvider;

/**
 * Class ArrayDataProvider
 *
 * @package Tinned\Libphono\DataProvider
 */
class SQLiteDataProvider implements DataProviderInterface
{
    const INPUT_ISO_3166_ALPHA2 = 'alpha2';
    const INPUT_ISO_3166_ALPHA3 = 'alpha3';

    /**
     * @var \PDO
     */
    protected $dbObject;

    /**
     * @var string
     */
    protected $dbPath;

    /**
     * SQLiteDataProvider constructor.
     *
     * @param string $dbPath
     */
    public function __construct($dbPath)
    {
        $this->dbPath = $dbPath;
    }

    /**
     *
     */
    protected function initialize()
    {
        if (is_null($this->dbObject)) {
            $this->dbObject = new \PDO("sqlite:" . $this->dbPath);
        }
    }

    /**
     * @param string $isoCode
     * @param string $iso3166CodeType
     *
     * @return array
     */
    public function fetchDataForISOCode($isoCode, $iso3166CodeType)
    {
        $this->initialize();
        // step 2: fetch from the database the exit_dialcode, international_dialcode, extended_dialcode, trunk_dialcode for this specific country

        // get the country_iso, country exit_dialcode, country international_dialcode, country extended_dialcode
        // trunk dialcode for a specific country to normalize the received number into international format before
        // further processing is possible

        $select_field_list = array(
            self::INPUT_ISO_3166_ALPHA2 => 'country_2_letter',
            self::INPUT_ISO_3166_ALPHA3 => 'country_3_letter',
        );

        if (array_key_exists($iso3166CodeType, $select_field_list) === true) {
            $select_field = $select_field_list[$iso3166CodeType];
        } else {
            throw new \Exception("Fetching data failed, _iso_3166_code given to class is unknown", 302);
        }

        $query = "SELECT Country_Exit_Dialcode.country_3_letter, \n"
            ."       Country_Exit_Dialcode.exit_dialcode, \n"
            ."       Country_Dialcodes.international_dialcode, \n"
            ."       Country_Dialcodes.extended_dialcode, \n"
            ."       Country_Trunk_Code.trunk_dialcode \n"
            ."FROM   Country_Exit_Dialcode, \n"
            ."       Country_Dialcodes, \n"
            ."       Country_Trunk_Code, \n"
            ."       Country_Codes \n"
            ."WHERE  Country_Exit_Dialcode.country_3_letter = Country_Codes.country_3_letter AND \n"
            ."       Country_Dialcodes.country_3_letter = Country_Codes.country_3_letter AND \n"
            ."       Country_Trunk_Code.country_3_letter = Country_Codes.country_3_letter AND \n"
            ."       Country_Codes.{$select_field} = '{$isoCode}'";

        $query_result = $this->dbObject->query($query, \PDO::FETCH_ASSOC);

        if (!$query_result) {
            throw new \Exception("Fetching data failed. Internal sql error", 301);
        }

        return $query_result->fetchAll();
    }

    /**
     * @param string $number
     *
     * @return string
     */
    public function getCountryForNumber($number)
    {
        $this->initialize();

        //
        // attempt to find the country of the number using the number
        $query = "SELECT * FROM Country_Dialcodes WHERE '{$number}' LIKE extended_dialcode || '%' ORDER BY LENGTH(extended_dialcode) DESC";

        // send query to db and get result
        $query_result = $this->dbObject->query($query, \PDO::FETCH_ASSOC);

        if (!$query_result) {
            throw new \Exception('PDO Error');
        }

        $return = 'ZZZ';
        $res = $query_result->fetchAll();
        if (count($res) > 0) {
            $return = $res[0]['country_3_letter'];
        }

        return $return;
    }
}
