<?php

namespace Redseanet\Admin\ViewModel\Customer\Grid;

use Redseanet\Admin\ViewModel\Eav\Grid as PGrid;
use Redseanet\Customer\Model\Collection\Address as Collection;
use Redseanet\Lib\Source\Store;
use Redseanet\Admin\Model\User;
use Redseanet\Lib\Session\Segment;
use Redseanet\I18n\Source\Country;

class Address extends PGrid
{
    protected $action = [
        'getEditAction' => 'Admin\\Customer\\Address::edit',
        'getDeleteAction' => 'Admin\\Customer\\Address::delete'
    ];
    protected $messAction = [];
    protected $translateDomain = 'customer';

    public function getEditAction($item)
    {
        return '<a type="button" class="btn" data-info=\'' . json_encode($item->toArray()) . '\' data-toggle="modal" data-target="#modal-edit-address" title="' . $this->translate('Edit') . '"><span class="fa fa-fw fa-file-text-o" aria-hidden="true"></span><span class="sr-only">' .
                $this->translate('Edit') . '</span></a>';
    }

    public function getDeleteAction($item)
    {
        return '<a href="' . $this->getAdminUrl(':ADMIN/customer_address/delete/') . '" data-method="delete" data-params="id=' . $item['id'] .
                '&csrf=' . $this->getCsrfKey() . '" title="' . $this->translate('Delete') .
                '"><span class="fa fa-fw fa-remove" aria-hidden="true"></span><span class="sr-only">' .
                $this->translate('Delete') . '</span></a>';
    }

    protected function prepareColumns($columns = [])
    {
        $userArray = (new Segment('admin'))->get('user');
        $user = new User();
        $user->load($userArray['id']);
        $columns = parent::prepareColumns([
            'id' => [
                'label' => 'ID'
            ],
            'customer_id' => [
                'label' => 'Customer ID',
                'use4sort' => false
            ],
            'name' => [
                'label' => 'Name',
                'use4sort' => false
            ],
            'store_id' => ($user->getStore() ? [
                'type' => 'hidden',
                'value' => $user->getStore()->getId(),
                'use4sort' => false,
                'use4filter' => false
            ] : [
                'type' => 'select',
                'options' => (new Store())->getSourceArray(),
                'label' => 'Registered Store',
                'handler' => function ($id) {
                    return $this->hasPermission('Admin\I18n\Store::edit') ? ('<a href="' . $this->getAdminUrl('i18n_store/edit/?id=') . $id . '">' . $id . '</a>') : $id;
                }
            ]),
            'country' => [
                'label' => 'Country',
                'use4sort' => false,
                'use4filter' => false,
                'options' => (new Country())->getSourceArrayId(),
                'type' => 'select',
            ],
            'region' => [
                'label' => 'Region',
                'use4sort' => false,
                'use4filter' => false
            ],
            'city' => [
                'label' => 'City',
                'use4sort' => false,
                'use4filter' => false
            ],
            'county' => [
                'label' => 'County',
                'use4sort' => false,
                'use4filter' => false
            ],
            'address' => [
                'label' => 'Address',
                'use4sort' => false
            ],
            'tel' => [
                'label' => 'Tel',
                'use4sort' => false
            ],
            'postcode' => [
                'label' => 'Postcode',
                'use4sort' => false
            ]
        ]);
        return $columns + [
            'created_at' => [
                'type' => 'daterange',
                'attrs' => [
                    'data-toggle' => 'datepicker'
                ],
                'label' => 'Created at'
            ]];
    }

    protected function prepareCollection($collection = null)
    {
        if (!$this->getQuery('desc')) {
            $this->query['desc'] = 'created_at';
        }
        return parent::prepareCollection(new Collection());
    }
}
