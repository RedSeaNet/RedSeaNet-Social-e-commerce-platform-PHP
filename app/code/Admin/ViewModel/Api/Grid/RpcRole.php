<?php

namespace Redseanet\Admin\ViewModel\Api\Grid;

use Redseanet\Admin\ViewModel\Grid as PGrid;
use Redseanet\Api\Model\Collection\Rpc\Role as Collection;

class RpcRole extends PGrid
{
    protected $action = [
        'getEditAction' => 'Admin\\Api\\Rpc\\Role::edit',
        'getDeleteAction' => 'Admin\\Api\\Rpc\\Role::delete'
    ];
    protected $translateDomain = 'api';

    public function getEditAction($item)
    {
        return '<a href="' . $this->getAdminUrl(':ADMIN/api_rpc_role/edit/?id=') . $item['id'] . '" title="' . $this->translate('Edit') .
                '"><span class="fa fa-fw fa-file-text-o" aria-hidden="true"></span><span class="sr-only">' .
                $this->translate('Edit') . '</span></a>';
    }

    public function getDeleteAction($item)
    {
        return '<a href="' . $this->getAdminUrl(':ADMIN/api_rpc_role/delete/') . '" data-method="delete" data-params="id=' . $item['id'] .
                '&csrf=' . $this->getCsrfKey() . '" title="' . $this->translate('Delete') .
                '"><span class="fa fa-fw fa-remove" aria-hidden="true"></span><span class="sr-only">' .
                $this->translate('Delete') . '</span></a>';
    }

    protected function prepareColumns()
    {
        return [
            'name' => [
                'label' => 'Name'
            ]
        ];
    }

    public function prepareCollection($collection = null)
    {
        if (!$this->getQuery('desc')) {
            $this->query['desc'] = 'created_at';
        }
        return parent::prepareCollection(new Collection());
    }
}
