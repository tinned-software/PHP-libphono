# Quick Start 

1. Add package to composer
```json
{
  "require": {
    "tinned-software/PHP-libphono": "*"
  }
}
```
2. run `composer install`


## Example Code

```php
require_once 'vendor/autoload.php';

$path = dirname(__FILE__) . '/resources/Country_Information.sqlite3';

$obj = new \Tinned\Libphono\PhoneNumber(
    new \Tinned\Libphono\DataProvider\ArrayDataProvider()
);

$sqlProvider = new \Tinned\Libphono\DataProvider\SQLiteDataProvider($path);
$obj = new \Tinned\Libphono\PhoneNumber(
    $sqlProvider
);

$res = $sqlProvider->fetchDataForISOCode('US', \Tinned\Libphono\PhoneNumber::INPUT_ISO_3166_ALPHA2);

var_dump($res);

$service = new \Tinned\Libphono\Service\LibphonoService(
    $sqlProvider
);

$phoneObj = $service->getPhoneNumber('06801111111', 'AUT', \Tinned\Libphono\PhoneNumber::INPUT_ISO_3166_ALPHA3);

print_r($phoneObj->get_normalized_international_number());
```
