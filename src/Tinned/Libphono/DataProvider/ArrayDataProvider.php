<?php

namespace Tinned\Libphono\DataProvider;

/**
 * Class ArrayDataProvider
 *
 * @package Tinned\Libphono\DataProvider
 */
class ArrayDataProvider implements DataProviderInterface
{

    // indexes: three letter, exit dialcode, international dialcode, extended dialcode, trunk dialcode
    protected $data = [
        'USA' => [
            ['USA', '011', '1', '1', '1'],
            ['USA', '011', '1', '1', null]
        ]
    ];

    protected $mapping = [
        'US' => 'USA'
    ];

    /**
     * @param string $isoCode
     * @param int $iso3166CodeType
     *
     * @return array
     *
     * @throws \Exception
     */
    public function fetchDataForISOCode($isoCode, $iso3166CodeType)
    {
        if ($iso3166CodeType == \Tinned\Libphono\PhoneNumber::INPUT_ISO_3166_ALPHA2) {
            if (!isset($this->mapping[$isoCode])) {
                throw new \Exception(1000, 'ISO Code not found');
            }
            $isoCode = $this->mapping[$isoCode];
        }
        if (!isset($this->data[$isoCode])) {
            throw new \Exception(1000, 'ISO Code not found');
        }
        return $this->data[$isoCode];
    }

    /**
     * @param string $number
     *
     * @return string
     */
    public function getCountryForNumber($number)
    {
        return '';
    }
}
