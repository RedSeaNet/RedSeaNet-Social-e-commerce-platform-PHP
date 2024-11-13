<?php

namespace Redseanet\Payment\Source;

use Redseanet\Lib\Source\SourceInterface;

class CcType implements SourceInterface
{
    public function getSourceArray()
    {
        return [
            'AE' => 'American Express',
            'VI' => 'Visa',
            'MC' => 'MasterCard',
            'DI' => 'Discover',
            'DICL' => 'Diners Club',
            'JCB' => 'JCB',
            'SM' => 'Switch/Maestro',
            'SO' => 'Solo'
        ];
    }
}
