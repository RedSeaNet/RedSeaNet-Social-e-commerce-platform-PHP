<?php

namespace Redseanet\Admin\ViewModel\User;

use Redseanet\Admin\ViewModel\Grid as PGrid;
use Redseanet\Admin\Model\Collection\User as Collection;
use Redseanet\Lib\Session\Segment;
use Redseanet\Admin\Source\Role;

class Grid extends PGrid
{
    protected $action = [
        'getEditAction' => 'Admin\\User::edit',
        'getDeleteAction' => 'Admin\\User::delete'
    ];

    public function getEditAction($item)
    {
        return '<a href="' . $this->getAdminUrl(':ADMIN/user/edit/?id=') . $item['id'] . '" title="' . $this->translate('Edit') .
                '"><span class="fa fa-fw fa-file-text-o" aria-hidden="true"></span><span class="sr-only">' .
                $this->translate('Edit') . '</span></a>';
    }

    public function getDeleteAction($item)
    {
        $userArray = (new Segment('admin'))->get('user');
        return $userArray['id'] == $item['id'] || $item['id'] == 1 ? false : '<a href="' . $this->getAdminUrl(':ADMIN/user/delete/') . '" data-method="delete" data-params="id=' . $item['id'] .
                '&csrf=' . $this->getCsrfKey() . '" title="' . $this->translate('Delete') .
                '"><span class="fa fa-fw fa-remove" aria-hidden="true"></span><span class="sr-only">' .
                $this->translate('Delete') . '</span></a>';
    }

    protected function prepareColumns()
    {
        return [
            'id' => [
                'label' => 'ID',
            ],
            'username' => [
                'label' => 'Username'
            ],
            'role_id' => [
                'type' => 'select',
                'label' => 'Role',
                'options' => (new Role())->getSourceArray(true),
                'required' => 'required'
            ],
            'email' => [
                'label' => 'Email',
                'class' => 'text-left',
            ],
            'status' => [
                'label' => 'Status',
                'sortby' => 'status',
                'type' => 'select',
                'options' => [
                    'Disabled',
                    'Enabled'
                ]
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
