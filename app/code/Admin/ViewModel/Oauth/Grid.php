<?php

namespace Redseanet\Admin\ViewModel\Oauth;

use Redseanet\Admin\ViewModel\Grid as PGrid;
use Redseanet\Oauth\Model\Collection\Client as Collection;

class Grid extends PGrid
{
    protected $action = [
        'getDeleteAction' => 'Admin\\Oauth::delete'
    ];

    public function getDeleteAction($item)
    {
        return '<a href="' . $this->getAdminUrl(':ADMIN/oauth/delete/') . '" data-method="delete" data-params="customer_id=' . $item['customer_id'] . '&oauth_server=' . $item['oauth_server'] . '&open_id=' . $item['open_id'] .
                '&csrf=' . $this->getCsrfKey() . '" title="' . $this->translate('Delete') .
                '"><span class="fa fa-fw fa-remove" aria-hidden="true"></span><span class="sr-only">' .
                $this->translate('Delete') . '</span></a>';
    }

    protected function prepareColumns()
    {
        return [
            'customer_id' => [
                'label' => 'Customer ID'
            ],
            'oauth_server' => [
                'label' => 'Oauth server'
            ],
            'open_id' => [
                'label' => 'Open ID'
            ]
        ];
    }

    protected function prepareCollection($collection = null)
    {
        return parent::prepareCollection(new Collection());
    }
}
