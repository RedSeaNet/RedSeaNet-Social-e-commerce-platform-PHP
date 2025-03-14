<?php

namespace Redseanet\Balance\Mq;

use Redseanet\Lib\Mq\MqInterface;
use Redseanet\Customer\Model\Balance;

class Recalc implements MqInterface {

    use \Redseanet\Balance\Traits\Recalc;
    use \Redseanet\Lib\Traits\Container;

    public function afterCustomerLogin($data) {
        print_r($data);
        $this->recalc($data['id']);
    }

    public function afterOrderPlace($data) {
        $config = $this->getContainer()->get('config');
        if ($config['balance/general/enable'] && $config['balance/general/product_for_recharge'] && $data['customer_id']) {
            foreach ($data["items"] as $item) {
                if ($item['product_id'] == $config['balance/general/product_for_recharge']) {
                    $recharge = new Balance([
                        'customer_id' => $data['customer_id'],
                        'order_id' => $data['id'],
                        'amount' => $item['qty'],
                        'comment' => 'Recharge Product',
                        'status' => 0
                    ]);
                    $recharge->save();
                }
            }
        }
    }

}
