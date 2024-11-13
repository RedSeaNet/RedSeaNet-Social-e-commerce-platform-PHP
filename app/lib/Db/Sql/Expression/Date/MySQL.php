<?php

namespace Redseanet\Lib\Db\Sql\Expression\Date;

class MySql extends AbstractDate
{
    protected $parameters = [
        'D' => '%a',
        'M' => '%b',
        'm' => '%c',
        'd' => '%d',
        'j' => '%e',
        'u' => '%f',
        'H' => '%H',
        'h' => '%h',
        'i' => '%i',
        'z' => '%j',
        'G' => '%k',
        'g' => '%l',
        'F' => '%M',
        'm' => '%m',
        'a' => '%p',
        'A' => '%p',
        's' => '%s',
        'W' => '%u',
        'l' => '%W',
        'w' => '%w',
        'Y' => '%Y',
        'y' => '%y'
    ];

    public function getExpressionData()
    {
        return [[
            'DATE_FORMAT(%s,%s)',
            [$this->field, $this->getFormat()],
            [static::TYPE_IDENTIFIER, static::TYPE_VALUE]
        ]];
    }
}
