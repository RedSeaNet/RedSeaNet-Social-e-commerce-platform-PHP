<?php

namespace Redseanet\Catalog\Model\Product;

use Redseanet\Catalog\Model\Product as Model;
use Redseanet\Catalog\Model\Collection\Product\Rating as Collection;
use Redseanet\Customer\Model\Customer;
use Redseanet\Lib\Exception\SpamException;
use Redseanet\Lib\Model\AbstractModel;

class Review extends AbstractModel
{
    protected function construct()
    {
        $this->init('review', 'id', ['id', 'product_id', 'customer_id', 'order_id', 'language_id', 'subject', 'content', 'reply', 'images', 'anonymous', 'status']);
    }

    protected function beforeSave()
    {
        $this->beginTransaction();
        if (!empty($this->storage['content'])) {
            $this->storage['content'] = gzencode($this->storage['content']);
        }
        if (!empty($this->storage['reply'])) {
            $this->storage['reply'] = gzencode($this->storage['reply']);
        }
        parent::beforeSave();
    }

    protected function afterSave()
    {
        if (!empty($this->storage['rating'])) {
            $tableGateway = $this->getTableGateway('review_rating');
            foreach ((array) $this->storage['rating'] as $id => $value) {
                $this->upsert(['value' => $value], ['review_id' => $this->getId(), 'rating_id' => $id], $tableGateway);
            }
            $this->flushList('rating');
        }
        parent::afterSave();
        $this->commit();
    }

    protected function afterLoad(&$result)
    {
        if (isset($result[0])) {
            $data = @gzdecode($result[0]['content']);
            if ($data !== false) {
                $result[0]['content'] = $data;
            }
            $data = @gzdecode($result[0]['reply']);
            if ($data !== false) {
                $result[0]['reply'] = $data;
            }
        }
        parent::afterLoad($result);
    }

    public function getProduct()
    {
        if (!empty($this->storage['product_id'])) {
            $product = new Model();
            $product->load($this->storage['product_id']);
            return $product;
        }
        return null;
    }

    public function getCustomer()
    {
        if (!empty($this->storage['customer_id'])) {
            $customer = new Customer();
            $customer->load($this->storage['customer_id']);
            return $customer;
        }
        return null;
    }

    public function getRatings($productOnly = false)
    {
        if ($this->getId()) {
            $collection = new Collection();
            $collection->join('review_rating', 'review_rating.rating_id=rating.id', ['value'], 'left')
                    ->where([
                        'status' => 1,
                        'review_rating.review_id' => $this->getId()
                    ]);
            if ($productOnly) {
                $collection->where(['type' => 0]);
            }
            return $collection;
        }
        return [];
    }
}
