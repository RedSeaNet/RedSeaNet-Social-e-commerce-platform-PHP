<?php

namespace Redseanet\Admin\ViewModel\Retailer\Edit;

use Redseanet\Admin\ViewModel\Edit as PEdit;
use Redseanet\Lib\Source\Store;
use Redseanet\Lib\Session\Segment;
use Redseanet\Admin\Model\User;

class Edit extends PEdit
{
    public function getSaveUrl()
    {
        return $this->getAdminUrl('retailer_index/save/');
    }

    public function getTitle()
    {
        return 'Edit Retailer';
    }

    protected function prepareElements($columns = [])
    {
        $userArray = (new Segment('admin'))->get('user');
        $user = new User();
        $user->load($userArray['id']);
        $model = $this->getVariable('model');
        $columns = [
            'csrf' => [
                'type' => 'csrf'
            ],
            'id' => [
                'type' => 'hidden',
            ],
            'store_id' => ($user->getStore() ? [
                'type' => 'hidden',
                'value' => $user->getStore()->getId()
            ] : [
                'type' => 'select',
                'options' => (new Store())->getNoneRetailerSourceArray(),
                'label' => 'Store',
                'required' => 'required'
            ]),
            'uri_key' => [
                'type' => 'text',
                'label' => 'Uri Key',
                'required' => 'required'
            ],
            'description' => [
                'type' => 'textarea',
                'label' => 'Description'
            ],
            'keywords' => [
                'type' => 'text',
                'label' => 'Keywords'
            ],
            'address' => [
                'type' => 'text',
                'label' => 'Address'
            ],
            'tel' => [
                'type' => 'tel',
                'label' => 'Telephone'
            ]
        ];
        return parent::prepareElements($columns);
    }
}
