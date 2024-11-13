<?php

namespace Redseanet\Bulk\ViewModel;

use Redseanet\Bulk\Model\Bulk;
use Redseanet\Lib\ViewModel\Template;

class Apply extends Template
{
    protected $date = null;
    protected $bulk = null;

    public function getBulk()
    {
        if (is_null($this->bulk) && ($id = $this->getQuery('bulk'))) {
            $this->bulk = new Bulk();
            $this->bulk->load($id);
        }
        return $this->bulk;
    }

    public function getCurrency()
    {
        return $this->getContainer()->get('currency');
    }
}
