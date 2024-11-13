<?php

namespace Redseanet\Catalog\Source;

use Redseanet\Catalog\Model\Collection\Category as Collection;
use Redseanet\Lib\Session\Segment;
use Redseanet\Lib\Source\SourceInterface;
use Redseanet\Admin\Model\User;

class Category implements SourceInterface
{
    public function getSourceArray($isBackend = true)
    {
        $collection = new Collection();
        $result = [];
        if ($isBackend) {
            $segment = new Segment('admin');
            if ($segment->get('hasLoggedIn')) {
                $userArray = $segment->get('user');
                $user = new User();
                $user->load($userArray['id']);
                if ($user->getStore()) {
                    $collection->where(['store_id' => $user->getStore()->getId()]);
                }
            }
        }
        foreach ($collection as $category) {
            $result[$category['id']] = $category['name'];
        }
        return $result;
    }
}
