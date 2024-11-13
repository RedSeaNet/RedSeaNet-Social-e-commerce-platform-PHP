<?php

namespace Redseanet\Customer\ViewModel;

use Redseanet\Customer\Model\Customer;
use Redseanet\Email\Model\Subscriber;
use Redseanet\Lib\Bootstrap;
use Redseanet\Lib\Model\Collection\Eav\Attribute;

class Edit extends Account
{
    public function getAttribute()
    {
        $collection = new Attribute();
        $collection->withLabel()
                ->join('eav_entity_type', 'eav_entity_type.id=eav_attribute.type_id', [], 'left')
                ->where(['eav_entity_type.code' => Customer::ENTITY_TYPE])
        ->where->notIn('eav_attribute.code', ['username', 'password', 'avatar']);
        $result = [];
        foreach ($collection as $item) {
            if (in_array($item->offsetGet('input'), ['select', 'radio', 'checkbox', 'multiselect'])) {
                $item->offsetSet('options', $item->getOptions(Bootstrap::getLanguage()->getId()));
            }
            $result[] = $item;
        }
        return $result;
    }

    public function getInputBox($attr)
    {
        $item = $attr->toArray();
        $item['value'] = $this->getCustomer()->offsetGet($item['code']);
        $class = empty($item['view_model']) ? '\\Redseanet\\Lib\\ViewModel\\Template' : $item['view_model'];
        $box = new $class();
        $box->setVariables([
            'key' => $item['code'],
            'item' => $item,
            'parent' => $this
        ]);
        $box->setTemplate('page/renderer/' . $item['input'], false);
        return $box;
    }

    public function hasSubscribed()
    {
        $subscriber = new Subscriber();
        $subscriber->load($this->getCustomer()->offsetGet('email'), 'email');
        return (bool) $subscriber->getId() && $subscriber->offsetGet('status');
    }
}
