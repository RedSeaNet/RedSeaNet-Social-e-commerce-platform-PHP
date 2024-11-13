<?php

namespace Redseanet\Sales\Model\Api\Soap;

use Exception;
use Redseanet\Api\Model\Api\AbstractHandler;
use Redseanet\Customer\Model\Customer;
use Redseanet\Lib\Session\Segment;
use Redseanet\Sales\Model\Cart as Model;

class Cart extends AbstractHandler
{
    /**
     * @var int
     */
    public $billing_address_id;

    /**
     * @var int
     */
    public $shipping_address_id;

    /**
     * @var string
     */
    public $billing_address;

    /**
     * @var string
     */
    public $shipping_address;

    /**
     * @var bool
     */
    public $is_virtual;

    /**
     * @var bool
     */
    public $free_shipping;

    /**
     * @var string
     */
    public $coupon;

    /**
     * @var string
     */
    public $base_currency = '';

    /**
     * @var string
     */
    public $currency = '';

    /**
     * @var string
     */
    public $shipping_method;

    /**
     * @var string
     */
    public $payment_method;

    /**
     * @var float
     */
    public $base_subtotal;

    /**
     * @var float
     */
    public $subtotal;

    /**
     * @var float
     */
    public $base_shipping;

    /**
     * @var float
     */
    public $shipping;

    /**
     * @var float
     */
    public $base_discount;

    /**
     * @var float
     */
    public $discount;

    /**
     * @var string
     */
    public $discount_detail;

    /**
     * @var float
     */
    public $base_tax;

    /**
     * @var float
     */
    public $tax;

    /**
     * @var float
     */
    public $base_total;

    /**
     * @var float
     */
    public $total;

    /**
     * @var string
     */
    public $additional;

    /**
     * @var string
     */
    public $customer_note;

    /**
     * @var bool
     */
    public $status;

    /**
     * @var \Redseanet\Sales\Model\Api\Soap\CartItem[]
     */
    public $items;

    /**
     * @param int $customerId
     * @return Model
     */
    protected function getCart($customerId)
    {
        $segment = new Segment('customer');
        $segment->set('hasLoggedIn', true)
                ->set('customer', (new Customer())->setId($customerId));
        return Model::instance();
    }

    /**
     * @param string $sessionId
     * @param int $customerId
     * @param bool $withItems
     * @return object
     */
    public function cartInfo($sessionId, $customerId, $withItems = false)
    {
        $this->validateSessionId($sessionId, __FUNCTION__);
        $cart = $this->getCart($customerId);
        $result = $cart->toArray();
        if ($withItems) {
            $items = $cart->getItems(true);
            $items->load(true, true);
            $result['items'] = [];
            foreach ($items->toArray() as $item) {
                $result['items'][] = $this->response($item, '\\Redseanet\\Sales\\Model\\Api\\CartItem');
            };
        }
        return $this->response($result);
    }

    /**
     * @param string $sessionId
     * @param int $customerId
     * @param int $productId
     * @param float $qty
     * @param int $warehouseId
     * @param string $options
     * @param string $sku
     * @return bool
     */
    public function cartAddItem($sessionId, $customerId, $productId, $qty, $warehouseId, $options = '[]', $sku = '')
    {
        $this->validateSessionId($sessionId, __FUNCTION__);
        $cart = $this->getCart($customerId);
        try {
            $options = json_decode($options, true);
            $cart->addItem($productId, $qty, $warehouseId, $options, $sku);
            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * @param string $sessionId
     * @param int $customerId
     * @param int $itemId
     * @param float $qty
     * @return bool
     */
    public function cartChangeItemQty($sessionId, $customerId, $itemId, $qty)
    {
        $this->validateSessionId($sessionId, __FUNCTION__);
        $cart = $this->getCart($customerId);
        try {
            $cart->changeQty($itemId, $qty);
            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * @param string $sessionId
     * @param int $customerId
     * @param int $itemId
     * @return bool
     */
    public function cartRemoveItem($sessionId, $customerId, $itemId)
    {
        $this->validateSessionId($sessionId, __FUNCTION__);
        $cart = $this->getCart($customerId);
        try {
            $cart->removeItem($itemId);
            return true;
        } catch (Exception $e) {
            return false;
        }
    }
}
