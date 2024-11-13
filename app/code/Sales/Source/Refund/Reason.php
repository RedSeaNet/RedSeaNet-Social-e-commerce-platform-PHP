<?php

namespace Redseanet\Sales\Source\Refund;

use Redseanet\Lib\Source\SourceInterface;

class Reason implements SourceInterface
{
    public function getSourceArray()
    {
        return [
            'Non-delivery',
            'I don\'t want it',
            'Quality issues',
            'Short delivered',
            'Not coincide to the description',
            'Invoice issues'
        ];
    }
}
