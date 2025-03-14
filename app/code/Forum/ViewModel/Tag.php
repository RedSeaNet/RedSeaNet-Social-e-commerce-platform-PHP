<?php

namespace Redseanet\Forum\ViewModel;

use Redseanet\Forum\Model\Collection\Tags as TagsCollection;
use Redseanet\Lib\Session\Segment;
use Redseanet\Lib\ViewModel\Template;
use Redseanet\Customer\Model\Collection\CustomerInGroup;
use Redseanet\Lib\Bootstrap;
use Redseanet\Forum\Model\Collection\Category as CategoryCollection;

class Tag extends Template {

    use \Redseanet\Lib\Traits\Filter;

    protected $posts = null;
    protected $bannedMember = ['query', 'posts'];
    protected $current = null;

    public function getLanguageId() {
        return Bootstrap::getLanguage()->getId();
    }

    public function getSysTags() {
        $tags = new TagsCollection();
        $tags->where(['sys_recommended' => 1]);
        $tags->order('sort_order desc');
        $tags->withName();
        $tags->load(true, true);
        return $tags;
    }

    public function getCustomerGroups($groupId) {
        $segment = new Segment('customer');
        $customer = $segment->get('customer');
        if ($segment->get('hasLoggedIn', false)) {
            $currentTime = date('Y-m-d H:i:s');
            $customerId = $customer->getId();
            $inGroup = new CustomerInGroup();
            $inGroup->where("customer_in_group.group_id=" . $groupId . " and customer_in_group.start_time is not null and customer_in_group.end_time is not null and customer_in_group.customer_id=" . $customerId)->where->lessThanOrEqualTo("customer_in_group.start_time", $currentTime)->greaterThanOrEqualTo("customer_in_group.end_time", $currentTime);
            //echo $inGroup->getSqlString(Bootstrap::getContainer()->get("dbAdapter")->getPlatform());exit;
            $inGroup->order("end_time desc");
            return $inGroup;
        } else {
            return [];
        }
    }

    public function getCategories() {
        $categories = new CategoryCollection();
        $categories->where(['status' => 1]);
        $categories->where(['parent_id' => 1]);
        $categories->order('sort_order desc');
        $categories->withName();

        return $categories;
    }

}
