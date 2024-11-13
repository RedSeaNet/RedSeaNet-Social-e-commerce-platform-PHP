<?php

namespace Redseanet\Admin\Controller\Customer;

use Redseanet\Customer\Model\Address;
use Redseanet\Lib\Bootstrap;
use Redseanet\Lib\Controller\AuthActionController;
use Redseanet\Lib\Model\Collection\Eav\Attribute;
use Redseanet\Lib\Model\Collection\Eav\Attribute\Set;
use Redseanet\Admin\ViewModel\Customer\Edit\Address as adressEditViewmodel;

class AddressController extends AuthActionController
{
    public function indexAction()
    {
        $root = $this->getLayout('admin_cutomer_address_list');
        $root->getChild('extra')->addChild('address-form', (new adressEditViewmodel())->setTemplate('admin/customer/addressForm'));
        return $root;
    }

    public function deleteAction()
    {
        return $this->doDelete('\\Redseanet\\Customer\\Model\\Address');
    }

    public function saveAction()
    {
        $collection = new Attribute();
        $collection->join('eav_entity_type', 'eav_entity_type.id=eav_attribute.type_id', [], 'left')
                ->where([
                    'is_required' => 1,
                    'eav_entity_type.code' => Address::ENTITY_TYPE
                ]);
        $required = [];
        foreach ($collection as $item) {
            $required[] = $item['code'];
        }
        $response = $this->doSave('\\Redseanet\\Customer\\Model\\Address', null, $required, function ($model, $data) {
            $set = new Set();
            $set->columns(['id', 'type_id'])
                    ->join('eav_entity_type', 'eav_entity_type.id=eav_attribute_set.type_id', [], 'left')
                    ->where(['eav_entity_type.code' => Address::ENTITY_TYPE]);
            $model->setData([
                'type_id' => $set->toArray()[0]['type_id'],
                'attribute_set_id' => $set->toArray()[0]['id'],
                'store_id' => Bootstrap::getStore()->getId()
            ]);
        });
        if (isset($response['data'])) {
            $response['address'] = nl2br((new Address(Bootstrap::getLanguage()->getId(), $response['data']))->display(false));
        }
        return $response;
    }
}
