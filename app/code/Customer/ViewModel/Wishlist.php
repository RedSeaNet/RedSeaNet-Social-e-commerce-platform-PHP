<?php

namespace Redseanet\Customer\ViewModel;

use Redseanet\Customer\Model\Collection\Wishlist\Item as Collection;

class Wishlist extends Account
{
    public function getItems()
    {
        $collection = new Collection();
        $collection->join('wishlist', 'wishlist.id=wishlist_item.wishlist_id', [], 'left')
                ->where(['wishlist.customer_id' => $this->getCustomer()->getId()])
                ->order('added_at DESC');
        return $collection;
    }
}
