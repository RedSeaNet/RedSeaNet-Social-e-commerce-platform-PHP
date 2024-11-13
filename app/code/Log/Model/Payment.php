<?php

namespace Redseanet\Log\Model;

use Redseanet\Lib\Model\AbstractModel;

class Payment extends AbstractModel
{
    protected $parameters = null;

    protected function construct()
    {
        $this->init('log_payment', 'id', ['id', 'order_id', 'trade_id', 'method', 'params', 'comment', 'is_request']);
    }

    public function getParameter($key = null)
    {
        if (is_null($this->parameters) && !empty($this->storage['params'])) {
            parse_str($this->storage['params'], $this->parameters);
        }
        return $key ? ($this->parameters[$key] ?? '') : $this->parameters;
    }
}
