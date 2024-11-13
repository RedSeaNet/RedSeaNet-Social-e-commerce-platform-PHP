<?php

namespace Redseanet\Admin\ViewModel\Catalog\Edit\Product;

use Redseanet\Admin\ViewModel\Catalog\Grid\Product;
use Redseanet\Catalog\Model\Collection\Product as Collection;
use Redseanet\Catalog\Model\Product as Model;
use Redseanet\Lib\Session\Segment;
use Redseanet\Lib\Source\Store;
use Redseanet\Admin\Model\User;

class Link extends Product
{
    protected $action = [];
    protected $type = '';
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

    protected function prepareColumns($columns = [])
    {
        $userArray = (new Segment('admin'))->get('user');
        $user = new User();
        $user->load($userArray['id']);
        return \Redseanet\Admin\ViewModel\Eav\Grid::prepareColumns([
            'store_id' => ($user->getStore() ? [
                'type' => 'hidden',
                'value' => $user->getStore()->getId(),
                'use4sort' => false,
                'use4filter' => false
            ] : [
                'type' => 'select',
                'options' => (new Store())->getSourceArray(),
                'label' => 'Store'
            ]),
            'name' => [
                'label' => 'Name',
                'type' => 'text'
            ],
            'sku' => [
                'label' => 'SKU',
                'type' => 'text'
            ]
        ]);
    }

    protected function prepareCollection($collection = null)
    {
        $collection = new Collection();
        $collection->getSelect()->where->notEqualTo('id', $this->getRequest()->getQuery('id'));
        if (!$this->getQuery('desc')) {
            $this->query['desc'] = 'created_at';
        }
        return parent::prepareCollection($collection);
    }

    public function getActiveIds()
    {
        $collection = (new Model())->setId($this->getRequest()->getQuery('id'))
                ->getLinkedProducts($this->getType());
        $old = $this->getSegment('admin')->get('form_data_product_link', false);
        if ($old === false) {
            $activeIds = [];
        } else {
            $activeIds = $old[$this->getType()]['product_link'];
        }
        if (count($collection)) {
            foreach ($collection->toArray() as $item) {
                if ($old === false || !in_array($item['id'], $old[$this->getType()]['remove'])) {
                    $activeIds[] = $item['id'];
                }
            }
        }
        return $activeIds;
    }
}
