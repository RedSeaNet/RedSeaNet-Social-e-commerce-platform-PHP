<?php

namespace Redseanet\Lib\Db\Sql\Expression;

use Laminas\Db\Sql\AbstractExpression;

class Date extends AbstractExpression
{
    use \Redseanet\Lib\Traits\Container;

    protected $field;
    protected $format;
    protected $platform;

    public function __construct($field = 'created_at', $format = 'Y-m-d H:i:s')
    {
        $this->field = $field;
        $this->format = $format;
        $this->platform = preg_replace('/\W+/', '_', $this->getContainer()->get('dbAdapter')->getPlatform()->getName());
    }

    public function getExpressionData()
    {
        $className = __CLASS__ . '\\' . $this->platform;
        return (new $className($this->field, $this->format))->getExpressionData();
    }
}
