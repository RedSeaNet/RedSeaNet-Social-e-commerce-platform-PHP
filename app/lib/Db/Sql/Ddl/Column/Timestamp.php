<?php

namespace Redseanet\Lib\Db\Sql\Ddl\Column;

use Laminas\Db\Sql\Ddl\Column\AbstractTimestampColumn;

class Timestamp extends AbstractTimestampColumn
{
    /**
     * @var string
     */
    protected $type = 'TIMESTAMP';

    public function getExpressionData()
    {
        $spec = $this->specification;

        $params = [];
        $params[] = $this->name;
        $params[] = $this->type;

        $types = [self::TYPE_IDENTIFIER, self::TYPE_LITERAL];

        if (!$this->isNullable) {
            $spec .= ' NOT NULL';
        }

        if ($this->default !== null) {
            $spec .= ' DEFAULT %s';
            $params[] = $this->default;
            $types[] = $this->default === 'CURRENT_TIMESTAMP' ? self::TYPE_LITERAL : self::TYPE_VALUE;
        }

        $options = $this->getOptions();

        if (isset($options['on_update'])) {
            $spec .= ' %s';
            $params[] = 'ON UPDATE CURRENT_TIMESTAMP';
            $types[] = self::TYPE_LITERAL;
        }

        $data = [[
            $spec,
            $params,
            $types,
        ]];

        foreach ($this->constraints as $constraint) {
            $data[] = ' ';
            $data = array_merge($data, $constraint->getExpressionData());
        }

        return $data;
    }
}
