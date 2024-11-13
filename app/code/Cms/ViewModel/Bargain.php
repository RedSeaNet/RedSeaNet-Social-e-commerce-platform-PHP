<?php

namespace Redseanet\Cms\ViewModel;

use Redseanet\Lib\ViewModel\Template;
use Redseanet\Catalog\Model\Collection\Product;
use Redseanet\Catalog\ViewModel\Product\Price;
use Redseanet\Bargain\Model\Collection\Bargain as bargainCollection;
use Redseanet\Lib\Bootstrap;

class Bargain extends Template
{
    protected $bargain = null;

    public function __construct()
    {
        $this->setTemplate('cms/bargain');
    }

    public function getBargains($limit = 8)
    {
        if (is_null($this->bargain)) {
            $collection = new bargainCollection();
            $collection->where('status=1');
            $collection->limit($limit);
            $this->bargain = $collection->load();
        }
        return $this->bargain;
    }

    public function getPriceBox($bargain)
    {
        $box = new Price();
        $box->setVariable('bargain', $bargain);
        return $box;
    }

    public function getLanguageId()
    {
        return Bootstrap::getLanguage()->getId();
    }
}
