<?php

namespace Redseanet\Admin\ViewModel\I18n\Grid;

use Redseanet\Admin\ViewModel\Grid as PGrid;
use Redseanet\I18n\Model\Collection\Currency as Collection;

class Currency extends PGrid
{
    protected $action = [
        'getEditAction' => 'Admin\\I18n\\Currency::edit',
        'getSyncAction' => 'Admin\\I18n\\Currency::sync'
    ];

    public function getEditAction($item)
    {
        return '<a href="' . $this->getAdminUrl(':ADMIN/i18n_currency/edit/?id=') . $item['id'] . '" title="' . $this->translate('Edit') .
                '"><span class="fa fa-fw fa-file-text-o" aria-hidden="true"></span><span class="sr-only">' .
                $this->translate('Edit') . '</span></a>';
    }

    public function getSyncAction($item)
    {
        return '<a href="' . $this->getAdminUrl(':ADMIN/i18n_currency/sync/?code=') . $item['code'] . '" title="' . $this->translate('Synchronize') .
                '"><span class="fa fa-fw fa-refresh" aria-hidden="true"></span><span class="sr-only">' .
                $this->translate('Synchronize') . '</span></a>';
    }

    protected function prepareColumns()
    {
        return [
            'code' => [
                'label' => 'Code',
            ],
            'symbol' => [
                'label' => 'Symbol'
            ],
            'rate' => [
                'label' => 'Currency Rate'
            ]
        ];
    }

    protected function prepareCollection($collection = null)
    {
        return parent::prepareCollection(new Collection());
    }
}
