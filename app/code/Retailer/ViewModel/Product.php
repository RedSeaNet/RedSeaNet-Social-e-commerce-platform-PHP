<?php

namespace Redseanet\Retailer\ViewModel;

use Redseanet\Lib\ViewModel\Template;
use Redseanet\Lib\Session\Segment;
use Redseanet\Sales\Model\Order as Omodel;
use Redseanet\Retailer\Model\Collection\Product as Collection;
use Redseanet\Sales\Model\Collection\Order as Ocollection;
use Redseanet\Sales\Model\Collection\Order\Item as Icollection;
use Redseanet\Customer\Model\Customer as Cmodel;
use Redseanet\Catalog\Model\Product as Pmodel;
use Redseanet\Sales\Model\Collection\Order\Status as Scollection;
use Redseanet\Catalog\Model\Collection\Product\Option as Pocollection;
use Laminas\Db\Sql\Expression;

class Product extends Template
{
    /**
     * getRetailerTransaction
     * Get retailer's transaction record by search condition
     *
     * @access public
     * @return object
     */
    public function getRetailerTransaction()
    {
        $condition = $this->getQuery();
        $segment = new Segment('customer');
        $customerArray = $segment->get('customer');
        $customer = new Cmodel();
        $customer->load($customer['id']);
        $retailer = $customer->getRetailer();

        //Sub query to select order id from sales_order table
        $sales_order_id_collection = new Ocollection();
        $order_id_select = $sales_order_id_collection->columns(['id'])->where(['sales_order.store_id' => $retailer->offsetGet('store_id')]);

        if ($sales_order_id_collection->count() <= 0) {
            return [];
        }

        $where_order_id = new \Laminas\Db\Sql\Where();
        $where_order_id->in('order_id', $order_id_select);

        //Get total price for order
        $sales_order_product_collection = new Icollection();
        $order_total_price = $sales_order_product_collection->columns(['order_id', 'total_order_price' => new Expression('SUM(total)')])->where($where_order_id)->group('order_id');

        //Generate where condition
        $where = new \Laminas\Db\Sql\Where();
        $where->in('op.order_id', $order_id_select);
        if (!empty($condition['track_id'])) {
            $where->like('sost.tracking_number', $condition['track_id']);
        }
        if (!empty($condition['increment_id'])) {
            $where->like('sales_order.increment_id', '%' . $condition['increment_id'] . '%');
        }
        if (!empty($condition['order_status']) && $condition['order_status'] > 0) {
            $where->equalTo('sales_order.status_id', $condition['order_status']);
        }
        if (!empty($condition['receiver'])) {
            $where->like('sales_order.shipping_address', '%' . $condition['receiver'] . '%');
        }
        if (!empty($condition['receiver_phone'])) {
            $where->like('sales_order.shipping_address', '%' . $condition['receiver_phone'] . '%');
        }
        if (!empty($condition['add_time_from'])) {
            $where->greaterThanOrEqualTo('sales_order.created_at', date('Y-m-d', strtotime($condition['add_time_from'])));
        }
        if (!empty($condition['add_time_to'])) {
            $where->lessThanOrEqualTo('sales_order.created_at', date('Y-m-d 23:59:59', strtotime($condition['add_time_to'])));
        }
        $sales_order_collection = new Ocollection();
        $sales_order_collection
                ->where($where)
//      ->where(['sales_order.store_id' => $segment->get('customer')['store_id']])
                ->join(['sos' => 'sales_order_status'], 'sos.id = sales_order.status_id', ['status_name' => 'name'], 'left')
                ->join(['op' => $order_total_price], 'sales_order.id = op.order_id', ['total_order_price'], 'left')
                ->join(['sost' => 'sales_order_shipment_track'], 'sales_order.id = sost.order_id', ['tracking_number'], 'left')
                ->order(['sales_order.created_at' => 'DESC']);
        $sales_order_collection = $this->prepareCollection($sales_order_collection);
        //      if(!empty($condition['limit'])){
        //          $sales_order_collection->limit((int) $condition['limit']);
        //      }
        //      unset($condition['limit']);
        //Debug code
        //      $adapter = $this->getContainer()->get('dbAdapter');
        //      $sql = $sales_order_collection->getSqlString($adapter->getPlatform(), $adapter::QUERY_MODE_EXECUTE);
        //      echo "<pre>";
        //      print_r($sql);
        //      echo "</pre>";
        //      exit();
        //Get product list in the order
        $item_collection = new Icollection();
        //$item_collection->where(['order_id' => $order_id_select]);
        $item_collection->where($where_order_id);

        //Seperate product by order id
        $product_list = [];
        foreach ($item_collection as $item) {
            $product_list[$item['order_id']][] = $item;
        }

        //Combine product list to sales order
        foreach ($sales_order_collection as &$order) {
            if (!empty($product_list[$order['id']])) {
                $order['items'] = $product_list[$order['id']];
            }
        }

        return $sales_order_collection;
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
     * getCustomerByID
     * Get customer by customer id
     *
     * @access public
     * @param int $customerID customer id
     * @return object
     */
    public function getCustomerByID($customerID)
    {
        $customer_model = new Cmodel();
        $customer = $customer_model->load($customerID);
        return $customer;
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
     * getAllSalesStatus
     * Get all sales status list or specific sales status by status id
     *
     * @access public
     * @param int $statusID sales status id
     * @return object
     */
    public function getAllSalesStatus($statusID = null)
    {
        $status_collection = new Scollection();
        if (!empty($statusID)) {
            $status_collection->where('id', $statusID);
        } else {
            $status_collection->order('id');
        }
        return $status_collection->toArray();
    }

    /**
     * getAllSalesStatus
     * Get all sales status list or specific sales status by status id
     *
     * @access public
     * @param int $statusID sales status id
     * @return object
     */
    public function getProductOptions($product_id, $json_options)
    {
        if (is_null($product_id) || is_null($json_options) || empty($json_options)) {
            return [];
        }
        $options_array = json_decode($json_options, true);
        if (!is_array($options_array) || empty($options_array)) {
            return [];
        }

        //$sub_where = new \Laminas\Db\Sql\Where();
        $where = new \Laminas\Db\Sql\Where();
        $where->equalTo('product_option.product_id', $product_id);

        $temp_where = new \Laminas\Db\Sql\Where();
        foreach ($options_array as $key => $item) {
            $sub_where = new \Laminas\Db\Sql\Where();
            $sub_where->equalTo('product_option.id', $key);
            $sub_where->equalTo('povt.value_id', $item);
            $temp_where->addPredicate($sub_where, \Laminas\Db\Sql\Where::OP_OR);
        }
        $where->addPredicate($temp_where);
        $pocollection = new Pocollection();
        $pocollection
                ->withLabel()
                ->join(['pov' => 'product_option_value'], 'product_option.id=pov.option_id', ['pov_id' => 'id'], 'left')
                ->join(['povt' => 'product_option_value_title'], 'pov.id=povt.value_id', ['option_value' => 'title', 'value_id'], 'left')
                ->where($where);

        //      $adapter = $this->getContainer()->get('dbAdapter');
        //      $sql = $pocollection->getSqlString($adapter->getPlatform(), $adapter::QUERY_MODE_EXECUTE);
        //      echo "<pre>";
        //      print_r($sql);
        //      echo "</pre>";
        return $pocollection->toArray();
    }

    public function getOrder($order_id)
    {
        if (!empty($order_id)) {
            return (new Omodel())->load($order_id);
        }
        return null;
    }

    /**
     * Handle sql for collection
     *
     * @param AbstractCollection $collection
     * @return AbstractCollection
     */
    protected function prepareCollection($collection = null)
    {
        if (is_null($collection)) {
            return [];
        }
        $condition = $this->getQuery();
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

    /**
     * {@inhertdoc}
     */
    protected function getRendered($template)
    {
        $collection = $this->prepareCollection();
        if ($collection instanceof AbstractCollection) {
            $collection->load();
        }
        $this->setVariable('collection', $collection);
        return parent::getRendered($template);
    }
}
