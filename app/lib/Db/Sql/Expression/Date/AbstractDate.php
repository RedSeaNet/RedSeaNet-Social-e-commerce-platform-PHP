<?php

namespace Redseanet\Lib\Db\Sql\Expression\Date;

use Laminas\Db\Sql\ExpressionInterface;

abstract class AbstractDate implements ExpressionInterface
{
    protected $field;
    protected $format;
    protected $parameters = [
        'h' => 'HH12',
        'H' => 'HH24',
        'i' => 'MI',
        's' => 'SS',
        'u' => 'MS',
        'A' => 'AM',
        'a' => 'am',
        'Y' => 'YYYY',
        'y' => 'YY',
        'F' => 'Month',
        'M' => 'Mon',
        'm' => 'MM',
        'n' => 'MM',
        'd' => 'DD',
        'D' => 'D',
        'j' => 'DD',
        'W' => 'WW',
        'z' => 'DDD'
    ];

    public function __construct($field = 'created_at', $format = 'Y-m-d H:i:s')
    {
        $this->field = $field;
        $this->format = $format;
    }

    protected function getFormat()
    {
        return str_replace(array_keys($this->parameters), array_values($this->parameters), $this->format);
    }
}
