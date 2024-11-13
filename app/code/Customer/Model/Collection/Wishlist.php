<?php

namespace Redseanet\Customer\Model\Collection;

use Redseanet\Lib\Model\AbstractCollection;
use Redseanet\Lib\Bootstrap;

class Wishlist extends AbstractCollection
{
    protected function construct()
    {
        $this->init('wishlist');
    }

    protected function beforeLoad()
    {
        //$this->select->join('wishlist_item', 'wishlist_item.wishlist_id=wishlist.id');
        parent::beforeLoad();
    }
}
