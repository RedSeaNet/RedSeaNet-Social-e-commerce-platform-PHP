<?php

namespace Redseanet\Lib\Db\Sql\Ddl\Column;

use Laminas\Db\Sql\Ddl\Column\Integer;

class UnsignedInteger extends Integer
{
    protected $type = 'INTEGER UNSIGNED';
}
