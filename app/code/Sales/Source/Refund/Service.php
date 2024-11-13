<?php

namespace Redseanet\Sales\Source\Refund;

use Redseanet\Lib\Source\SourceInterface;

class Service implements SourceInterface
{
    public function getSourceArray()
    {
        return [
            'Refund Only',
            'Return &amp; Refund',
            'Repair or Exchange'
        ];
    }
}
