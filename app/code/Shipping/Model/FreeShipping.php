<?php

namespace Redseanet\Shipping\Model;

class FreeShipping extends AbstractMethod
{
    public const METHOD_CODE = 'free_shipping';

    public function getShippingRate($storeId)
    {
        return 0;
    }
}
