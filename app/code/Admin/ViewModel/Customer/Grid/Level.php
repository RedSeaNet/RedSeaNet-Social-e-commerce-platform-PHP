<?php

namespace Redseanet\Admin\ViewModel\Customer\Grid;

use Redseanet\Admin\ViewModel\Grid as PGrid;
use Redseanet\Customer\Model\Collection\Level as Collection;

class Level extends PGrid
{
    protected $action = [
        'getEditAction' => 'Admin\\Customer\\Level::edit',
        'getDeleteAction' => 'Admin\\Customer\\Level::delete'
    ];
    protected $translateDomain = 'customer';

    public function getEditAction($item)
    {
        return '<a href="' . $this->getAdminUrl(':ADMIN/customer_level/edit/?id=') . $item['id'] . '" title="' . $this->translate('Edit') .
                '"><span class="fa fa-fw fa-file-text-o" aria-hidden="true"></span><span class="sr-only">' .
                $this->translate('Edit') . '</span></a>';
    }

    public function getDeleteAction($item)
    {
        return '<a href="' . $this->getAdminUrl(':ADMIN/customer_level/delete/') . '" data-method="delete" data-params="id=' . $item['id'] .
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
            'level' => [
                'label' => 'Level'
            ],
            'name' => [
                'label' => 'Name',
                'use4filter' => false,
                'use4sort' => false
            ],
            'amount' => [
                'label' => 'Amount'
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
