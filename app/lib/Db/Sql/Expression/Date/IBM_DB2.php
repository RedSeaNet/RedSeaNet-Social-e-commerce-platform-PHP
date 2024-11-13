<?php

namespace Redseanet\Lib\Db\Sql\Expression\Date;

class IBM_DB2 extends AbstractDate
{
    public function getExpressionData()
    {
        return [[
            'TO_CHAR(%s,%s)',
            [$this->field, $this->getFormat()],
            [static::TYPE_IDENTIFIER, static::TYPE_VALUE]
        ]];
    }
}
