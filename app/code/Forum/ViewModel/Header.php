<?php

namespace Redseanet\Forum\ViewModel;

use Redseanet\Lib\Bootstrap;
use Redseanet\Lib\ViewModel\Template;
use Redseanet\Forum\Model\Collection\Category as CategoryCollection;
use Redseanet\Lib\Stdlib\Singleton;
use Redseanet\Lib\Session\Segment;

final class Header extends Template implements Singleton {

    protected static $instance = null;
    protected $base = null;

    private function __construct() {
        
    }

    public static function instance() {
        if (is_null(static::$instance)) {
            static::$instance = new static();
        }
        return static::$instance;
    }

    public function getBase() {
        return $this->base;
    }

    public function setBase($base) {
        $this->base = $base;
        return $this;
    }

    public function getCustomer() {
        $segment = new Segment('customer');
        return $segment->get('customer');
    }

    public function getCategories($systemRecomment = false) {
        $categories = new CategoryCollection();
        //$categories->where(['status' => 1]);
        $categories->where(['parent_id' => 1]);
        $categories->order('sort_order desc');
        $categories->withName();

        return $categories;
    }

}
