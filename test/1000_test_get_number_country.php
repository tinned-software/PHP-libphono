<?php
/*******************************************************************************
 * 
 * @author Tyler Ashton
 * @version 0.2
 * 
 * @package framework
 * 
 * General Framework initialisation
 * 
*******************************************************************************/

require_once(dirname(__FILE__).'/../config/php_config.php');
require_once(dirname(__FILE__).'/../src/functions/get_number_country.php');

$GLOBALS['DBG']->info('*** Starting file '.basename(__FILE__));


echo "<b>Test '".basename(__FILE__)."' ... Start </b><br/>\n";


function kMemReport() {
	global $mem_usage, $mem_delta, $mem_delta_step, $mem_start;
	$mem_usage = memory_get_usage();
	$mem_delta_step = $mem_usage - $mem_start - $mem_delta - 1440; // 1440 is internal cost
	$mem_delta = $mem_usage - $mem_start;
	echo "[Mem: ".$mem_usage." Delta: ".$mem_delta." Step Delta = ".$mem_delta_step."]<br>";
}

	global $mem_start;
	global $mem_delta;
	global $mem_delta_step;
	$mem_start = memory_get_usage();
	kMemReport();

	echo "______________________<br>\n";

// ===============================================================
// STEP 1: select all phone numbers
// ===============================================================

	// select all numbers from phonebook.

	$debug_string = "Generating test cases";
	echo $debug_string."<br>\n";
	$GLOBALS['DBG']->debug2($debug_string);

	// array of arrays in the form :
	// 		array('no' => '16145554444', 'cc' => 1, 'ok' => 'USA'))
	// no - the number
	// cc - the country code of the number
	// ok - the expected response

	$test_cases = array(
		array('no' => '16145554444', 'cc' => '1', 'ok' => 'USA'),
		array('no' => '13124567891', 'cc' => '1', 'ok' => 'USA'),
		array('no' => '390399909169', 'cc' => '39', 'ok' => 'ITA'),
		array('no' => '393920000087', 'cc' => '39', 'ok' => 'ITA'),
		array('no' => '50370813738', 'cc' => '503', 'ok' => 'SLV'),
		array('no' => '38640784494', 'cc' => '386', 'ok' => 'SVN'),
		array('no' => '14163611000', 'cc' => '1', 'ok' => 'CAN'),
		array('no' => '34677331843', 'cc' => '34', 'ok' => 'ESP'),
		array('no' => '4917691320507', 'cc' => '49', 'ok' => 'DEU'),
		array('no' => '4369917200100', 'cc' => '43', 'ok' => 'AUT'),
		array('no' => '447550664778', 'cc' => '44', 'ok' => 'GBR'),
		array('no' => '441534664778', 'cc' => '44', 'ok' => 'JEY'),
		array('no' => '441624664778', 'cc' => '44', 'ok' => 'IMN'),
		array('no' => '12684445555', 'cc' => '1', 'ok' => 'ATG'),
		array('no' => '556181183884', 'cc' => '55', 'ok' => 'BRA'),
		array('no' => '48503501785', 'cc' => '44', 'ok' => 'POL'),
		array('no' => '573134411648', 'cc' => '57', 'ok' => 'COL'),
		array('no' => '77774447712', 'cc' => '7', 'ok' => 'KAZ'),
		array('no' => '77776755557', 'cc' => '7', 'ok' => 'KAZ'),
		array('no' => '77774881279', 'cc' => '7', 'ok' => 'KAZ'),
		array('no' => '79093230851', 'cc' => '7', 'ok' => 'RUS'),
		array('no' => '420605222527', 'cc' => '420', 'ok' => 'CZE'),
		array('no' => '50255279339', 'cc' => '502', 'ok' => 'GTM'),
		array('no' => '7636755557', 'cc' => '7', 'ok' => 'KAZ'),
		array('no' => '9039299518300', 'cc' => '90', 'ok' => 'CYP'),
		array('no' => '35799518300', 'cc' => '90', 'ok' => 'CYP'),
		array('no' => '905384383548', 'cc' => '90', 'ok' => 'TUR'),
		array('no' => '905384383548', 'cc' => '90', 'ok' => 'TUR'),
		array('no' => '18493095637', 'cc' => '1', 'ok' => 'DOM'),
		array('no' => '18297746900', 'cc' => '1', 'ok' => 'DOM'),
		array('no' => '18094313676', 'cc' => '1', 'ok' => 'DOM'),
		array('no' => '18493095637', 'cc' => '1849', 'ok' => 'DOM'), // emulates old incorrect app behavior.
		array('no' => '18297746900', 'cc' => '1829', 'ok' => 'DOM'), // emulates old incorrect app behavior.
		array('no' => '18094313676', 'cc' => '1809', 'ok' => 'DOM'), // emulates old incorrect app behavior.
		array('no' => '23412779000', 'cc' => '234', 'ok' => 'NGA'), 
		array('no' => '99912345678', 'cc' => '999', 'ok' => 'ZZZ'),
		array('no' => '699444444444', 'cc' => '6', 'ok' => 'ZZZ'),
		array('no' => '889123456789', 'cc' => '89', 'ok' => 'ZZZ'),
		);


	echo "<a href=\"javascript:document.getElementById('test_cases').style.display='block';\" >show cases</a><br/>\n";
	echo "<div id='test_cases' style='display:none;'>\n";
	$i = 0;
	foreach($test_cases as $case)
	{
		echo 'case '.$i++.': number:' . $case['no'] . ' with country code:' . $case['cc'] . ' should return:' . $case['ok'] . "<br/>\n";
	}
	unset($i);
	echo "</div>\n";

	echo "______________________<br>\n";
	 
// ===============================================================
// STEP 2: make new array with data from db result
// ===============================================================

	$debug_string = "Performing tests...";
	echo $debug_string."<br>\n";
	$GLOBALS['DBG']->debug2($debug_string);

	$result_count = count($test_cases);

	echo "<table>\n";
	for ($i=0; $i<$result_count; ++$i)
	{
		echo sprintf("<tr><td width='200px'>Test case %03u : </td><td width='200px'>", $i);

		$error = false;
		$error_list = array();
		$result = get_number_country($test_cases[$i]['no'], $test_cases[$i]['cc'], $error, $error_list);

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
// READY!
// ===============================================================


echo "<b>Test '".basename(__FILE__)."' ... End.</b><br/><br/>\n";


?>
