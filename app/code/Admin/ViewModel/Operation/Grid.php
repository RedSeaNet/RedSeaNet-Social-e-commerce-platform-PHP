<?php

namespace Redseanet\Admin\ViewModel\Operation;

use Redseanet\Admin\ViewModel\Grid as PGrid;
use Redseanet\Admin\Model\Collection\Operation as Collection;

class Grid extends PGrid
{
    protected $action = [
        'getEditAction' => 'Admin\\Operation::edit',
        'getDeleteAction' => 'Admin\\Operation::delete'
    ];

    public function getEditAction($item)
    {
        return $item['is_system'] ? '' : ('<a href="' . $this->getAdminUrl(':ADMIN/operation/edit/?id=') . $item['id'] . '" title="' . $this->translate('Edit') .
                '"><span class="fa fa-fw fa-file-text-o" aria-hidden="true"></span><span class="sr-only">' .
                $this->translate('Edit') . '</span></a>');
    }

    public function getDeleteAction($item)
    {
        return $item['is_system'] ? '' : ('<a href="' . $this->getAdminUrl(':ADMIN/operation/delete/') . '" data-method="delete" data-params="id=' . $item['id'] .
                '&csrf=' . $this->getCsrfKey() . '" title="' . $this->translate('Delete') .
                '"><span class="fa fa-fw fa-remove" aria-hidden="true"></span><span class="sr-only">' .
                $this->translate('Delete') . '</span></a>');
    }

    protected function prepareColumns()
    {
        return [
            'name' => [
                'label' => 'Name'
            ],
            'description' => [
                'label' => 'Description'
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
