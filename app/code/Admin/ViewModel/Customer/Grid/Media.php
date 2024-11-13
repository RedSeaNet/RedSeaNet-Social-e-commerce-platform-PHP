<?php

namespace Redseanet\Admin\ViewModel\Customer\Grid;

use Redseanet\Admin\ViewModel\Grid as PGrid;
use Redseanet\Customer\Model\Collection\Media as Collection;

class Media extends PGrid
{
    protected $action = [
        'getEditAction' => 'Admin\\Customer\\Media::edit',
        'getDeleteAction' => 'Admin\\Customer\\Media::delete'
    ];
    protected $translateDomain = 'customer';

    public function getEditAction($item)
    {
        return '<a href="' . $this->getAdminUrl(':ADMIN/customer_media/edit/?id=') . $item['id'] . '" title="' . $this->translate('Edit') .
                '"><span class="fa fa-fw fa-file-text-o" aria-hidden="true"></span><span class="sr-only">' .
                $this->translate('Edit') . '</span></a>';
    }

    public function getDeleteAction($item)
    {
        return '<a href="' . $this->getAdminUrl(':ADMIN/customer_media/delete/') . '" data-method="delete" data-params="id=' . $item['id'] .
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
            'label' => [
                'label' => 'Name',
            ],
            'link' => [
                'label' => 'Link',
            ],
            'icon' => [
                'label' => 'Icon',
                'type' => 'icon',
            ]
        ];
    }

    protected function prepareCollection($collection = null)
    {
        return parent::prepareCollection(new Collection());
    }
}
