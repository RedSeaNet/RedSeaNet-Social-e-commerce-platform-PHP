<?php

namespace Redseanet\Retailer\ViewModel\Sales;

use Redseanet\Customer\Model\Address;
use Redseanet\Lib\Bootstrap;
use Redseanet\Lib\Model\Collection\Eav\Attribute;
use Redseanet\Retailer\ViewModel\AbstractViewModel;
use Redseanet\Sales\Model\Collection\Order as Collection;
use Redseanet\Sales\Model\Collection\Shipment\Track;
use Redseanet\Sales\Source\Order\Status;
use Laminas\Db\Sql\Expression;
use Laminas\Db\Sql\Select;

class Order extends AbstractViewModel
{
    use \Redseanet\Lib\Traits\Filter;

    public function getCollection()
    {
        $collection = new Collection();
        $select = $collection->where(['store_id' => $this->getRetailer()['store_id']])
                ->order('created_at DESC');
        $languageId = Bootstrap::getLanguage()->getId();
        $attribute = new Attribute();
        $attribute->columns(['id', 'type'])
                ->join('eav_entity_type', 'eav_entity_type.id=eav_attribute.type_id', [], 'left')
                ->where([
                    'eav_attribute.code' => 'name',
                    'eav_entity_type.code' => Address::ENTITY_TYPE
                ]);
        $attribute->load(true, true);
        $select->join(['recipient_attr' => Address::ENTITY_TYPE . '_value_' . $attribute[0]['type']], new Expression('sales_order.shipping_address_id=recipient_attr.entity_id AND recipient_attr.language_id=' . $languageId . ' AND recipient_attr.attribute_id=' . $attribute[0]['id']), ['recipient' => 'value'], 'left');
        $attribute = new Attribute();
        $attribute->columns(['id', 'type'])
                ->join('eav_entity_type', 'eav_entity_type.id=eav_attribute.type_id', [], 'left')
                ->where([
                    'eav_attribute.code' => 'tel',
                    'eav_entity_type.code' => Address::ENTITY_TYPE
                ]);
        $attribute->load(true, true);
        $select->join(['tel_attr' => Address::ENTITY_TYPE . '_value_' . $attribute[0]['type']], new Expression('sales_order.shipping_address_id=tel_attr.entity_id AND tel_attr.language_id=' . $languageId . ' AND tel_attr.attribute_id=' . $attribute[0]['id']), ['tel' => 'value'], 'left');
        $data = $this->getQuery();
        if (!empty($data['created_at']) && count($data['created_at']) == 2 && !empty($data['created_at'][0]) && !empty($data['created_at'][1])) {
            $select->where->greaterThanOrEqualTo('created_at', $data['created_at'][0] . ' 00:00:00')
                    ->lessThanOrEqualTo('created_at', $data['created_at'][1] . ' 23:59:59');
        }
        if (!empty($data['tracking_number'])) {
            $track = new Track();
            $track->column(['order_id'])
                    ->where(['tracking_number' => $data['tracking_number']]);
            $collection->in('sales_order.id', $track);
        }
        if (!empty($data['recipient'])) {
            $data['recipient_attr.value'] = $data['recipient'];
        }
        if (!empty($data['tel'])) {
            $data['tel_attr.value'] = $data['tel'];
        }
        unset($data['created_at'], $data['recipient'], $data['tel'], $data['tracking_number'], $data['store_id']);
        if ($this->getVariable('bulk_only', false)) {
            $select->join('bulk_sale_member', 'bulk_sale_member.order_id=sales_order.id', ['bulk_id'], 'right')
                    ->join('bulk_sale', 'bulk_sale.id=bulk_sale_member.bulk_id', ['bulk_size' => 'size', 'bulk_count' => 'count', 'bulk' => 'subject', 'bulk_status' => 'status'], 'left')
                    ->reset('order')
                    ->order('bulk_id DESC, created_at DESC');
        } else {
            $subselect = new Select('bulk_sale_member');
            $subselect->columns(['order_id']);
            $select->where->notIn('id', $subselect);
        }
        $this->filter($collection, $data, ['order' => 1]);
        return $collection;
    }

    public function getFilters()
    {
        $data = $this->getQuery();
        return [
            'page' => [
                'type' => 'hidden',
                'value' => $data['page'] ?? 1
            ],
            'increment_id' => [
                'type' => 'text',
                'label' => 'Order ID',
                'value' => $data['increment_id'] ?? ''
            ],
            'status_id' => [
                'type' => 'select',
                'label' => 'Order Status',
                'options' => (new Status())->getSourceArray(),
                'value' => $data['status_id'] ?? ''
            ],
            'recipient' => [
                'type' => 'text',
                'label' => 'Recipient',
                'value' => $data['recipient'] ?? ''
            ],
            'tel' => [
                'type' => 'tel',
                'label' => 'Telephone',
                'value' => $data['tel'] ?? ''
            ],
            'tracking_number' => [
                'type' => 'text',
                'label' => 'Track Number',
                'value' => $data['tracking_number'] ?? ''
            ],
            'created_at[]' => [
                'type' => 'daterange',
                'label' => 'Placed at',
                'value' => $data['created_at'] ?? []
            ]
        ];
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
            'parent' => $this
        ]);
        $box->setTemplate('page/renderer/' . (in_array($item['type'], ['multiselect', 'checkbox']) ? 'select' : $item['type']), false);
        return $box;
    }

    public function renderItem($order)
    {
        $item = new static();
        $item->setTemplate('retailer/sales/order/item');
        $item->setVariable('order', $order);
        return $item;
    }
}
