<?php

namespace Redseanet\Admin\ViewModel\Message;

use Redseanet\Admin\ViewModel\Grid as PGrid;
use Redseanet\Message\Model\Collection\Template as Collection;

class Grid extends PGrid
{
    protected $action = [
        'getEditAction' => 'Admin\\Message\\Template::edit',
        'getDeleteAction' => 'Admin\\Message\\Template::delete'
    ];

    public function getEditAction($item)
    {
        return '<a href="' . $this->getAdminUrl(':ADMIN/message_template/edit/?id=') . $item['id'] . '" title="' . $this->translate('Edit') .
                '"><span class="fa fa-fw fa-file-text-o" aria-hidden="true"></span><span class="sr-only">' .
                $this->translate('Edit') . '</span></a>';
    }

    public function getDeleteAction($item)
    {
        return '<a href="' . $this->getAdminUrl(':ADMIN/message_template/delete/') . '" data-method="delete" data-params="id=' . $item['id'] .
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
            'code' => [
                'label' => 'Code',
            ],
            'language' => [
                'label' => 'Language',
                'use4sort' => false,
                'use4filter' => false
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
