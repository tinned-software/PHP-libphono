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

// ===============================================================
// STEP 0: process POST AJAX input
// ===============================================================

//
// for custom test
//
if(isset($_POST['nr_custom']))
{
    $GLOBALS['DBG']->debug2('got POST '.print_r($_POST, true));
    $GLOBALS['DBG']->debug2('got POST number/iso:'.$_POST['nr_custom'].'/'.$_POST['nr_custom_iso']);
    $iso_type_list = array(
        'alpha2' => Phone_Number::INPUT_ISO_3166_ALPHA2,
        'alpha3' => Phone_Number::INPUT_ISO_3166_ALPHA3,
        );
    
    // generate internal variables from post input
    $nr_int = preg_replace('/[^\x20-\x7e]/', '', $_POST['nr_custom']);
    $iso_int = preg_replace('/[^a-zA-Z]/', '', $_POST['nr_custom_iso']);
    $iso_int = strtoupper($iso_int);

    // do iso type calculations, default to three letter if not available / valid
    $iso_type_int = $_POST['nr_custom_iso_type'];
    if(array_key_exists($iso_type_int, $iso_type_list) === TRUE)
    {
        $iso_type_int = $iso_type_list[$iso_type_int];
    }
    else
    {
        $iso_type_int = $iso_type_list['alpha3'];
    }
    
    $GLOBALS['DBG']->debug2('got cleaned number/iso:'.$nr_int.'/'.$iso_int);

    // set and fetch values
    $phone_number_obj = new Phone_Number(2, $GLOBALS['DBG'], $sql_db, NULL);
    $phone_number_obj->set_normalized_country($iso_int, $iso_type_int);
    $phone_number_obj->set_input_number($nr_int);
    
    $result = $phone_number_obj->get_normalized_number();
    $result .= "\n---debug-bounds---\n".print_r($phone_number_obj->explain(TRUE, TRUE), TRUE);
    $GLOBALS['DBG']->debug("printed '$result' to the script, and exiting..");
    echo $result;
    
    exit;
}


// ===============================================================
// STEP 1: begin javascript / interfactive output
// ===============================================================

?>
<html>
<head>
<script type='text/javascript'>

var request = false;
var number = '';
var iso = '';

function normalizeNumber()
{
    number = document.getElementById('nr_custom').value;
    iso = document.getElementById('nr_custom_iso').value;
    iso_type = document.getElementById('nr_custom_iso_type').value;
    request = new XMLHttpRequest();
    if(!request) {
        alert("Cannot create XMLHTTP-Instance");
        return false;
    } else {
        request.open('post', '<?php echo $_SERVER['PHP_SELF'] ?>', true);
        request.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
        request.send('nr_custom='+encodeURIComponent(number)+'&nr_custom_iso='+encodeURIComponent(iso)+'&nr_custom_iso_type='+encodeURIComponent(iso_type));
        // evaluate request
        request.onreadystatechange = interpretRequest;
    }
}


// evaluate request
function interpretRequest() {
    switch (request.readyState) {
        // if readyState 4 and the request.status is 200 ist, everything is ok
        case 4:
            if(request.status != 200) {
                alert("The request finished but it's not OK\nError:"+request.status);
            } else {
                var outputTextfield = document.getElementById('custom_output');
                var outputDebugTextfield = document.getElementById('custom_debug_output');
                var oldContent = outputTextfield.innerHTML;
                var responseContent = request.responseText;
                var responseContentParsed = responseContent.split("\n---debug-bounds---\n");
                var outputContent =  'International Number: +'+responseContentParsed[0]+" (Input: "+number+", normalized using: "+iso.toUpperCase()+")\n"+oldContent
                var outputDebugContent = responseContentParsed[1];
                // output content in the <div>
                outputTextfield.innerHTML = outputContent;
                outputDebugTextfield.innerHTML = outputDebugContent;
            }
            break;
        default:
            break;
    }
}

function toggleElement(elementId)
{
    var elementToToggle = document.getElementById(elementId)
    elementToToggle.style.display=(elementToToggle.style.display == 'none' ? 'block' : 'none');
}

function resetOutput() {
    var outputTextfield = document.getElementById('custom_output');
    outputTextfield.innerHTML = '';
    var outputDebugTextfield = document.getElementById('custom_debug_output');
    outputDebugTextfield.innerHTML = '';
}
</script>
</head>
<?php

echo "<p style='font-weight:700'>This script test the behavior of the normalizaion logic in the phone number class<p/>\n";
echo "<b>Test '".basename(__FILE__)."' ... Start </b><br/>\n";
echo "______________________<br>\n";

// ===============================================================
// STEP 2: and produce HTML output
// ===============================================================

echo "</div>\n";
echo "<a href=\"javascript:toggleElement('custom_test')\" >toggle interactive test</a><br/>\n";
echo "<div id='custom_test' style='display:block;'>\n";
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
echo "  </td></tr>";
echo "  <tr><td>A summary of the output</td>";
echo "  <td>An internal class representation of the last test</td></tr>";
echo "</table>";
echo "  </p>";
echo "</form>";
echo "</div>\n";
echo "______________________<br>\n";


echo "<b>Test '".basename(__FILE__)."' ... End.</b><br/><br/>\n";
?>
