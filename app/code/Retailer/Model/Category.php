<?php

namespace Redseanet\Retailer\Model;

use Redseanet\Catalog\Model\Collection\Product;
use Redseanet\Lib\Bootstrap;
use Redseanet\Lib\Model\AbstractModel;
use Redseanet\Lib\Model\Store;
use Redseanet\Retailer\Model\Collection\Category as Collection;

class Category extends AbstractModel
{
    protected function construct()
    {
        $this->init('retailer_category', 'id', ['id', 'parent_id', 'default_name', 'store_id', 'uri_key', 'sort_order']);
    }

    protected function beforeLoad($select)
    {
        $select->join('retailer_category_name', 'retailer_category_name.category_id=retailer_category.id', ['language_id', 'name'], 'left');
        parent::beforeLoad($select);
    }

    protected function afterLoad(&$result)
    {
        if (isset($result[0])) {
            $name = [];
            foreach ($result as $item) {
                $name[$item['language_id']] = $item['name'];
            }
            $result[0]['name'] = $name;
        }
        parent::afterLoad($result);
    }

    protected function beforeSave()
    {
        $this->beginTransaction();
        parent::beforeSave();
    }

    protected function afterSave()
    {
        if (isset($this->storage['name'])) {
            $tableGateway = $this->getTableGateway('retailer_category_name');
            foreach ($this->storage['name'] as $languageId => $name) {
                $tableGateway->upsert(['name' => $name], ['category_id' => $this->getId(), 'language_id' => $languageId]);
            }
        }
        parent::afterSave();
        $this->commit();
    }

    public function getName($languageId = null)
    {
        if (is_null($languageId)) {
            $languageId = Bootstrap::getLanguage()->getId();
        }
        return $this->storage['name'][$languageId] ?? $this->storage['default_name'];
    }

    public function getProducts()
    {
        if ($this->getId()) {
            $products = new Product($this->languageId);
            $tableGateway = $this->getTableGateway('retailer_category_with_product');
            $select = $tableGateway->getSql()->select();
            $select->columns(['id' => 'product_id'])
                    ->where(['category_id' => $this->getId()]);
            $products->in('id', $select);
            return $products;
        }
        return [];
    }

    public function getChildrenCategories()
    {
        if ($this->getId()) {
            $category = new Collection();
            $category->where(['parent_id' => $this->getId()]);
            return $category;
        }
        return [];
    }

    public function getStore()
    {
        if (!empty($this->storage['store_id'])) {
            $store = new Store();
            $store->load($this->storage['store_id']);
            return $store;
        }
        return null;
    }
}
