<?php

namespace Tinned\Libphono\Service;

use Tinned\Libphono\DataProvider\DataProviderInterface;
use Tinned\Libphono\PhoneNumber;

/**
 * Class LibphonoService
 *
 * @package Tinned\Libphono\Service
 */
class LibphonoService
{

    /**
     * @var \Tinned\Libphono\DataProvider\DataProviderInterface
     */
    protected $dataProvider;

    public function __construct(DataProviderInterface $dataProvider)
    {
        $this->dataProvider = $dataProvider;
    }

    /**
     * @param string $number
     * @return \Tinned\Libphono\PhoneNumber
     */
    public function getPhoneNumber($number, $isoCode, $iso3166CodeType)
    {
        $ret = new PhoneNumber($this->dataProvider);
        $ret->setInputNumber($number);
        $ret->setNormalizedCountry($isoCode, $iso3166CodeType);
        return $ret;
    }

    /**
     * @param $number
     * @param $error
     * @param $error_list
     *
     * @return bool|null|string
     */
    public function getNumberCountry($number)
    {
        if (isset($number) === false) {
            throw new \LogicException('missing parameters, cannot continue to process number');
        }
        return $this->dataProvider->getCountryForNumber($number);
    }
}
