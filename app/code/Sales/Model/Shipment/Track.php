<?php

namespace Redseanet\Sales\Model\Shipment;

use Redseanet\Lib\Model\AbstractModel;

class Track extends AbstractModel
{
    protected function construct()
    {
        $this->init('sales_order_shipment_track', 'id', [
            'id', 'shipment_id', 'order_id', 'carrier',
            'carrier_code', 'tracking_number', 'description'
        ]);
    }
}
