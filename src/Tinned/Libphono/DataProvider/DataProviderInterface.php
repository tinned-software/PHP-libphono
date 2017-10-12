<?php

namespace Tinned\Libphono\DataProvider;

/**
 * Class DataProviderInterface
 *
 * @package Tinned\Libphono\DataProvider
 */
interface DataProviderInterface
{

    /**
     * @param string $isoCode
     *
     * @return array
     */
    public function fetchDataForISOCode($isoCode);

}
