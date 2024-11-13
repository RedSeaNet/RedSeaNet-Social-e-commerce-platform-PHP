<?php

namespace Redseanet\Retailer\ViewModel;

use Redseanet\Lib\Bootstrap;
use Redseanet\Lib\ViewModel\Template;
use Redseanet\Lib\Session\Segment;
use Redseanet\Catalog\Model\Product as Pmodel;
use Redseanet\Catalog\Model\Collection\Product as Pcollection;
use Redseanet\Catalog\Model\Collection\Category as Ccollection;
use Redseanet\Retailer\Model\Collection\ProductInCategoryCollection as Picollection;
use Redseanet\Catalog\Model\Warehouse;
use Redseanet\Retailer\Model\Retailer;
use Laminas\Db\Sql\Expression;

class SalesProducts extends Template
{
    protected $categories = null;

    /**
     * getRetailerSalesProducts
     * Get retailer's products in sales record by search condition
     *
     * @access public
     * @return object
     */
    public function getRetailerSalesProducts($params = [], $current_store_id = null)
    {
        return $this->fetchRetailerProducts($params, 1, 1, $current_store_id);
    }

    public function getRetailerSalesProductsCount($params = [], $current_store_id = null)
    {
        return $this->fetchRetailerProducts($params, 1, 1, $current_store_id, 1);
    }

    /**
     * getRetailerStockProducts
     * Get retailer's products in sales record by search condition
     *
     * @access public
     * @return object
     */
    public function getRetailerStockProducts($params = [])
    {
        return $this->fetchRetailerProducts($params, 1, 0);
    }

    /**
     * getRetailerHistoryProducts
     * Get retailer's products history by search condition
     *
     * @access public
     * @return object
     */
    public function getRetailerHistoryProducts($params = [])
    {
        return $this->fetchRetailerProducts($params, 0, -1);
    }

    /**
     * fetchRetailerProducts
     * Get retailer product form database
     *
     * @param $delete_status  status in product_1_index
     * @param $stock_status   status in warehouse_inventory
     * @access protected
     * @return object
     */
    public function fetchRetailerProducts($params, $delete_status, $stock_status, $current_store_id = null, $judgeCount = 0)
    {
        $storeid = null;
        if (empty($current_store_id)) {
            $user = (new Segment('customer'))->get('customer');
            $retailer = new Retailer();
            $retailer->load($user['id'], 'customer_id');
            if ($retailer->getId()) {
                $storeid = $retailer->offsetGet('store_id');
            }
        } else {
            $storeid = $current_store_id;
        }
        $condition = $params;
        $sales_products = new Pcollection();
        $where = new \Laminas\Db\Sql\Where();
        $where->equalTo('main_table.status', $delete_status);
        $where->nest->isNull('new_end')->or->greaterThanOrEqualTo('new_end', date('Y-m-d H:i:s'))->unnest;
        if ($storeid) {
            $where->equalTo('store_id', $storeid);
        }
        //Search condition
        if (!empty($condition['name'])) {
            $where->like('name', '%' . $condition['name'] . '%');
        }
        if (!empty($condition['sku'])) {
            $where->like('sku', '%' . $condition['sku'] . '%');
        }
        if (!empty($condition['price_from'])) {
            $where->greaterThanOrEqualTo('price', $condition['price_from']);
        }
        if (!empty($condition['price_to'])) {
            $where->lessThanOrEqualTo('price', $condition['price_to']);
        }

        if (!empty($condition['product_ids'])) {
            $where->in('id', $condition['product_ids']);
        }
        if (isset($condition['catalog']) && $condition['catalog'] != '') {
            $product_in_category_collection = new Picollection();
            $product_in_category_id = $product_in_category_collection->columns(['product_id'])->where(['category_id' => intval($condition['catalog'])]);
            $where->in('id', $product_in_category_id);
        }
        if (isset($condition['recomend_status']) && $condition['recomend_status'] != '') {
            $where->equalTo('recommend', $condition['recomend_status']);
        }

        $stock_status_option = ($stock_status == 0) ? '=' : '>=';
        $sales_products
                ->where($where)
                ->join(['wi' => 'warehouse_inventory'], 'wi.product_id = id', ['sales_status' => new Expression('SUM(wi.status)')], 'left')
                ->group('id')
                ->having(['sales_status ' . $stock_status_option . ' ' . $stock_status])
                ->order(['created_at' => 'DESC']);
        //echo $sales_products->getSqlString(Bootstrap::getContainer()->get("dbAdapter")->getPlatform());
        if ($judgeCount == 1) {
            return count($sales_products);
        } else {
            return $this->prepareCollection($sales_products, $condition);
        }
    }

    /**
     * unidimensional
     * Convert multi dimensional to unidimensional
     *
     * @access protected
     * @return object
     */
    protected function unidimensional($array, $level)
    {
        static $result_array = [];
        echo $level;
        foreach ($array as &$item) {
            if (is_array($item)) {
                $this->unidimensional($item, $level + 1);
            } else {
                $item['level'] = $level;
                $result_array[] = $item;
            }
        }
        return $result_array;
    }

    /**
     * getCategories
     * Get main category list
     *
     * @access public
     * @return object
     */
    public function getCategories()
    {
        if (is_null($this->categories)) {
            $collection = new Ccollection();
            $collection->order('parent_id ASC, sort_order ASC');
            $this->categories = [];
            foreach ($collection as $item) {
                $pid = (int) $item['parent_id'];
                if (!isset($this->categories[$pid])) {
                    $this->categories[$pid] = [];
                }
                $this->categories[$pid][] = $item;
            }
        }
        return $this->categories;
    }

    public function renderCategory($level = 0, $class = 0, $current_id = null)
    {
        $html = '';
        $selected = '';
        if (!empty($this->getCategories()[$level])) {
            foreach ($this->getCategories()[$level] as $category) {
                if ($category['id'] == $current_id) {
                    $selected = "selected='selected'";
                }
                $html .= '<option  value="' . $category['id'] . '" ' . $selected . '>';
                $html .= str_repeat('&nbsp;&nbsp;', $class) . $category['name'];
                $html .= '</option>';
                $html .= (isset($this->getCategories()[$category['id']]) ? $this->renderCategory($category['id'], $class + 1, $current_id) : '');
            }
        }
        return $html;
    }

    /**
     * getInventory
     * @access public
     * @param int $productID product id
     * @return object
     */
    public function getInventory($productId, $sku = '', $warehouse = 1)
    {
        $warehouse = (new Warehouse())->setId($warehouse);
        $warehouse_qty = $warehouse->getInventory($productId, $sku);
        if (!empty($warehouse_qty['qty'])) {
            return $warehouse_qty['qty'];
        } else {
            return 0;
        }
    }

    /**
     * getCurrency
     * Get price with currency
     *
     * @access public
     * @return object
     */
    public function getCurrency()
    {
        return $this->getContainer()->get('currency');
    }

    /**
     * getProduct
     * Get product obj by product id
     *
     * @access public
     * @param int $productID product id
     * @return object
     */
    public function getProduct($productID)
    {
        $product_model = new Pmodel();
        $product = $product_model->load($productID);
        return $product;
    }

    /**
     * Get current url
     *
     * @return string
     */
    public function getCurrentUrl()
    {
        return $this->getUri()->withQuery('')->withFragment('')->__toString();
    }

    /**
     * Handle sql for collection
     *
     * @param AbstractCollection $collection
     * @return AbstractCollection
     */
    protected function prepareCollection($collection = null, $params = [])
    {
        if (is_null($collection)) {
            return [];
        }
        $condition = !empty($params) ? $params : $this->getQuery();
        $limit = $condition['limit'] ?? 20;
        if (isset($condition['page'])) {
            $collection->offset(($condition['page'] - 1) * $limit);
            unset($condition['page']);
        }
        $collection->limit((int) $limit);
        unset($condition['limit']);
        if (isset($condition['asc'])) {
            $collection->order((strpos($condition['asc'], ':') ?
                            str_replace(':', '.', $condition['asc']) :
                            $condition['asc']) . ' ASC');
            unset($condition['asc']);
        } elseif (isset($condition['desc'])) {
            $collection->order((strpos($condition['desc'], ':') ?
                            str_replace(':', '.', $condition['desc']) :
                            $condition['desc']) . ' DESC');
            unset($condition['desc']);
        }
        return $collection;
    }
}
