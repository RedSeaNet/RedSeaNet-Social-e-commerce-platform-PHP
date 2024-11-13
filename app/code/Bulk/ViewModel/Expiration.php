<?php

namespace Redseanet\Bulk\ViewModel;

use Redseanet\Bulk\Model\Bulk;
use Redseanet\Lib\ViewModel\Template;

class Expiration extends Template
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

    public function getDate()
    {
        if (is_null($this->date) && $this->getQuery('bulk')) {
            $expiration = [];
            foreach ($this->getBulk()->getItems() as $item) {
                $expiration[] = $item['product']['bulk_expiration'] ?? 5;
            }
            $this->date = date('Y-m-d', strtotime($this->getBulk()['created_at']) + 86400 * min($expiration));
        }
        return $this->date;
    }

    public function setDate($date)
    {
        $this->date = $date;
        return $this;
    }
}
