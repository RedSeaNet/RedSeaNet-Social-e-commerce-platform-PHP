<?php

namespace Redseanet\Lib\Db\Sql\Expression\Date;

class SQLite extends AbstractDate
{
    protected $parameters = [
        'd' => '%d',
        'j' => '%d',
        'H' => '%H',
        'z' => '%j',
        'm' => '%m',
        'n' => '%m',
        'i' => '%M',
        's' => '%S',
        'W' => '%W',
        'w' => '%w',
        'Y' => '%Y'
    ];

    public function getExpressionData()
    {
        return [[
            'strftime(%s,%s)',
            [$this->getFormat(), $this->field],
            [static::TYPE_VALUE, static::TYPE_IDENTIFIER]
        ]];
    }
}
