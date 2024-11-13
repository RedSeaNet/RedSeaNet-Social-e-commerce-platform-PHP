<?php

namespace Redseanet\Admin\ViewModel\I18n\Grid;

use Redseanet\Admin\ViewModel\Grid as PGrid;
use Redseanet\I18n\Model\Collection\City as Collection;
use Redseanet\I18n\Source\Country;

class City extends PGrid
{
    protected $action = [
        'getEditAction' => 'Admin\\I18n\\City::edit',
        'getDeleteAction' => 'Admin\\I18n\\City::delete'
    ];

    public function getEditAction($item)
    {
        return '<a href="' . $this->getAdminUrl(':ADMIN/i18n_city/edit/?id=') . $item['id'] . '" title="' . $this->translate('Edit') .
                '"><span class="fa fa-fw fa-file-text-o" aria-hidden="true"></span><span class="sr-only">' .
                $this->translate('Edit') . '</span></a>';
    }

    public function getDeleteAction($item)
    {
        return '<a href="' . $this->getAdminUrl(':ADMIN/i18n_country/delete/') . '" data-method="delete" data-params="id=' . $item['id'] .
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
                'label' => 'Country',
                'type' => 'select',
                'options' => (new Country())->getSourceArrayId()
            ],
            'region_default_name' => [
                'label' => 'Region',
                'use4filter' => false,
                'use4sort' => false
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
        $collection->join('i18n_region', 'i18n_city.parent_id=i18n_region.id', ['country' => 'parent_id', 'region_default_name' => 'default_name'], 'left');
        $collection->join('i18n_country', 'i18n_region.parent_id=i18n_country.id', ['country_default_name' => 'default_name'], 'left');
        return parent::prepareCollection($collection);
    }
}
