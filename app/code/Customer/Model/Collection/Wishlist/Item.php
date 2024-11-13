<?php

namespace Redseanet\Customer\Model\Collection\Wishlist;

use Redseanet\Lib\Model\AbstractCollection;

class Item extends AbstractCollection
{
    protected function construct()
    {
        $this->init('wishlist_item');
    }
}
