<?php

namespace Redseanet\Promotion\ViewModel;

use Redseanet\Lib\ViewModel\Template;
use Redseanet\Lib\Model\Store;
use Redseanet\Lib\Model\Collection\Store as storeCollection;
use Redseanet\Promotion\Model\Collection\Rule;

class Product extends Template
{
    public function getProductPromotion()
    {
        if ($product = $this->getVariable('product')) {
            $rules = new Rule();
            $rules->withStore(true)
                    ->where(['promotion.status' => 1, 'promotion_in_store.store_id' => $product['store_id']])
                    ->order('promotion.sort_order');
            return $rules;
        }
        return [];
    }
}
