<?php

namespace Redseanet\Customer\Source;

use Redseanet\Customer\Model\Collection\Customer as Collection;
use Redseanet\Lib\Session\Segment;
use Redseanet\Lib\Source\SourceInterface;
use Redseanet\Admin\Model\User;

class Customer implements SourceInterface
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
        foreach ($collection as $item) {
            $result[$item['id']] = $item['username'];
        }
        return $result;
    }
}
