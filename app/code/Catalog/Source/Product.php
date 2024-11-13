<?php

namespace Redseanet\Catalog\Source;

use Redseanet\Catalog\Model\Collection\Product as Collection;
use Redseanet\Lib\Session\Segment;
use Redseanet\Lib\Source\SourceInterface;
use Redseanet\Admin\Model\User;

class Product implements SourceInterface
{
    public function getSourceArray()
    {
        $collection = new Collection();
        $result = [];
        $userArray = (new Segment('admin'))->get('user');
        $user = new User();
        $user->load($userArray['id']);
        if ($user->getStore()) {
            $collection->where(['store_id' => $user->getStore()->getId()]);
        }
        foreach ($collection as $product) {
            $result[$product['id']] = $product['name'];
        }
        return $result;
    }
}
