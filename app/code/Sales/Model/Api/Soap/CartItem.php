<?php

namespace Redseanet\Sales\Model\Api\Soap;

class CartItem
{
    /**
     * @var int
     */
    public $id;

    /**
     * @var int
     */
    public $product_id;

    /**
     * @var string
     */
    public $product_name;

    /**
     * @var int
     */
    public $store_id;

    /**
     * @var int
     */
    public $warehoust_id;

    /**
     * @var string
     */
    public $options;

    /**
     * @var float
     */
    public $qty;

    /**
     * @var string
     */
    public $sku;

    /**
     * @var bool
     */
    public $is_virtual;

    /**
     * @var bool
     */
    public $free_shipping;

    /**
     * @var float
     */
    public $base_price;

    /**
     * @var float
     */
    public $price;

    /**
     * @var float
     */
    public $base_discount;

    /**
     * @var float
     */
    public $discount;

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
     * @var float
     */
    public $weight;

    /**
     * @var bool
     */
    public $status;
}
