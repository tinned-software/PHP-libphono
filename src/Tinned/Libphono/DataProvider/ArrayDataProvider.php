<?php

namespace Tinned\Libphono\DataProvider;

/**
 * Class ArrayDataProvider
 *
 * @todo this needs to be completed with the data from the database
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
                throw new \Exception('ISO Code not found', 1000);
            }
            $isoCode = $this->mapping[$isoCode];
        }
        if (!isset($this->data[$isoCode])) {
            throw new \Exception('ISO Code not found', 1000);
        }
        return array_map(
            function ($element) {
                return [
                    'country_3_letter' => $element[0],
                    'exit_dialcode' => $element[1],
                    'international_dialcode' => $element[2],
                    'extended_dialcode' => $element[3],
                    'trunk_dialcod' => $element[4],
                ];
            },
            $this->data[$isoCode]
        );
    }

    /**
     * @param string $number
     *
     * @return string
     */
    public function getCountryForNumber($number)
    {
        throw new \Exception('Not implemented');
    }
}
