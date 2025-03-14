<?php

namespace Redseanet\Forum\ViewModel;

use Redseanet\Customer\Model\Collection\Customer as CustomerCollection;
use Redseanet\Lib\Bootstrap;
use Redseanet\Lib\Session\Segment;
use Redseanet\Lib\ViewModel\Template;
use Laminas\Db\Sql\Expression;
use Laminas\Db\Sql\Select;

class Customer extends Template
{
    use \Redseanet\Lib\Traits\Filter;

    public function getCustomer($exclude = [])
    {
        $segment = new Segment('customer');
        $customer_id = $segment->get('customer')["id"];
        if (!in_array($customer_id, $exclude)) {
            $exclude[] = $customer_id;
        }
        $query = $this->getQuery();
        $sub1 = new Select('forum_customer_like');
        $sub1->columns(['like_customer_id'])->where(['customer_id' => $customer_id]);
        $followSelect = new Select();
        $followSelect->from('forum_customer_like');
        $followSelect->columns(['followed' => new Expression('count(forum_customer_like.like_customer_id)')]);
        $followSelect->where('`forum_customer_like`.`customer_id`='.$customer_id.' and `forum_customer_like`.`like_customer_id`=`main_table`.`id`');
        
        $customers = new CustomerCollection();
        $customers->columns(['*', 'meFollowed' => $followSelect]);
        $customersSelect = $customers->getSelect();
        $customersSelect->where->notIn('id', $exclude);
        $customers->where(['status' => 1]);
        $keyword=addslashes($query['q']);
        if (isset($query['q']) && '' != $query['q']) {
            $customers->where('username like "%'.$keyword.'%"');
        } else {
            $customersSelect->where->notIn('id', $sub1);
        }
        $customers->order('created_at')->limit(50);
        //echo $customers->getSqlString(Bootstrap::getContainer()->get("dbAdapter")->getPlatform());exit;
        return $customers;
    }
}
