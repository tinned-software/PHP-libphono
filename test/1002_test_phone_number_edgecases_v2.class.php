<?php
/*******************************************************************************
 *
 * @author     Apostolos Karakousis <apostolos.karakousis@tinned-software.net>
 * @version 0.1
 *
 * @package general scripts
 *
 * Phone Number Tests
 *
*******************************************************************************/

echo "<b>Test '".basename(__FILE__)."' ... </b><br/>\n";

require_once(dirname(__FILE__).'/../config/php_config.php');
require_once(dirname(__FILE__).'/../src/classes/phone_number.class.php');
require_once(dirname(__FILE__).'/../src/classes/mysql.class.php');
require_once(dirname(__FILE__).'/../src/classes/sqlite3.class.php');

/*
 * log to browser
 *         $log = new Debug_Logging(false, null, true);
 * log to file
 *         $log = new Debug_Logging(true, dirname(__FILE__)."/../../log/general_scripts_test", false);
 * log to global log
 *         $log = $GLOBALS['DBG']
 */

// global preparation for this script
$GLOBALS['DBG']->info('*** Starting file '.basename(__FILE__));

//$sql_db = new MySQL($conn_string, $debug_level, $GLOBALS['DBG']);
$sql_db = new SQLite_3($GLOBALS['config_libphono_connection_string'], $GLOBALS['config_debug_level_class'], $GLOBALS['SQL']);

class kTest
{
    public $test_table = NULL;
    public $log = NULL;
    public $endresults_to_log = FALSE;

    function kTest($log, $endresults_to_log = FALSE)
    {
        $this->log = $log;
        $this->endresults_to_log = $endresults_to_log;
    }
    function start($test_number, $test_description, $operations_count, $test_expected)
    {
        $this->test_table[$test_number]["description"] = $test_description;
        $this->test_table[$test_number]["operations_count"] = $operations_count;
        $this->test_table[$test_number]["expected"] = $test_expected;
        $this->test_table[$test_number]["start_time"] = "";
        $this->test_table[$test_number]["stop_time"] = "";
        $this->test_table[$test_number]["memory_start"] = memory_get_usage();
        $this->test_table[$test_number]["memory_stop"] = "";
        $this->test_table[$test_number]["memory_consumption"] = "";
        $this->test_table[$test_number]["memory_peak"] = "";
        $this->test_table[$test_number]["duration"] = "";
        $this->test_table[$test_number]["performance"] = "";

        if ($this->endresults_to_log === TRUE)
        {
            $this->log->debug2("--------------------------------------------------------------");
            $this->log->debug2("TEST [$test_number] START");
            $this->log->debug2("--------------------------------------------------------------");
        }

        $this->test_table[$test_number]["start_time"] = microtime(TRUE);
    }
    function stop($test_number)
    {
        $this->test_table[$test_number]["stop_time"] = microtime(TRUE);
        $this->test_table[$test_number]["memory_stop"] = memory_get_usage();
//        $this->test_table[$test_number]["memory_peak"] = memory_get_peak_usage();
//        $this->test_table[$test_number]["duration"] = number_format($this->test_table[$test_number]["stop_time"] - $$this->test_table[$test_number]["start_time"],4);
        $this->test_table[$test_number]["duration"] = number_format($this->test_table[$test_number]["stop_time"] - $this->test_table[$test_number]["start_time"],8);
        $this->test_table[$test_number]["performance"] = number_format($this->test_table[$test_number]["operations_count"] / $this->test_table[$test_number]["duration"],3);
        $this->test_table[$test_number]["memory_consumption"] = $this->test_table[$test_number]["memory_stop"] - $this->test_table[$test_number]["memory_start"];

        if ($this->endresults_to_log)
        {
            $this->log->debug2("--------------------------------------------------------------");
            $this->log->debug2("TEST [$test_number] STOP");
            $this->log->debug2("--------------------------------------------------------------");
        }
    }
    function print_result($test_number, $result)
    {
        echo "Test [$test_number] : ".$this->test_table[$test_number]["description"]." : \t <b>";
        if ($result === TRUE)
        {
            echo "<font color=green>PASSED</font></b><br/>\n";
        }
        else
        {
            echo "<font color=red>FAILED</font></b><br/>\n";
        }

        if ($this->endresults_to_log === TRUE)
        {
            $this->log->debug2("test number: ".$test_number);
            $this->log->debug2("description: ".$this->test_table[$test_number]["description"]);
            $this->log->debug2("count: ".$this->test_table[$test_number]["operations_count"]);
            $this->log->debug2("expected: ".$this->test_table[$test_number]["expected"]);
            //$this->log->debug2("start_time: ".$this->test_table[$test_number]["start_time"]);
            //$this->log->debug2("stop_time: ".$this->test_table[$test_number]["stop_time"]);
            //$this->log->debug2("memory_start: ".$this->test_table[$test_number]["memory_start"]);
            //$this->log->debug2("memory_stop: ".$this->test_table[$test_number]["memory_stop"]);
            $this->log->debug2("memory_consumption: ".$this->test_table[$test_number]["memory_consumption"]);
            //$this->log->debug2("peak_memory: ".$this->test_table[$test_number]["memory_peak"]);
            $this->log->debug2("duration: ".$this->test_table[$test_number]["duration"]);
            $this->log->debug2("performance: ".$this->test_table[$test_number]["performance"]);
        }
        $this->log->debug2("--------------------------------------------------------------");
    }
    function final_printout()
    {
        echo "<table border = 1>";
        echo "<tr>";
        echo "<td>No</td>";
        echo "<td>memory</td>";
        echo "<td>duration</td>";
        echo "<td>performance</td>";
        echo "</tr>";
        for ($i = 1; $i<sizeof($this->test_table)+1; ++$i)
        {
            echo "<tr>";
            echo "<td>".$i."</td>";
            echo "<td>".$this->test_table[$i]['memory_consumption']."</td>";
            echo "<td>".$this->test_table[$i]['duration']."</td>";
            echo "<td>".$this->test_table[$i]['performance']."</td>";
            echo "</tr>";

        }
        echo "</table>";
    }
}


$test = new kTest($GLOBALS['DBG'], FALSE);



// --------------------------------------------------------------------------------------------------------------------------------------------

// additional cases          input number
/*																					googleLibPhoneNumber            same   test code      source
    italia                  0412770869          ->        390412770869				390412770869                           DONE     13  
    colombia           		03 76123456 			->			610376123456 		5776123456                            OK        14
    finland (FI)             0 9 69661	    	->          						358 358 969661                        OK        15    http://www.tld.io/finland/tld/fi.php 
    australia		     0892588777   		->			                 			61892588777                           OK        16    http://www.tld.io/australia/tld/au.php
    australia                0383414111                                             61383414111                           OK        17    http://www.tld.io/australia/tld/au.php
	australia                01300732929                                            611300732929                          OK        18    http://www.tld.io/australia/tld/au.php
	colombia           		0916169961 			->			             			5716169961                            ΟΚ G      19    http://www.tld.io/colombia/tld/co.php
    andora                      875274                                              376875274                             OK        20    http://www.tld.io/andorra/tld/ad.php
    Montserrat (MS/MSR)     16644916386                                        1 664 4916386                           OK        21    http://www.tld.io/montserrat/tld/ms.php
    american samoa (AS/ASM) 16846335900										        16846335900 			              OK	    22    http://sadieshotels.com/
	brazil                  02125456500 			->			552125456500		552125456500                          OK        23
    brazil                  01155093505                                             551155093505                          OK        24    http://www.tld.io/brazil/tld/br.php
    turkmenistan  (TM/TKM)    812398729												99312398729                           OK        25    http://www.tld.io/turkmenistan/tld/tm.php
    turkmenistan     	      812381027												99312381027							  OK		26 	  http://www.cbt.tm/
	italia                   +393895140231                                          393895140231                                    27    from production case
    vatican city VA/VAT 06 69893461   3790669893461

    turkmenistan  (TM/TKM)    8p10*993812398729		?????										99312398729                                     25    http://www.tld.io/turkmenistan/tld/tm.php

*/

$tests = array(
    /* testid ISO3  input_number       expected_normalized  description googleresult  http://libphonenumber.googlecode.com/svn/trunk/javascript/i18n/phonenumbers/demo.html*/
    array(1, "ITA","3895140231",            "393895140231", "123",  "393895140231"),
    array(2, "ITA","00393895140231",        "393895140231", "123",  "393895140231"),

    array(3, "ITA","0412770869",            "390412770869", "123",  "390412770869"),
    array(4, "ITA","00390412770869",        "390412770869", "123",  "390412770869"),
    array(5, "ITA",'+393895140231',         "393895140231", "123",  "393895140231"),
    array(6, "ITA","00393895140231",        "393895140231", "123",  "393895140231"),

    array(7, "ITA","0393 123456",           "390393123456","123",   "390393123456"),
    array(8, "ITA","00390393123456",        "390393123456","123",   "390393123456"),

    array(9, "ITA","0393 0 123456",         "3903930123456","123",   "3903930123456"),
    array(10, "ITA","00390393123456",       "390393123456","123",   "390393123456"),

    array(11, "USA","6148895544",         "16148895544","123",   "16148895544"),
    array(12, "USA","16148895544",        "16148895544","123",   "16148895544"),
    array(13, "USA","01143680123456",     "43680123456","123",   "43680123456"),
    //array(14, "USA","116148895544",        "16148895544","123",   "16148895544"),


    array(50, "AND","875274",               "376875274",     "123", "376875274"),
    array(51, "AND","00376875274",          "376875274",     "123", "376875274"),

    array(100, "MSR","16644916386",         "16644916386", "123",   "16644916386"),
    array(101, "MSR","0111 664 4916386",   "16644916386", "123",   "16644916386"),

    array(150, "ASM","16846335900",        "16846335900", "123",    "16846335900"),

    array(200, "AUS","0892588777",         "61892588777",  "123",   "61892588777"),
    array(201, "AUS","0011 61 892588777", "61892588777", "123",    "61892588777"),

    array(205, "AUS","0383414111",         "61383414111",  "123",   "61383414111"),
    array(206, "AUS","001161 383414111", "61383414111",  "123",    "61383414111"),

    array(210, "AUS","01300732929",        "611300732929", "123",   "611300732929"),
    array(211, "AUS","001161 1300732929", "611300732929", "123",   "611300732929"),

    array(250, "FIN","0969661",            "358969661",    "123",   "358969661"),
    array(251, "FIN","9904315440624121",   "4315440624121", "123",  "4315440624121"),
    array(252, "FIN","994 43 15440624121", "4315440624121", "123",  "4315440624121"),
    array(253, "FIN","00 43 15440624121",  "4315440624121", "123",  "4315440624121"),

    array(300, "BRA","02125456500",        "552125456500", "123",   "552125456500"),
    array(301, "BRA","0021 55 2125456500", "552125456500", "123",  "552125456500"),

    array(305, "BRA","01155093505",        "551155093505", "123",   "551155093505"),
    array(306, "BRA","0023 55 1155093505", "551155093505", "123", "551155093505"),

    array(350, "TKM","812398729",          "99312398729", "123",    "99312398729"),
    array(351, "TKM","8p10 993 12398729", "99312398729", "123",    "99312398729"),

    array(352, "TKM","812381027",          "99312381027", "123",    "99312381027"),
    array(353, "TKM","8p10 993 12381027", "99312381027", "123",    "99312381027"),

    array(400, "COL","0376123456",           "5776123456",   "123", "5776123456"),
    array(402, "COL","00444 57 76123456",    "5776123456",   "123"),
    array(404, "COL","0916169961",           "5716169961",   "123", "5716169961"),
    array(406, "COL","0095716169961",      "5716169961",   "123", "5716169961"),
);

/* prepare html table */
$table = "<table border='1'  id='anyid' class='sortable'>";
$table .= '<tr>
    <td>test id</td>
    <td>ISO3</td>
    <td>input_number</td>
    <td>expected</td>
    <td>normalized</td>
    <td>validated</td>
    <td>normalized_international</td>
    <td>international</td>
    <td>google</td>
    </tr>';
$debug_cases = array (404);
foreach ($tests as $testcase)
{
    $test_id                  = $testcase[0];
    $test_input_country       = $testcase[1];
    $test_input_number        = $testcase[2];
    $test_expected_normalized = $testcase[3];
    $test_description         = $testcase[4];
    $test_google              = $testcase[5];

    $test->start($test_id, $test_description." [$test_input_country '$test_input_number' expecting: '$test_expected_normalized']", 1, "");
        // setup
        if (in_array($test_id, $debug_cases) === TRUE)
        {
            $number = new Phone_Number(2, $GLOBALS['DBG'], $sql_db);
        }
        else
        {
            $number = new Phone_Number(0, $GLOBALS['DBG'], $sql_db);
        }
        // input
        $number->set_input_number($test_input_number);
        $number->set_normalized_country($test_input_country);
        // results
        $result = $number->get_normalized_number();
    $test->stop($test_id);
    $test->print_result($test_id,
            isset($result) === TRUE && is_string($result) && $result === $test_expected_normalized
    );

    $table .= '<tr>
        <td>'.$test_id.'</td>
        <td>'.$test_input_country.'</td>
        <td>'.$test_input_number.'</td>
        <td>'.$test_expected_normalized.'</td>';
        if ($result === $test_expected_normalized)
        {
            $table .= '<td bgcolor="green">'.$result.'</td>';
        }
        else
        {
            $table .= '<td bgcolor="red">'.$result.'</td>';
        }
    $table .= '<td>'.$number->get_validated_input_number().'</td>
        <td>'.$number->get_normalized_international_number().'</td>
        <td>'.$number->get_international_number().'</td>';
        if ($result === $test_google)
        {
            $table .= '<td bgcolor="green">'.$test_google.'</td>';
        }
        else
        {
            $table .= '<td bgcolor="red">'.$test_google.'</td>';
        }

    $table .= '</tr>';
    // reset
    $test_id = $description = $operations_count = $test_input_number = $test_input_country = $test_expected_normalized = $number = $result = NULL;

}
$table .= "</table>";
echo '<script src="sortable.js"></script>';
echo "<hr>".$table;


// --------------------------------------------------------------------------------------------------------------------------------------------
echo "<br><hr> case 404 (colombia is a known issue)";
//$test->final_printout();

//echo "<pre>".print_r($test->test_table, TRUE)."</pre>";

echo "<br>";
echo "<b>Test '".basename(__FILE__)."' ... Finished.</b><br/><br/>\n";



?>
