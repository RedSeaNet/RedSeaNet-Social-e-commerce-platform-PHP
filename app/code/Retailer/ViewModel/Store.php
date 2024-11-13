<?php

namespace Redseanet\Retailer\ViewModel;

use Redseanet\Lib\Session\Segment;
use Redseanet\Lib\ViewModel\Template;
use Redseanet\Retailer\Model\Retailer;

class Store extends Template
{
    protected $retailer = null;

    public function getRetailer()
    {
        if (is_null($this->retailer)) {
            $this->retailer = new Retailer();
            $segment = new Segment('customer');
            $this->retailer->load($segment->get('customer')['id'], 'customer_id');
        }
        return $this->retailer;
    }

    public function getStore()
    {
        return $this->getRetailer()->getStore();
    }

    public function getCustomer()
    {
        $segment = new Segment('customer');
        return $segment->get('customer');
    }
}
