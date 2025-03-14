<?php

namespace Redseanet\Admin\ViewModel\Log;

use Redseanet\Admin\ViewModel\Grid;
use Redseanet\Log\Model\Collection\Customer as Log;

class Customer extends Grid
{
    use \Redseanet\Lib\Traits\Filter;

    protected function prepareColumns()
    {
        $columns = [
            'remote_addr' => [
                'label' => 'IP',
                'handler' => function ($value) {
                    return $value;
                }
            ],
            'http_user_agent' => [
                'label' => 'User Agent'
            ],
            'created_at' => [
                'label' => 'Logged Date'
            ]
        ];
        return $columns;
    }

    protected function prepareCollection($collection = null)
    {
        $log = new Log();
        $log->where(['customer_id' => $this->getVariable('model')->getId()])->order('id DESC');
        $query = $this->getQuery();
        $this->filter($log, array_intersect_key($query, ['asc' => 1, 'desc' => 1, 'limit' => 1, 'page' => 1]));
        return $log;
    }
}
