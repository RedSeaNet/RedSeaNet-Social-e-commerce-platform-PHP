<?php

namespace Redseanet\Promotion\Model\Collection;

use Redseanet\Lib\Model\AbstractCollection;

class Rule extends AbstractCollection
{
    protected $withStore = false;

    protected function construct()
    {
        $this->init('promotion');
    }

    public function withStore($inColumns = false)
    {
        $this->select->join('promotion_in_store', 'promotion_in_store.promotion_id=promotion.id', $inColumns ? ['store_id'] : [], 'left');
        $this->withStore = true;
        return $this;
    }

    protected function afterLoad(&$result)
    {
        if ($this->withStore) {
            $tmp = [];
            foreach ($result as $item) {
                if (isset($tmp[$item['id']]) && isset($item['store_id'])) {
                    $tmp[$item['id']]['store_id'][] = $item['store_id'];
                } else {
                    $tmp[$item['id']] = $item;
                    if (isset($item['store_id'])) {
                        $tmp[$item['id']]['store_id'] = [$item['store_id']];
                    }
                }
            }
            $result = $tmp;
        }
        parent::afterLoad($result);
    }
}
