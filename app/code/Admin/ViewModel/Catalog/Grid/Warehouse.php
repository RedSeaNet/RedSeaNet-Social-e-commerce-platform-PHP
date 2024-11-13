<?php

namespace Redseanet\Admin\ViewModel\Catalog\Grid;

use Redseanet\Admin\ViewModel\Grid as PGrid;
use Redseanet\Catalog\Model\Collection\Warehouse as Collection;
use Redseanet\Lib\Session\Segment;

class Warehouse extends PGrid
{
    protected $action = [
        'getEditAction' => 'Admin\\Catalog\\Warehouse::edit',
        'getDeleteAction' => 'Admin\\Catalog\\Warehouse::delete'
    ];
    protected $messAction = [
    ];
    protected $translateDomain = 'catalog';

    public function getEditAction($item)
    {
        return '<a href="' . $this->getAdminUrl(':ADMIN/catalog_warehouse/edit/?id=') . $item['id'] . '" title="' . $this->translate('Edit') .
                '"><span class="fa fa-fw fa-file-text-o" aria-hidden="true"></span><span class="sr-only">' .
                $this->translate('Edit') . '</span></a>';
    }

    public function getDeleteAction($item)
    {
        return '<a href="' . $this->getAdminUrl(':ADMIN/catalog_warehouse/delete/') . '" data-method="delete" data-params="id=' . $item['id'] .
                '&csrf=' . $this->getCsrfKey() . '" title="' . $this->translate('Delete') .
                '"><span class="fa fa-fw fa-remove" aria-hidden="true"></span><span class="sr-only">' .
                $this->translate('Delete') . '</span></a>';
    }

    protected function prepareColumns($columns = [])
    {
        $columns = [
            'id' => [
                'label' => 'ID',
            ],
            'name' => [
                'label' => 'Name',
                'type' => 'text'
            ],
            'country' => [
                'label' => 'Country',
                'type' => 'text',
                'use4sort' => false,
                'use4filter' => false
            ],
            'region' => [
                'label' => 'Region',
                'type' => 'text',
                'use4sort' => false,
                'use4filter' => false
            ],
            'city' => [
                'label' => 'City',
                'type' => 'text',
                'use4sort' => false,
                'use4filter' => false
            ],
            'address' => [
                'label' => 'Address',
                'type' => 'text',
                'use4sort' => false,
                'use4filter' => false
            ],
            'contact_info' => [
                'label' => 'Contact Info',
                'type' => 'text',
                'use4sort' => false,
                'use4filter' => false
            ],
            'longitude' => [
                'label' => 'Longitude',
                'type' => 'text',
                'use4sort' => false,
                'use4filter' => false
            ],
            'latitude' => [
                'label' => 'Latitude',
                'type' => 'text',
                'use4sort' => false,
                'use4filter' => false
            ],
            'open_at' => [
                'label' => 'Open At',
                'type' => 'time',
                'use4sort' => false,
                'use4filter' => false,
                'attrs' => [
                    'data-toggle' => 'timepicker'
                ]
            ],
            'close_at' => [
                'label' => 'Close At',
                'type' => 'text',
                'use4sort' => false,
                'use4filter' => false
            ],
            'status' => [
                'type' => 'select',
                'label' => 'Status',
                'options' => [
                    '0' => 'Disable',
                    '1' => 'Enable'
                ]
            ]
        ];
        $columns['updated_at'] = [
            'type' => 'daterange',
            'label' => 'Updated at',
            'attrs' => [
                'data-toggle' => 'datepicker'
            ],
            'use4sort' => false,
            'use4filter' => false
        ];
        $columns['created_at'] = [
            'type' => 'daterange',
            'label' => 'Created at',
            'attrs' => [
                'data-toggle' => 'datepicker'
            ],
            'use4sort' => false,
            'use4filter' => false
        ];
        return $columns;
    }

    protected function prepareCollection($collection = null)
    {
        if (is_null($collection)) {
            $collection = new Collection();
            if (!$this->getQuery('desc')) {
                $this->query['desc'] = 'created_at';
            }
        }
        return parent::prepareCollection($collection);
    }
}
