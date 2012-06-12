# Quick Start 

1. Download the source locally to a web-available directory with a PHP server running.
2. Point your browser to the ./test/ directory to see examples of how the class works.

## Example Code

```php
// set up objects
$debug_level = 0;
$debug_object = new Debug_Logging(TRUE, 'php://stderr', FALSE);
$sql_db = new SQLite_3('sqlite3://path/to/sqlite/db', $debug_level, $debug_object);
$phone_number_obj = new Phone_Number($debug_level, $debug_object, $sql_db, NULL);

// configure phone number object
$phone_number_obj->set_normalized_country('AUT');
$phone_number_obj->set_input_number('0123456789');

// get result
$result = $phone_number_obj->get_normalized_number(); // returns 43123456789
$result = $phone_number_obj->get_normalized_international_number(); // returns +43123456789
```
