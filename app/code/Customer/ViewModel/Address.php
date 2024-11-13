<?php

namespace Redseanet\Customer\ViewModel;

use Redseanet\Customer\Model\Address as Model;
use Redseanet\Customer\Model\Collection\Address as Collection;
use Redseanet\Lib\Model\Collection\Eav\Attribute;
use Redseanet\Lib\ViewModel\Template;
use Redseanet\Lib\Session\Segment;
use Redseanet\Sales\Model\Cart;

class Address extends Template
{
    protected $hasLoggedIn = null;

    public function hasLoggedIn()
    {
        if (is_null($this->hasLoggedIn)) {
            $segment = new Segment('customer');
            $this->hasLoggedIn = $segment->get('hasLoggedIn');
        }
        return $this->hasLoggedIn;
    }

    public function getAddressAttribute()
    {
        $collection = new Attribute();
        $collection->withLabel()
                ->join('eav_entity_type', 'eav_entity_type.id=eav_attribute.type_id', [], 'left')
                ->where(['eav_entity_type.code' => Collection::ENTITY_TYPE])
                ->order('id');
        return $collection;
    }

    public function getAddress()
    {
        if ($this->hasLoggedIn()) {
            $address = new Collection();
            $segment = new Segment('customer');
            $address->where(['customer_id' => $segment->get('customer')['id']])
                    ->order('is_default DESC');
            if ($address->count()) {
                return $address;
            }
        } elseif (Cart::instance()['shipping_address_id']) {
            $address = new Model();
            $address->load(Cart::instance()['shipping_address_id']);
            return [$address];
        }
        return [];
    }

    public function getCurrenctAddress()
    {
        return Cart::instance()['shipping_address_id'];
    }

    public function getInputBox($key, $item)
    {
        if (empty($item['type'])) {
            return '';
        }
        $class = empty($item['view_model']) ? '\\Redseanet\\Lib\\ViewModel\\Template' : $item['view_model'];
        $box = new $class();
        $box->setVariables([
            'key' => $key,
            'item' => $item,
            'parent' => $this,
            'boxClass' => $key
        ]);
        $box->setTemplate('page/renderer/' . $item['type'], false);
        return $box;
    }
}
