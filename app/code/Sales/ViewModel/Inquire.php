<?php

namespace Redseanet\Sales\ViewModel;

use Redseanet\Sales\Model\Collection\Order as Collection;
use Redseanet\Lib\ViewModel\Template;

class Inquire extends Template
{
    public function getInquireies()
    {
        $collection = new Collection();
        $collection->where(['increment_id' => $this->getRequest()->getPost('increment_id')]);
        return $collection;
    }
}
