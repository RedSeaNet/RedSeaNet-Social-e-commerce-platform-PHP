<?php

namespace Redseanet\Bargain\ViewModel;

use Redseanet\Lib\ViewModel\Template;
use Redseanet\Resource\Model\Resource;

class Bargain extends Template
{
    public function getCurrency()
    {
        return $this->getContainer()->get('currency');
    }

    public function getThumbnail($thumbnail)
    {
        $resource = new Resource();
        $resource->load($thumbnail);
        return $this->getResourceUrl('image/' . $resource['real_name']);
    }
}
