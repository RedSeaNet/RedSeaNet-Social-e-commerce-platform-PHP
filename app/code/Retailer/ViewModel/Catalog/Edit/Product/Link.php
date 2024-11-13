<?php

namespace Redseanet\Retailer\ViewModel\Catalog\Edit\Product;

use Redseanet\Retailer\ViewModel\Catalog\Grid\Product;
use Redseanet\Catalog\Model\Collection\Product as Collection;
use Redseanet\Catalog\Model\Product as Model;
use Laminas\Db\Sql\Predicate\Operator;

class Link extends Product
{
    protected $action = [];
    protected $type = '';
    protected $activeIds = null;
    protected $bannedFields = ['id', 'linktype', 'attribute_set', 'product_type'];

    public function __construct()
    {
        $this->setTemplate('admin/catalog/product/link');
    }

    public function getType()
    {
        return $this->type ?: $this->getQuery('linktype');
    }

    public function setType($type)
    {
        $this->type = $type;
        $this->query = [];
        return $this;
    }

    public function getOrderByUrl($attr)
    {
        $query = $this->getQuery();
        if (isset($query['asc'])) {
            if ($query['asc'] == $attr) {
                unset($query['asc']);
                $query['desc'] = $attr;
            } else {
                $query['asc'] = $attr;
            }
        } elseif (isset($query['desc'])) {
            if ($query['desc'] == $attr) {
                unset($query['desc']);
                $query['asc'] = $attr;
            } else {
                $query['desc'] = $attr;
            }
        } else {
            $query['asc'] = $attr;
        }
        return $this->getAdminUrl('catalog_product/list/?linktype=' . $this->getType() . '&' . http_build_query($query));
    }

    protected function prepareCollection($collection = null)
    {
        $collection = new Collection();
        if ($id = $this->getRequest()->getQuery('id')) {
            $collection->where(new Operator('id', '!=', $id));
        }
        return parent::prepareCollection($collection);
    }

    public function getActiveIds()
    {
        if (is_null($this->activeIds)) {
            $collection = (new Model())->setId($this->getRequest()->getQuery('id'))
                    ->getLinkedProducts($this->getType());
            $this->activeIds = [];
            if (count($collection)) {
                foreach ($collection->toArray() as $item) {
                    $this->activeIds[] = $item['id'];
                }
            }
        }
        return $this->activeIds;
    }
}
