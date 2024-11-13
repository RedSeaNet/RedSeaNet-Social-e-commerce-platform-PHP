<?php

namespace Redseanet\Admin\ViewModel\Retailer\Grid;

use Redseanet\Admin\ViewModel\Grid as PGrid;
use Redseanet\Retailer\Model\Collection\Manager as Collection;
use Redseanet\Customer\Model\Customer;
use Redseanet\Lib\Session\Segment;
use Redseanet\Lib\Source\Store;
use Redseanet\Admin\Model\User;
use Redseanet\Lib\Model\Store as storeModel;

class Manager extends PGrid
{
    protected $translateDomain = 'retailer';
    protected $action = [
        'getDeleteAction' => 'Admin\\Retailer\\Manager::delete'
    ];
    protected $messAction = [
    ];

    public function getDeleteAction($item)
    {
        return '<a href="' . $this->getAdminUrl(':ADMIN/retailer_manager/delete/') . '" data-method="delete" data-params="id=' . $item['id'] .
                '&csrf=' . $this->getCsrfKey() . '" title="' . $this->translate('Delete') .
                '"><span class="fa fa-fw fa-remove" aria-hidden="true"></span><span class="sr-only">' .
                $this->translate('Delete') . '</span></a>';
    }

    public function getEditAction($item)
    {
        return '<a href="' . $this->getAdminUrl(':ADMIN/retailer_manager/edit/?id=' . $item['id']) . '"title="' . $this->translate('Edit') .
                '"><span class="fa fa-fw fa-file-text-o" aria-hidden="true"></span><span class="sr-only">'
                . $this->translate('Edit') . '</span></a>';
    }

    protected function prepareColumns()
    {
        $userArray = (new Segment('admin'))->get('user');
        $user = new User();
        $user->load($userArray['id']);
        return [
            'id' => [
                'label' => 'ID',
            ],
            'store_id' => ($user->getStore() ? [
                'type' => 'hidden',
                'value' => $user->getStore()->getId(),
                'use4sort' => false,
                'use4filter' => false,
                'handler' => function ($id, &$item) {
                    $storeObject = new storeModel();
                    $storeObject->load($id);
                    $item['store_name'] = $storeObject['name'];
                    $item['store_code'] = $storeObject['code'];
                    return $this->hasPermission('Admin\I18n\Store::edit') ? '<a href="' . $this->getAdminUrl('i18n_store/edit/?id=') . $id . '">' . $id . '</a>' : $id;
                }
            ] : [
                'type' => 'select',
                'options' => (new Store())->getSourceArray(),
                'label' => 'Store',
                'handler' => function ($id, &$item) {
                    $storeObject = new storeModel();
                    $storeObject->load($id);
                    $item['store_name'] = $storeObject['name'];
                    $item['store_code'] = $storeObject['code'];
                    return $this->hasPermission('Admin\I18n\Store::edit') ? '<a href="' . $this->getAdminUrl('i18n_store/edit/?id=') . $id . '">' . $id . '</a>' : $id;
                }
            ]),
            'store_name' => [
                'label' => 'Store name',
                'type' => 'text',
                'use4sort' => false,
                'use4filter' => false,
            ],
            'customer_id' => [
                'label' => 'Administrator',
                'type' => 'text',
                'use4sort' => false,
                'use4filter' => false,
                'handler' => function ($id) {
                    $model = new Customer();
                    $model->load($id);
                    return $this->hasPermission('Admin\Customer\Manage::edit') ? '<a href="' . $this->getAdminUrl('customer_manage/edit/?id=') . $id . '">' . $model->offsetGet('username') . '</a>' : $model->offsetGet('username');
                }
            ],
            'retailer_id' => [
                'label' => 'Retailer',
                'type' => 'text',
                'handler' => function ($id, &$item) {
                    return '<a href="' . $this->getAdminUrl('retailer_index/edit/?id=' . $id) . '">' . $id . '</a>';
                }
            ],
            'uri_key' => [
                'label' => 'Uri Key',
                'type' => 'text',
                'use4sort' => false,
                'use4filter' => false
            ],
            'store_id' => [
                'label' => 'Store',
                'type' => 'text',
                'use4sort' => false,
                'use4filter' => false,
                'handler' => function ($id) {
                    return $this->hasPermission('Admin\I18n\Store::edit') ? '<a href="' . $this->getAdminUrl('i18n_store/edit/?id=') . $id . '">' . $id . '</a>' : $id;
                }
            ]
        ];
    }

    protected function prepareCollection($collection = null)
    {
        $collection = new Collection();
        $collection->join('retailer', 'retailer_id=retailer.id', ['uri_key', 'store_id'], 'left');
        $collection->join('core_store', 'core_store.id=retailer.store_id', ['store_name' => 'name'], 'left');
        $collection->order('created_at DESC');
        return parent::prepareCollection($collection);
    }
}
