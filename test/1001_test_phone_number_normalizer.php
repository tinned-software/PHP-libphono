<?php
/**
 * 
 * @author Tyler Ashton
 * @version 0.4
 * 
 * @package framework
 * 
 * General Framework initialisation
 *
 * 
**/

// include required files
define("ROOTPATH", dirname(__FILE__)."/../");
require_once(ROOTPATH.'config/php_config.php');
require_once(ROOTPATH.'src/classes/sqlite3.class.php');
require_once(ROOTPATH.'src/classes/phone_number.class.php');

// start logging
$GLOBALS['DBG']->info('*** Starting file '.basename(__FILE__));

//$sql_db = new MySQL($conn_string, $debug_level, $GLOBALS['DBG']);
$sql_db = new SQLite_3($GLOBALS['config_libphono_connection_string'], $GLOBALS['config_debug_level_class'], $GLOBALS['SQL']);

echo "<html>\n";
echo "<head>\n";
echo "<script type='text/javascript'>\n";
echo "function toggleElement(elementId)
{
    var elementToToggle = document.getElementById(elementId)
    elementToToggle.style.display=(elementToToggle.style.display == 'none' ? 'block' : 'none');
}";

echo "</script>\n";
echo "</head>\n";
echo "<p style='font-weight:700'>This script test the behavior of the normalizaion logic in the phone number class<p/>\n";
echo "<b>Test '".basename(__FILE__)."' ... Start </b><br/>\n";
echo "______________________<br>\n";

// ===============================================================
// STEP 1: read test cases from csv
// ===============================================================

$test_file_path = dirname(__FILE__).'/1001_phone_number_testcases.csv';
$test_file_handle = fopen($test_file_path, 'r');
if($test_file_handle === FALSE)
{
    echo "error opening test csv file: $test_file_path";
    die();
}

// array of arrays in the form :
//      array('no' => '16145554444', 'country' => 1, 'ok' => 'USA', 'debug' => true))
// no    - the number
// country - the country code of the number
// ok    - the expected response
// debug - optional boolean whether extra debug output should be produced
// known - known problem case
$test_cases = array();

// debug output
$debug_string = "Generating test cases";
echo $debug_string."\n";
$GLOBALS['DBG']->debug2($debug_string);


$row = 1;
$test_case_index = 0;
while(($data = fgetcsv($test_file_handle, 1000, $delimiter = ',', $enclosure = '"')) !== FALSE)
{
    $num = count($data);
    $GLOBALS['DBG']->debug2("$num fields in line $row : $num");
    $row++;
    if($row <= 2)
    {
        continue;
    }
    
    //$GLOBALS['DBG']->debug2("no field : {$data[0]}");
    $test_cases[$test_case_index]['no'] = $data[0];
    
    //$GLOBALS['DBG']->debug2("country_3-letter field : {$data[1]}");
    $test_cases[$test_case_index]['country'] = $data[1];
    
    //$GLOBALS['DBG']->debug2("ok field : {$data[2]}");
    $test_cases[$test_case_index]['ok'] = $data[2];
    
    if(isset($data[3]))
    {
        //$GLOBALS['DBG']->debug2("debug field : {$data[3]}");
        $test_cases[$test_case_index]['debug'] = ($data[3] == 0 ? false : true);
    }
    
    if(isset($data[4]))
    {
        //$GLOBALS['DBG']->debug2("known field : {$data[4]}");
        $test_cases[$test_case_index]['known'] = ($data[4] == 0 ? false : true);
    }
    
    if(isset($data[5]))
    {
        $test_cases[$test_case_index]['fail_reason'] = $data[5];
    }
    
    $GLOBALS['DBG']->debug2("test case with : {$data[0]},{$data[1]},{$data[2]},{$data[3]},{$data[4]},{$data[5]}");

    $test_case_index++;
}

fclose($test_file_handle);

//$GLOBALS['DBG']->debug2_array('array', $test_cases);

// ===============================================================
// STEP 2: do tests and produce output
// ===============================================================

echo "<a href=\"javascript:toggleElement('test_cases')\" >show cases</a><br/>\n";
echo "<div id='test_cases' style='display:none;'>\n";
foreach($test_cases as $case)
{
    echo 'number:' . $case['no'] . ' with country code:' . $case['country'] . ' should return:' . $case['ok'] . "<br/>\n";
}
echo "</div>\n";
echo "<div id='custom_test' style='display:none;'>\n";
echo "<form method='post'>";
echo "<p>";
echo "  Specify a Phone Number:";
echo "  <input type='text' name='nr_custom' id='nr_custom' value='+18001236547' size='25' />";
echo "  </p>";
echo "  <p>";
echo "  Specify a Default Country:";
echo "  <input type='text' name='nr_custom_iso' id='nr_custom_iso' value='USA' size='2' />";
echo "  </p>";
echo "  <p>Specify Input Type:";
echo " <select name='nr_custom_iso_type' id='nr_custom_iso_type'> ";
echo "    <option value='alpha3' selected='selected'>ISO 3166 Alpha3</option>";
echo "    <option value='alpha2'>ISO 3166 Alpha2</option>";
echo " </select> ";
echo "  </p>";
//echo "  <p>";
//echo "  Specify a Carrier Code:";
//echo "  <input type='text' name='carrierCode' id='carrierCode' size='2' />";
//echo "  (optional, only valid for some countries)";
//echo "  </p>";
echo "  <input type='button' value='Submit' onclick='normalizeNumber()' />";
echo "  <input type='reset' value='Reset' onClick='resetOutput()'/>";
echo "  <p>";
echo "  <table>";
echo "<tr><td style='font-weight:600'>output:</td><td style='font-weight:600'>debug output:</td></tr>";
echo "";
echo "<tr><td>";
echo "  <textarea id='custom_output' rows='10' cols='80'></textarea>";
echo "  </td><td>";
echo "  <textarea id='custom_debug_output' rows='10' cols='40'></textarea>";
echo "  </td></tr></table>";
echo "  </p>";
echo "</form>";
echo "</div>\n";
echo "______________________<br>\n";


// ===============================================================
// STEP 3: make new array with data from db result
// ===============================================================

$debug_string = "Performing tests...";
echo $debug_string."<br>\n";
$GLOBALS['DBG']->debug2($debug_string);

$result_count = count($test_cases);
$output_debug_info = FALSE;

echo "<table border='2px'>\n";
for($i=0; $i<$result_count; ++$i)
{
    echo sprintf("<tr><td width='150px'>Test case %03u : </td><td width='400px'>", $i);
    
    $debug = 0;
    if(isset($test_cases[$i]['debug']) && $test_cases[$i]['debug'] === TRUE)
    {
        $debug = 2;
        $output_debug_info = TRUE;
    }
    
    $fail_reason = NULL;
    if(isset($test_cases[$i]['fail_reason']) && empty($test_cases[$i]['fail_reason']) === FALSE)
    {
        $GLOBALS['DBG']->debug('failed because...');
        $fail_reason = $test_cases[$i]['fail_reason'];
    }
    
    $phone_number_obj = new Phone_Number($debug, $GLOBALS['DBG'], $sql_db, NULL);
    $phone_number_obj->set_normalized_country($test_cases[$i]['country']);
    $phone_number_obj->set_input_number($test_cases[$i]['no']);
    
    $result = $phone_number_obj->get_normalized_number();
    
    if($phone_number_obj->has_error() !== FALSE)
    {
        echo "error while executing function: </td>". print_r($phone_number_obj->get_error('all'), TRUE);
    }
    
    echo "$result == {$test_cases[$i]['ok']}</td><td width='240px'>(input: {$test_cases[$i]['no']}, {$test_cases[$i]['country']})</td>";
    if($test_cases[$i]['ok'] == $result)
    {
        echo "<td width='100px' style='color:green;font-weight:500'>passed...</td>";
    }
    else
    {
        echo "<td width='100px' style='color:red;font-weight:700'>failed";
        if(isset($test_cases[$i]['known']) && $test_cases[$i]['known'] === TRUE)
        {
            echo "***";
        }
        echo "</td>";
    }
    
    if(empty($fail_reason) === FALSE)
    {
        echo "<td width='100px' style='text-align:left;'>";
        echo "<a href=\"javascript:toggleElement('test_{$i}')\">why?</a>";
        echo "<div id='test_{$i}' style='display:none;'>{$fail_reason}</div></td>";
    }
    else
    {
        echo "<td width='100px'>&nbsp;</td>";
    }
    
    if($debug > 0)
    {
        echo "<td width='35px' style='text-align:center;color:green;font-weight:700'>dbg*</td>";
    }
    else
    {
        echo "<td width='35px'>&nbsp;</td>";
    }
    
    echo " </tr>\n";
    $phone_number_obj = NULL;
    unset($phone_number_obj);
}
echo "</table>\n";

// ===============================================================
// DONE!
// ===============================================================

echo "<p style='font-style:italic'>***indicates that this is a known problem case</p>\n";
if($output_debug_info)
{
    echo "<p style='font-style:italic'>*Indicates extra debug output was produced in the logfile.<p/>\n";
}
echo "<b>Test '".basename(__FILE__)."' ... End.</b><br/><br/>\n";
?>
