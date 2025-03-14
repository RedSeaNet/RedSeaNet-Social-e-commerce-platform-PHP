<?php

namespace Redseanet\Balance\Traits;

use Redseanet\Customer\Model\Customer;
use Redseanet\Customer\Model\Collection\Balance as Collection;
use Redseanet\Lib\Model\Collection\Language;
use Laminas\Db\Sql\Expression;

trait Recalc {

    public function recalc($customerId) {
        $collection = new Collection();
        $collection->columns(['customer_id', 'amount' => new Expression('sum(amount)')])
                ->where([
                    'customer_id' => $customerId,
                    'status' => 1
                ])->group('customer_id');
        $collection->load(false, true);
        $balances = round((float) (count($collection) ? $collection->toArray()[0]['amount'] : 0), 2);
        $languages = new Language();
        $languages->columns(['id']);
        $languages->load(true, true);
        foreach ($languages as $language) {
            $customer = new Customer($language['id']);
            $customer->load($customerId);
            $customer->setData('balance', $balances);
            $customer->save();
        }
    }

}
