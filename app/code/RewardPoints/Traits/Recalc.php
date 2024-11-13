<?php

namespace Redseanet\RewardPoints\Traits;

use Redseanet\Customer\Model\Customer;
use Redseanet\RewardPoints\Model\Collection\Record as Collection;
use Redseanet\Lib\Model\Collection\Language;
use Laminas\Db\Sql\Expression;

trait Recalc
{
    public function recalc($customerId)
    {
        foreach ((array) $customerId as $id) {
            $collection = new Collection();
            $collection->columns(['customer_id', 'amount' => new Expression('sum(count)')])
                    ->where([
                        'customer_id' => $id,
                        'status' => 1
                    ])->group('customer_id');
            $collection->load(false, true);
            $points = count($collection) ? $collection->toArray()[0]['amount'] : 0;
            $languages = new Language();
            $languages->columns(['id']);
            $languages->load(true, true);
            foreach ($languages as $language) {
                $customer = new Customer($language['id']);
                $customer->load($id);
                $customer->setData('rewardpoints', (int) $points)
                        ->save();
            }
        }
    }
}
