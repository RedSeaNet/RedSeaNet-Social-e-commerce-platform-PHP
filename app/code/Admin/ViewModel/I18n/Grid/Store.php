<?php

namespace Redseanet\Admin\ViewModel\I18n\Grid;

use Redseanet\Admin\ViewModel\Grid as PGrid;
use Redseanet\Lib\Model\Collection\Store as Collection;
use Redseanet\Lib\Source\Merchant;

class Store extends PGrid {

    protected $action = [
        'getEditAction' => 'Admin\\I18n\\Store::edit',
        'getDeleteAction' => 'Admin\\I18n\\Store::delete'
    ];

    public function getEditAction($item) {
        return '<a href="' . $this->getAdminUrl(':ADMIN/i18n_store/edit/?id=') . $item['id'] . '" title="' . $this->translate('Edit') .
                '"><span class="fa fa-fw fa-file-text-o" aria-hidden="true"></span><span class="sr-only">' .
                $this->translate('Edit') . '</span></a>';
    }

    public function getDeleteAction($item) {
        return '<a href="' . $this->getAdminUrl(':ADMIN/i18n_store/delete/') . '" data-method="delete" data-params="id=' . $item['id'] .
                '&csrf=' . $this->getCsrfKey() . '" title="' . $this->translate('Delete') .
                '"><span class="fa fa-fw fa-remove" aria-hidden="true"></span><span class="sr-only">' .
                $this->translate('Delete') . '</span></a>';
    }

    protected function prepareColumns() {
        return [
            'id' => [
                'label' => 'ID',
            ],
            'merchant_id' => [
                'type' => 'select',
                'label' => 'Merchant',
                'options' => (new Merchant())->getSourceArray()
            ],
            'code' => [
                'label' => 'Code',
                'type' => 'text',
            ],
            'name' => [
                'label' => 'Name',
                'type' => 'text',
            ],
            'status' => [
                'label' => 'Status',
                'type' => 'select',
                'options' => [
                    'Disabled',
                    'Enabled'
                ]
            ],
            'is_default' => [
                'type' => 'select',
                'label' => 'Is Default',
                'required' => 'required',
                'options' => ['No', 'Yes']
            ]
        ];
    }

    protected function prepareCollection($collection = null) {
        $collection = new Collection();
        return parent::prepareCollection($collection);
    }

}
