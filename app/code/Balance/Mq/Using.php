<?php

namespace Redseanet\Balance\Mq;

use Redseanet\Lib\Mq\MqInterface;
use Redseanet\Customer\Model\Balance;
use Redseanet\Customer\Model\Collection\Balance as Collection;
use Redseanet\Sales\Model\Order;
use Redseanet\Lib\Bootstrap;

class Using implements MqInterface {

    use \Redseanet\Lib\Traits\Container;
    use \Redseanet\Lib\Traits\DB;
    use \Redseanet\Lib\Traits\DataCache;
    use \Redseanet\Balance\Traits\Calc;
    use \Redseanet\Balance\Traits\Recalc;

    public function afterOrderPlace($data) {
        $config = $this->getContainer()->get('config');
        $model = $data;
        Bootstrap::getContainer()->get('log')->logException(new \Exception('mp afterOrderPlace ------'));
        Bootstrap::getContainer()->get('log')->logException(new \Exception(json_encode($data)));
        $discountDetail = json_decode($model['discount_detail'], true);
        if ($config['balance/general/enable'] && $config['balance/general/product_for_recharge'] && !empty($model['customer_id'])) {
            $balances = (float) (!empty($discountDetail["balance"]["total"]) ? $discountDetail["balance"]["total"] : 0);
            if ($balances && $balances > 0) {
                $record = new Balance([
                    'customer_id' => $model['customer_id'],
                    'order_id' => $model['id'],
                    'amount' => -$balances,
                    'status' => 1,
                    'comment' => 'Consumption'
                ]);
                $record->save();
            }
        }
    }

}
