<?php

namespace Tinned\Tests\Libphono\Service;

use Tinned\Libphono\DataProvider\SQLiteDataProvider;
use Tinned\Libphono\Service\LibphonoService;

/**
 * 
 * @author Tyler Ashton
 * @version 0.3
 * 
 * @package framework
 * 
 * Test script
 * 
**/
class GetNumberCountryTest extends \PHPUnit\Framework\TestCase
{

    // array of arrays in the form :
    //      array('no' => '16145554444', 'cc' => 1, 'ok' => 'USA'))
    // no - the number
    // cc - the country code of the number
    // ok - the expected response
    protected $test_cases = array(
        array('no' => '16145554444',   'ok' => 'USA'),
        array('no' => '13124567891',   'ok' => 'USA'),
        array('no' => '390399909169',  'ok' => 'ITA'),
        array('no' => '393920000087',  'ok' => 'ITA'),
        array('no' => '50370813738',   'ok' => 'SLV'),
        array('no' => '38640784494',   'ok' => 'SVN'),
        array('no' => '14163611000',   'ok' => 'CAN'),
        array('no' => '34677331843',   'ok' => 'ESP'),
        array('no' => '4917691320507', 'ok' => 'DEU'),
        array('no' => '4369917200100', 'ok' => 'AUT'),
        array('no' => '447550664778',  'ok' => 'GBR'),
        array('no' => '441534664778',  'ok' => 'JEY'),
        array('no' => '441624664778',  'ok' => 'IMN'),
        array('no' => '12684445555',   'ok' => 'ATG'),
        array('no' => '556181183884',  'ok' => 'BRA'),
        array('no' => '48503501785',   'ok' => 'POL'),
        array('no' => '573134411648',  'ok' => 'COL'),
        array('no' => '77774447712',   'ok' => 'KAZ'),
        array('no' => '77776755557',   'ok' => 'KAZ'),
        array('no' => '77774881279',   'ok' => 'KAZ'),
        array('no' => '79093230851',   'ok' => 'RUS'),
        array('no' => '420605222527',  'ok' => 'CZE'),
        array('no' => '50255279339',   'ok' => 'GTM'),
        array('no' => '7636755557',    'ok' => 'KAZ'),
        array('no' => '9039299518300', 'ok' => 'CYP'),
        array('no' => '35799518300',   'ok' => 'CYP'),
        array('no' => '905384383548',  'ok' => 'TUR'),
        array('no' => '905384383548',  'ok' => 'TUR'),
        array('no' => '18493095637',   'ok' => 'DOM'),
        array('no' => '18297746900',   'ok' => 'DOM'),
        array('no' => '18094313676',   'ok' => 'DOM'),
        array('no' => '18493095637',   'ok' => 'DOM'),
        array('no' => '18297746900',   'ok' => 'DOM'),
        array('no' => '18094313676',   'ok' => 'DOM'),
        array('no' => '23412779000',   'ok' => 'NGA'),
        array('no' => '99912345678',   'ok' => 'ZZZ'),
        array('no' => '699444444444',  'ok' => 'ZZZ'),
        array('no' => '889123456789',  'ok' => 'ZZZ'),
    );

    protected $dbPath;

    public function setUp()
    {
        parent::setUp(); // TODO: Change the autogenerated stub
        $this->dbPath = dirname(__FILE__) . '/../../../../../resources/Country_Information.sqlite3';
    }

    public function test_getNumberCountry()
    {
        $service = new LibphonoService(
            new SQLiteDataProvider($this->dbPath)
        );

        $result_count = count($this->test_cases);
        for ($i = 0; $i < $result_count; ++$i) {
            $result = $service->getNumberCountry($this->test_cases[$i]['no']);
            $this->assertEquals($this->test_cases[$i]['ok'], $result, "Test $i failed...");
        }
    }
}
