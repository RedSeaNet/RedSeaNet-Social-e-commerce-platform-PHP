<?php

namespace Redseanet\Balance\Source;

use Redseanet\Lib\Source\SourceInterface;

class DrawType implements SourceInterface
{
    public function getSourceArray()
    {
        return [
            'Alipay',
            'Unionpay'
        ];
    }
}
