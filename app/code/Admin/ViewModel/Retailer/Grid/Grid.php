<?php

namespace Redseanet\Admin\ViewModel\Retailer\Grid;

use Redseanet\Admin\ViewModel\Grid as PGrid;
use Redseanet\Retailer\Model\Collection\Retailer as Collection;
use Redseanet\Lib\Model\Store;

class Grid extends PGrid
{
    protected $translateDomain = 'retailer';
    protected $action = [
        'getEditAction' => 'Admin\\Retailer\\Index::edit',
        'getManagerAction' => 'Admin\\Retailer\\Manager::index'
    ];
    protected $messAction = [
    ];

    public function getManagerAction($item)
    {
        return '<a href="' . $this->getAdminUrl(':ADMIN/retailer_manager/?retailer_id=' . $item['id']) . '"title="' . $this->translate('Manager') .
                '"><span class="fa fa-fw fa-user-o" aria-hidden="true"></span><span class="sr-only">'
                . $this->translate('Manager') . '</span></a>';
    }

    public function getEditAction($item)
    {
        return '<a href="' . $this->getAdminUrl(':ADMIN/retailer_index/edit/?id=' . $item['id']) . '"title="' . $this->translate('Edit') .
                '"><span class="fa fa-fw fa-file-text-o" aria-hidden="true"></span><span class="sr-only">'
                . $this->translate('Edit') . '</span></a>';
    }

    protected function prepareColumns()
    {
        return [
            'id' => [
                'label' => 'ID',
            ],
            'store_id' => [
                'label' => 'Store',
                'type' => 'text',
                'handler' => function ($id, &$item) {
                    $storeObject = new Store();
                    $storeObject->load($id);
                    $item['store_name'] = $storeObject['name'];
                    $item['store_code'] = $storeObject['code'];
                    return $this->hasPermission('Admin\I18n\Store::edit') ? '<a href="' . $this->getAdminUrl('i18n_store/edit/?id=') . $id . '">' . $id . '</a>' : $id;
                }
            ],
            'store_name' => [
                'label' => 'Store name',
                'type' => 'text',
                'use4sort' => false,
                'use4filter' => false,
            ],
            'store_code' => [
                'label' => 'Store code',
                'type' => 'text',
                'use4sort' => false,
                'use4filter' => false,
            ],
            'description' => [
                'label' => 'Description',
                'type' => 'text',
                'use4sort' => false,
                'use4filter' => false,
            ],
            'address' => [
                'label' => 'Address',
                'type' => 'text',
                'use4sort' => false,
                'use4filter' => false,
            ],
            'tel' => [
                'label' => 'Telephone',
                'type' => 'text',
                'use4sort' => false
            ],
            'uri_key' => [
                'label' => 'Uri Key',
                'type' => 'text',
                'use4sort' => false
            ]
        ];
    }

    protected function prepareCollection($collection = null)
    {
        $collection = new Collection();
        $collection->order('created_at DESC');
        return parent::prepareCollection($collection);
    }
}
