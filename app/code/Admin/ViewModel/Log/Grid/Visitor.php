<?php

namespace Redseanet\Admin\ViewModel\Log\Grid;

use Redseanet\Admin\ViewModel\Grid as PGrid;
use Redseanet\Log\Model\Collection\Visitor as Collection;
use Redseanet\Lib\Source\Store;
use Redseanet\Lib\Session\Segment;

class Visitor extends PGrid
{
    protected $action = [
        'getDeleteAction' => 'Admin\\Log\\Visitor::delete'
    ];
    protected $messAction = ['getMessDeleteAction' => 'Admin\\Log\\Visitor::delete'];
    protected $translateDomain = 'customer';

    public function getDeleteAction($item)
    {
        return '<a href="' . $this->getAdminUrl(':ADMIN/log_visitor/delete/') . '" data-method="delete" data-params="id=' . $item['id'] .
                '&csrf=' . $this->getCsrfKey() . '" title="' . $this->translate('Delete') .
                '"><span class="fa fa-fw fa-remove" aria-hidden="true"></span><span class="sr-only">' .
                $this->translate('Delete') . '</span></a>';
    }

    public function getMessDeleteAction()
    {
        return '<a href="' . $this->getAdminUrl(':ADMIN/log_visitor/delete/') . '" data-method="delete" data-serialize=".grid .table" title="' . $this->translate('Delete') .
                '"><span>' . $this->translate('Delete') . '</span></a>';
    }

    protected function prepareColumns($columns = [])
    {
        $columns = parent::prepareColumns([
            'id' => [
                'label' => 'ID',
                'use4filter' => false,
                'use4sort' => true
            ]
        ]);
        return $columns + [
            'customer_id' => [
                'label' => 'Customer',
                'use4filter' => true,
                'use4sort' => false
            ],
            'store_id' => [
                'type' => 'text',
                'label' => 'Store',
                'use4filter' => true,
                'use4sort' => false
            ],
            'product_id' => [
                'type' => 'text',
                'label' => 'Product',
                'use4filter' => true,
                'use4sort' => false
            ],
            'post_id' => [
                'type' => 'text',
                'label' => 'Post',
                'use4filter' => true,
                'use4sort' => false
            ],
            'session_id' => [
                'type' => 'text',
                'label' => 'Session ID',
                'use4filter' => false,
                'use4sort' => false
            ],
            'remote_addr' => [
                'type' => 'text',
                'label' => 'IP',
                'use4filter' => false,
                'use4sort' => false
            ],
            'http_referer' => [
                'type' => 'text',
                'label' => 'Referer',
                'use4filter' => false,
                'use4sort' => false
            ],
            'http_user_agent' => [
                'type' => 'text',
                'label' => 'Agent',
                'use4filter' => false,
                'use4sort' => false
            ],
            'http_accept_charset' => [
                'type' => 'text',
                'label' => 'Charset',
                'use4filter' => false,
                'use4sort' => false
            ],
            'http_accept_language' => [
                'type' => 'text',
                'label' => 'Language',
                'use4filter' => false,
                'use4sort' => false
            ],
            'created_at' => [
                'type' => 'daterange',
                'attrs' => [
                    'data-toggle' => 'datepicker'
                ],
                'label' => 'Created At'
            ]];
    }

    protected function prepareCollection($collection = null)
    {
        if (!$this->getQuery('desc')) {
            $this->query['desc'] = 'created_at';
        }
        return parent::prepareCollection(new Collection());
    }
}
