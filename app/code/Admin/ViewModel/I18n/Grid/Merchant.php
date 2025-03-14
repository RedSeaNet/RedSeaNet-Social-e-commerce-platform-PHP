<?php

namespace Redseanet\Admin\ViewModel\I18n\Grid;

use Redseanet\Admin\ViewModel\Grid as PGrid;
use Redseanet\Lib\Model\Collection\Merchant as Collection;

class Merchant extends PGrid {

    protected $action = [
        'getEditAction' => 'Admin\\I18n\\Merchant::edit',
        'getDeleteAction' => 'Admin\\I18n\\Merchant::delete'
    ];

    public function getEditAction($item) {
        return '<a href="' . $this->getAdminUrl(':ADMIN/i18n_merchant/edit/?id=') . $item['id'] . '" title="' . $this->translate('Edit') .
                '"><span class="fa fa-fw fa-file-text-o" aria-hidden="true"></span><span class="sr-only">' .
                $this->translate('Edit') . '</span></a>';
    }

    public function getDeleteAction($item) {
        return '<a href="' . $this->getAdminUrl(':ADMIN/i18n_merchant/delete/') . '" data-method="delete" data-params="id=' . $item['id'] .
                '&csrf=' . $this->getCsrfKey() . '" title="' . $this->translate('Delete') .
                '"><span class="fa fa-fw fa-remove" aria-hidden="true"></span><span class="sr-only">' .
                $this->translate('Delete') . '</span></a>';
    }

    protected function prepareColumns() {
        return [
            'id' => [
                'label' => 'ID',
                'type' => 'number'
            ],
            'code' => [
                'type' => 'text',
                'label' => 'Name'
            ],
            'is_default' => [
                'type' => 'select',
                'label' => 'Is Default',
                'required' => 'required',
                'options' => ['No', 'Yes']
            ],
            'status' => [
                'type' => 'select',
                'label' => 'Status',
                'options' => [
                    1 => 'Enabled',
                    0 => 'Disabled'
                ]
            ]
        ];
    }

    protected function prepareCollection($collection = null) {
        $collection = new Collection();
        return parent::prepareCollection($collection);
    }

}
