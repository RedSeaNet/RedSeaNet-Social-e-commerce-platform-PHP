<?php

namespace Redseanet\Admin\ViewModel\I18n\Grid;

use Redseanet\Admin\ViewModel\Grid as PGrid;
use Redseanet\I18n\Model\Collection\Region as Collection;
use Redseanet\I18n\Source\Country;

class Region extends PGrid
{
    protected $action = [
        'getEditAction' => 'Admin\\I18n\\Region::edit',
        'getDeleteAction' => 'Admin\\I18n\\Region::delete'
    ];

    public function getEditAction($item)
    {
        return '<a href="' . $this->getAdminUrl(':ADMIN/i18n_region/edit/?id=') . $item['id'] . '" title="' . $this->translate('Edit') .
                '"><span class="fa fa-fw fa-file-text-o" aria-hidden="true"></span><span class="sr-only">' .
                $this->translate('Edit') . '</span></a>';
    }

    public function getDeleteAction($item)
    {
        return '<a href="' . $this->getAdminUrl(':ADMIN/i18n_region/delete/') . '" data-method="delete" data-params="id=' . $item['id'] .
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
            'country' => [
                'type' => 'select',
                'label' => 'Country',
                'options' => (new Country())->getSourceArrayId()
            ],
            'code' => [
                'label' => 'Code',
            ],
            'default_name' => [
                'label' => 'Default Name',
            ]
        ];
    }

    protected function prepareCollection($collection = null)
    {
        $collection = new Collection();
        $collection->join('i18n_country', 'i18n_region.parent_id=i18n_country.id', ['country' => 'id', 'country_default_name' => 'default_name'], 'left');
        return parent::prepareCollection($collection);
    }
}
