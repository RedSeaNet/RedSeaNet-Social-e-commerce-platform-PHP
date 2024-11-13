<?php

namespace Redseanet\Admin\ViewModel\Api\Grid;

use Redseanet\Admin\ViewModel\Grid as PGrid;
use Redseanet\Oauth\Model\Collection\Consumer as Collection;

class Consumer extends PGrid
{
    protected $action = [
        'getEditAction' => 'Admin\\Api\\Oauth\\Consumer::edit',
        'getDeleteAction' => 'Admin\\Api\\Oauth\\Consumer::delete'
    ];
    protected $translateDomain = 'api';

    public function getEditAction($item)
    {
        return '<a href="' . $this->getAdminUrl(':ADMIN/api_oauth_consumer/edit/?id=') . $item['id'] . '" title="' . $this->translate('Edit') .
                '"><span class="fa fa-fw fa-file-text-o" aria-hidden="true"></span><span class="sr-only">' .
                $this->translate('Edit') . '</span></a>';
    }

    public function getDeleteAction($item)
    {
        return '<a href="' . $this->getAdminUrl(':ADMIN/api_oauth_consumer/delete/') . '" data-method="delete" data-params="id=' . $item['id'] .
                '&csrf=' . $this->getCsrfKey() . '" title="' . $this->translate('Delete') .
                '"><span class="fa fa-fw fa-remove" aria-hidden="true"></span><span class="sr-only">' .
                $this->translate('Delete') . '</span></a>';
    }

    protected function prepareColumns()
    {
        return [
            'name' => [
                'label' => 'Name'
            ],
            'key' => [
                'label' => 'Client Key'
            ],
            'secret' => [
                'label' => 'Client Secret'
            ],
            'callback_url' => [
                'label' => 'Callback Url',
                'type' => 'url'
            ],
            'rejected_callback_url' => [
                'label' => 'Rejected Callback Url',
                'type' => 'url'
            ]
        ];
    }

    protected function prepareCollection($collection = null)
    {
        if (!$this->getQuery('desc')) {
            $this->query['desc'] = 'created_at';
        }
        return parent::prepareCollection(new Collection());
    }
}
