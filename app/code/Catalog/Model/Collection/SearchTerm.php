<?php

namespace Redseanet\Catalog\Model\Collection;

use Redseanet\Search\Model\Collection\Term;

class SearchTerm extends Term
{
    protected function construct()
    {
        $this->init('product_search_term');
    }
}
