<?php

namespace Tinned\Tests\Libphono\Service;

use Tinned\Libphono\DataProvider\SQLiteDataProvider;
use Tinned\Libphono\PhoneNumber;
use Tinned\Libphono\Service\LibphonoService;

/*
 * log to browser
 *         $log = new Debug_Logging(false, null, true);
 * log to file
 *         $log = new Debug_Logging(true, dirname(__FILE__)."/../../log/general_scripts_test", false);
 * log to global log
 *         $log = $GLOBALS['DBG']
 * write in performance log:
 *         $GLOBALS['PRF']->timer_start('T-'.crc32(__FILE__));
 *         $GLOBALS['PRF']->memory_start('M-'.crc32(__FILE__));
 *         $GLOBALS['PRF']->memory_show('Measurement at the beginning of file '.basename(__FILE__));
 */

// global preparation for this script
//$log = $GLOBALS['DBG'];
//$log->info('*** Starting file '.basename(__FILE__));
////$sql_db = new MySQL($conn_string, $debug_level, $GLOBALS['DBG']);
//$sql_db = new SQLite_3($GLOBALS['config_libphono_connection_string'], $GLOBALS['config_debug_level_class'], $GLOBALS['SQL']);

class PhoneNumberClassTest extends \PHPUnit\Framework\TestCase
{
    public $test_table = NULL;
    public $log = NULL;
    public $endresults_to_log = FALSE;
    
    function test_true()
    {
        $this->assertTrue(true);
    }

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
        
//        if($this->endresults_to_log === TRUE)
//        {
//            $this->log->debug2("--------------------------------------------------------------");
//            $this->log->debug2("TEST [$test_number] START");
//            $this->log->debug2("--------------------------------------------------------------");
//        }
        
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
        
//        if($this->endresults_to_log)
//        {
//            $this->log->debug2("--------------------------------------------------------------");
//            $this->log->debug2("TEST [$test_number] STOP");
//            $this->log->debug2("--------------------------------------------------------------");
//        }
    }
    
    function print_result($test_number, $result)
    {
        echo "Test [$test_number] : ".$this->test_table[$test_number]["description"]." : \t <b>";
        if($result === TRUE)
        {
            echo "<font color=green>PASSED</font></b><br/>\n";
        }
        else
        {
            echo "<font color=red>FAILED</font></b><br/>\n";
        }
        
        if($this->endresults_to_log === TRUE)
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
        
        for($i = 1; $i<sizeof($this->test_table)+1; ++$i)
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

    function todo()
    {

        // --------------------------------------------------------------------------------------------------------------------------------------------

        // test variables preparation
        // none
        $this->start($test_id = 1, $description = "creation of a PhoneNumber object", $operations_count = 1, "");
        $result = new PhoneNumber(0, $log, $sql_db, NULL);
        $this->stop($test_id);
        $this->print_result($test_id,
            isset($result) === TRUE && is_object($result) && get_class($result) === "PhoneNumber"
        );
        $test_id = $description = $operations_count = $result = NULL;

// --------------------------------------------------------------------------------------------------------------------------------------------

        // test variables preparation
        $test_country = "AUT";
        $this->start($test_id = 2, $description = "setting country", $operations_count = 1, "");
        // setup
        $number = new PhoneNumber(0, $log, $sql_db, NULL);
        // input + result
        $result = $number->set_normalized_country($test_country);
        $this->stop($test_id);
        $this->print_result($test_id,
            isset($result) === TRUE && is_bool($result) && $result === TRUE
        );
// reset
        $test_id = $description = $operations_count = $test_country = $number = $result = NULL;

// --------------------------------------------------------------------------------------------------------------------------------------------

        // test variables preparation
        $test_country = "AUT";
        $this->start($test_id = 3, $description = "getting country", $operations_count = 1, "");
        // setup
        $number = new PhoneNumber(0, $log, $sql_db, NULL);
        // input
        $number->set_normalized_country($test_country);
        // results
        $result = $number->get_normalized_country();
        $this->stop($test_id);
        $this->print_result($test_id,
            isset($result) === TRUE && is_string($result) && $result === $test_country
        );
// reset
        $test_id = $description = $operations_count = $test_country = $number = $result = NULL;

// --------------------------------------------------------------------------------------------------------------------------------------------

        // test variables preparation
        $test_input_number = "06944/651156";
        $this->start($test_id = 4, $description = "set input number", $operations_count = 1, "");
        // setup
        $number = new PhoneNumber(0, $log, $sql_db, NULL);
        // input + result
        $result = $number->set_input_number($test_input_number);
        $this->stop($test_id);
        $this->print_result($test_id,
            isset($result) === TRUE && is_bool($result) && $result === TRUE
        );
// reset
        $test_id = $description = $operations_count = $test_input_number = $number = $result = NULL;

// --------------------------------------------------------------------------------------------------------------------------------------------
        // test variables preparation
        $test_input_number = "06944/651156";
        $this->start($test_id = 5, $description = "get input number", $operations_count = 1, "");
        // setup
        $number = new PhoneNumber(0, $log, $sql_db, NULL);
        // input
        $number->set_input_number($test_input_number);
        // results
        $result = $number->get_input_number();
        $this->stop($test_id);
        $this->print_result($test_id,
            isset($result) === TRUE && is_string($result) && $result === $test_input_number
        );
// reset
        $test_id = $description = $operations_count = $test_input_number = $number = $result = NULL;

// --------------------------------------------------------------------------------------------------------------------------------------------

        // test variables preparation
        $test_input_number = "06944/651156";
        $test_validated_number = "06944651156";
        $this->start($test_id = 6, $description = "get validated input number", $operations_count = 1, "");
        // setup
        $number = new PhoneNumber(0, $log, $sql_db, NULL);
        // input
        $number->set_input_number($test_input_number);
        // results
        $result = $number->get_validated_input_number();
        $this->stop($test_id);
        $this->print_result($test_id,
            isset($result) === TRUE && is_string($result) && $result === $test_validated_number
        );
// reset
        $test_id = $description = $operations_count = $test_input_number = $test_validated_number = $number = $result = NULL;

// --------------------------------------------------------------------------------------------------------------------------------------------

        // test variables preparation
        $test_input_number = "06944/651156";
        $test_input_country = "AUT";
        $test_international_number = "00436944651156";
        $this->start($test_id = 7, $description = "get international number", $operations_count = 1, "");
        // setup
        $number = new PhoneNumber(0, $log, $sql_db, NULL);
        // input
        $number->set_input_number($test_input_number);
        $number->set_normalized_country($test_input_country );
        // results
        $result = $number->get_international_number();
        $this->stop($test_id);
        $this->print_result($test_id,
            isset($result) === TRUE && is_string($result) && $result === $test_international_number
        );
// reset
        $test_id = $description = $operations_count = $test_input_number = $test_input_country = $test_international_number = $number = $result = NULL;
// --------------------------------------------------------------------------------------------------------------------------------------------

        // test variables preparation
        $test_input_number = "06944/651156";
        $test_input_country = "AUT";
        $test_normalized_international_number = "+436944651156";
        $this->start($test_id = 8, $description = "get normalized international number", $operations_count = 1, "");
        // setup
        $number = new PhoneNumber(0, $log, $sql_db, NULL);
        // input
        $number->set_input_number($test_input_number);
        $number->set_normalized_country($test_input_country);
        // results
        $result = $number->get_normalized_international_number();
        $this->stop($test_id);
        $this->print_result($test_id,
            isset($result) === TRUE && is_string($result) && $result === $test_normalized_international_number
        );
// reset
        $test_id = $description = $operations_count = $test_input_number = $test_input_country = $test_normalized_international_number = $number = $result = NULL;
// --------------------------------------------------------------------------------------------------------------------------------------------
        // test variables preparation
        $test_input_number = "06944/651156";
        $test_input_country = "AUT";
        $test_normalized_number = "436944651156";

        $this->start($test_id = 9, $description = "get normalized number", $operations_count = 1, "");
        // setup
        $number = new PhoneNumber(0, $log, $sql_db, NULL);
        // input
        $number->set_input_number($test_input_number );
        $number->set_normalized_country($test_input_country);
        // results
        $result = $number->get_normalized_number();
        $this->stop($test_id);
        $this->print_result($test_id,
            isset($result) === TRUE && is_string($result) && $result === $test_normalized_number
        );
// reset
        $test_id = $description = $operations_count = $test_input_number = $test_input_country = $test_normalized_number = $number = $result = NULL;
// --------------------------------------------------------------------------------------------------------------------------------------------
        // test variables preparation
        $test_input_number = "06944/651156";
        $test_input_country = "AUT";
        $test_normalized_number = "436944651156";

        $this->start($test_id = 10, $description = "get normalized number only", $operations_count = 1, "");
        // setup
        $number = new PhoneNumber(2, $log, $sql_db, NULL);
        // input
        $number->set_input_number($test_input_number );
        $number->set_normalized_country($test_input_country);
        // results
        $result = $number->get_normalized_number_only();
        $this->stop($test_id);
        $this->print_result($test_id,
            isset($result) === TRUE && is_string($result) && $result === $test_normalized_number
        );
        if($result !== $test_normalized_number)
        {
            echo "result of normalized_number_only() = '$result'<br>";
        }
// reset
        $test_id = $description = $operations_count = $test_input_number = $test_input_country = $test_normalized_number = $number = $result = NULL;
// --------------------------------------------------------------------------------------------------------------------------------------------
        // test variables preparation
        $test_input_number = "06944/651156";
        $test_input_country = "USA";
        $test_normalized_number = "";

        $this->start($test_id = 11, $description = "get normalized number only (should not be normalizable)", $operations_count = 1, "");
        // setup
        $number = new PhoneNumber(2, $log, $sql_db, NULL);
        // input
        $number->set_input_number($test_input_number );
        $number->set_normalized_country($test_input_country);
        // results
        $result = $number->get_normalized_number_only();
        $this->stop($test_id);
        $this->print_result($test_id,
            isset($result) === TRUE && is_string($result) && $result === $test_normalized_number
        );
        if($result !== $test_normalized_number)
        {
            echo "result of normalized_number_only() = '$result'<br>";
        }
// reset
        $test_id = $description = $operations_count = $test_input_number = $test_input_country = $test_normalized_number = $number = $result = NULL;
// --------------------------------------------------------------------------------------------------------------------------------------------
        // test variables preparation
        $test_input_number = "06944/651156";
        $test_input_country = "AUT";
        $test_local_number = "06944651156";

        $this->start($test_id = 12, $description = "get local number", $operations_count = 1, "");
// setup
        $number = new PhoneNumber(0, $log, $sql_db, NULL);
        // input
        $number->set_input_number($test_input_number);
        $number->set_normalized_country($test_input_country);
        // results
        $result = $number->get_local_number();
        $this->stop($test_id);
        $this->print_result($test_id,
            isset($result) === TRUE && is_string($result) && $result === $test_local_number
        );
// reset
        $test_id = $description = $operations_count = $test_input_number = $test_input_country = $test_local_number = $number = $result = NULL;
// --------------------------------------------------------------------------------------------------------------------------------------------
        // test variables preparation
        $test_id = 13;
        $test_input_number = "06944/651156";
        $test_input_country = "AUT";
        $test_international_number = "00436944651156";
        $test_normalized_international_number = "+436944651156";
        $test_normalized_number = "436944651156";
        $test_local_number = "06944651156";
        $this->start($test_id = 13, $description = "complete test with test number '$test_input_number'  and country '$test_input_country'", 1, "");
        // setup
        $number = new PhoneNumber(0, $log, $sql_db, NULL);
        // input
        $number->set_input_number($test_input_number);
        $number->set_normalized_country($test_input_country);
        // results
        $result_country_name = $number->get_normalized_country();
        $result_input_number = $number->get_input_number();
        $result_international_number= $number->get_international_number();
        $result_normalized_international_number = $number->get_normalized_international_number();
        $result_normalized_number = $number->get_normalized_number();
        $result_local_number = $number->get_local_number();
        $this->stop($test_id);
        $this->print_result($test_id,
            isset($result_country_name) === TRUE && is_string($result_country_name) && $result_country_name === $test_input_country &&
            isset($result_input_number) === TRUE && is_string($result_input_number) && $result_input_number === $test_input_number &&
            isset($result_international_number) === TRUE && is_string($result_international_number) && $result_international_number === $test_international_number &&
            isset($result_normalized_international_number) === TRUE && is_string($result_normalized_international_number) && $result_normalized_international_number === $test_normalized_international_number &&
            isset($result_normalized_number) === TRUE && is_string($result_normalized_number) && $result_normalized_number === $test_normalized_number &&
            isset($result_local_number) === TRUE && is_string($result_local_number) && $result_local_number === $test_local_number
        );
// reset
        $test_id = $description = $operations_count = $test_input_number = $test_input_country = NULL;
        $test_international_number = $test_normalized_international_number = $test_normalized_number = $test_local_number = NULL;
        $result_country_name = $result_input_number = $result_international_number = $result_normalized_international_number = $result_normalized_number = $result_local_number = NULL;
        $result = $number = NULL;

    }


}
