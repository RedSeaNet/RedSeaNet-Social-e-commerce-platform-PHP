<?php

namespace Redseanet\Bulk\Traits;

use Redseanet\Lib\Session\Segment;
use Redseanet\Sales\Model\Cart;
use Laminas\Db\Sql\Expression;

trait Cancel
{
    protected function doCancel()
    {
        $segment = new Segment('bulk');
        $ids = [Cart::instance()->getId()];
        if ($id = $segment->get('cart_id', false)) {
            $ids[] = $id;
            $this->flushRow($id, null, 'sales_cart');
        }
        $tableGateway = $this->getTableGateway('sales_cart');
        $tableGateway->update(['status' => new Expression('!status')], ['id' => [$ids]]);
        $this->flushRow(Cart::instance()->getId(), null, 'sales_cart');
        $this->flushList('sales_cart');
        $segment->clear();
        (new Segment('customer'))->offsetUnset('cart');
    }
}
