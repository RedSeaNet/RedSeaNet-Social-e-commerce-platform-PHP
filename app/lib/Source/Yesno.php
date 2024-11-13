<?php

namespace Redseanet\Lib\Source;

class Yesno implements SourceInterface
{
    public function getSourceArray()
    {
        return [
            '1' => 'Yes',
            '0' => 'No'
        ];
    }
}
