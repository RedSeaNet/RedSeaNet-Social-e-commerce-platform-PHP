<?php

namespace Redseanet\Admin\ViewModel\I18n\Grid;

use Redseanet\Admin\ViewModel\Grid as PGrid;
use Redseanet\Lib\Model\Collection\Merchant;
use Redseanet\Lib\Session\Segment;
use Redseanet\Admin\Model\User;

class Scope extends PGrid
{
    protected function prepareCollection($collection = null)
    {
        $collection = new Merchant();
        $collection->join('core_store', 'core_store.merchant_id = core_merchant.id', ['store' => 'name', 'store_id' => 'id'], 'left')
                ->join('core_language', 'core_language.merchant_id = core_merchant.id', ['language' => 'name', 'language_id' => 'id'], 'left')
                ->columns(['merchant' => 'code', 'merchant_id' => 'id'])
                ->order('core_merchant.id, core_store.id, core_language.id');
        return $collection->load(true, true);
    }

    public function getUser()
    {
        $segment = new Segment('admin');
        $userArray = $segment->get('user');
        $user = new User();
        $user->load($userArray['id']);
        return $user;
    }
}
