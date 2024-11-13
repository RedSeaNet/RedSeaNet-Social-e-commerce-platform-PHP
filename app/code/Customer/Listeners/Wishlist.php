<?php

namespace Redseanet\Customer\Listeners;

use Redseanet\Customer\Model\Wishlist as Model;
use Redseanet\Lib\Listeners\ListenerInterface;
use Redseanet\Lib\Session\Segment;
use Laminas\Db\TableGateway\TableGateway;

class Wishlist implements ListenerInterface
{
    use \Redseanet\Lib\Traits\Container;

    use \Redseanet\Lib\Traits\DataCache;

    public function afterAddToCart($event)
    {
        $segment = new Segment('customer');
        if ($segment->get('hasLoggedIn')) {
            $wishlist = new Model();
            $customer = $segment->get('customer');
            $wishlist->load($customer['id'], 'customer_id');
            if ($wishlist->getId()) {
                $tableGateway = new TableGateway('wishlist_item', $this->getContainer()->get('dbAdapter'));
                $tableGateway->delete([
                    'wishlist_id' => $wishlist->getId(),
                    'product_id' => $event['product_id']
                ]);
                $this->flushList('wishlist');
                $this->flushList('wishlist_item');
            }
        }
    }
}
