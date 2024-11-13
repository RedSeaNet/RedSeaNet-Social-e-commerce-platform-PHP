<?php

namespace Redseanet\Retailer\Model;

use Redseanet\Catalog\Model\Collection\Product\Rating;
use Redseanet\Lib\Model\AbstractModel;
use Redseanet\Lib\Model\Store;
use Laminas\Db\Sql\Expression;
use Redseanet\Retailer\Model\Collection\Manager;

class Retailer extends AbstractModel
{
    protected $store = null;

    protected function construct()
    {
        $this->init('retailer', 'id', ['id', 'store_id', 'description', 'contact', 'keywords', 'address', 'tel', 'uri_key', 'profile', 'watermark', 'banner']);
    }

    public function getStore()
    {
        if (is_null($this->store) && !empty($this->storage['store_id'])) {
            $store = new Store();
            $store->load($this->storage['store_id']);
            if ($store->getId()) {
                $this->store = $store;
            }
        }
        return $this->store;
    }

    public function getStoreUrl()
    {
        if (!empty($this->storage['uri_key'])) {
            return 'store/' . $this->storage['uri_key'] . '.html';
        }
        return '';
    }

    public function getRatings()
    {
        if (!empty($this->storage['store_id'])) {
            $collection = new Rating();
            $collection->columns(['id', 'title', 'type'])
                    ->join('review_rating', 'review_rating.rating_id=rating.id', ['value' => new Expression('avg(value)')], 'left')
                    ->join('review', 'review.id=review_rating.review_id', [], 'left')
                    ->join('product_entity', 'product_entity.id=review.product_id', [], 'left')
                    ->where([
                        'product_entity.store_id' => $this->storage['store_id'],
                        'rating.status' => 1
                    ])->group(['rating.id', 'rating.title', 'rating.type']);
            return $collection;
        }
        return [];
    }

    protected function beforeLoad($select)
    {
        $select->join('retailer_manager', 'retailer_manager.retailer_id=retailer.id', ['customer_id'], 'left');
        parent::beforeLoad($select);
    }

    protected function afterSave()
    {
        if (!empty($this->storage['customer_id'])) {
            $tableGateway = $this->getTableGateway('retailer_manager');
            $this->upsert(['retailer_id' => $this->getId()], ['customer_id' => $this->storage['customer_id']], $tableGateway);
        }
        parent::afterSave();
    }

    public function getManager()
    {
        $managers = new Manager();
        $managers->where(['retailer_id' => $this->storage['id']]);
        return $managers;
    }
}
