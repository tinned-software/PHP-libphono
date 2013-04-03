<?php
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

define("ROOTPATH", dirname(__FILE__)."/../");
require_once(ROOTPATH.'config/php_config.php');
require_once(ROOTPATH.'src/classes/sqlite3.class.php');
require_once(ROOTPATH.'src/classes/phone_number.class.php');

$GLOBALS['DBG']->info('*** Starting file '.basename(__FILE__));

echo "<b>Test '".basename(__FILE__)."' ... Start </b><br/>\n";
echo "______________________<br>\n";

// ===============================================================
// STEP 1: Generate a list of test cases
// ===============================================================

$debug_string = "Generating test cases";
echo $debug_string."<br>\n";
$GLOBALS['DBG']->debug2($debug_string);

// array of arrays in the form :
//      array('no' => '16145554444', 'cc' => 1, 'ok' => 'USA'))
// no - the number
// cc - the country code of the number
// ok - the expected response

$test_cases = array(
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


echo "<a href=\"javascript:document.getElementById('test_cases').style.display='block';\" >show cases</a><br/>\n";
echo "<div id='test_cases' style='display:none;'>\n";
foreach($test_cases as $case)
{
    echo 'Number:' . $case['no'] . ' should return:' . $case['ok'] . "<br/>\n";
}
echo "</div>\n";

echo "______________________<br>\n";

// ===============================================================
// STEP 2: Perform tests
// ===============================================================

$debug_string = "Performing tests... (first column is calculated result, second is expected)";
echo $debug_string."<br>\n";
$GLOBALS['DBG']->debug2($debug_string);

$result_count = count($test_cases);

$sql_db = new SQLite_3($GLOBALS['config_libphono_connection_string'], $GLOBALS['config_debug_level_class'], $GLOBALS['DBG']);

echo "<table>\n";
for($i=0; $i<$result_count; ++$i)
{
    echo sprintf("<tr><td width='200px'>Test case %03u : </td><td width='200px'>", $i);
    
    $error = false;
    $error_list = array();
    $result_obj = Phone_Number::object_with_e164_number($test_cases[$i]['no'], $GLOBALS['config_debug_level_class'], $GLOBALS['DBG'], $sql_db);
    $result = $result_obj->get_normalized_country();
    
    if($error === true)
    {
        echo "error while executing function: </td>". print_r($error_list);
    }
    
    echo "$result == {$test_cases[$i]['ok']}</td>";
    if($test_cases[$i]['ok'] == $result)
    {
        echo "<td width='200px' style='color:green;'>passed...</td>";
    }
    else
    {
        echo "<td width='200px' style='color:red;'>failed</td>";
    }
    
    echo " </tr>\n";
}
echo "</table>\n";

// ===============================================================
// DONE!
// ===============================================================

echo "<b>Test '".basename(__FILE__)."' ... End.</b><br/><br/>\n";

?>
