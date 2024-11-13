<?php

namespace Redseanet\Retailer\Controller;

use Exception;
use Redseanet\Retailer\Model\Retailer as Rmodel;
use Redseanet\Lib\Session\Segment;

/**
 * Retailer submenu products marketing controller
 *
 */
class MarketingController extends AuthActionController
{
    public function indexAction()
    {
        $segment = new Segment('customer');

        if ($customerId = $segment->get('customer')['id']) {
            $customer = new Cmodel();
            $customer->load($customerId);
            $root = $this->getLayout('retailer_store_settings');
            $root->getChild('main', true)->setVariable('customer', $customer);
            return $root;
        }
        return $root;
    }

    /**
     * releaseAction
     * Show release product view
     *
     * @access public
     * @return object
     */
    public function activityAction()
    {
        $root = $this->getLayout('retailer_activity');
        return $root;
    }

    /**
     * viewActivityAction
     * Show more infomation about activity
     *
     * @access public
     * @return object
     */
    public function viewActivityAction()
    {
        $root = $this->getLayout('retailer_activity_view');
        return $root;
    }

    /**
     * promotionAction
     * Manage the retailer's promotion
     *
     * @access public
     * @return object
     */
    public function promotionAction()
    {
        $root = $this->getLayout('retailer_promotion');
        return $root;
    }

    /**
     * viewPromotionAction
     * Show detail infomation about promotion
     *
     * @access public
     * @return object
     */
    public function viewPromotionAction()
    {
        $root = $this->getLayout('retailer_promotion_view');
        return $root;
    }

    /**
     * relationshipAction
     * Manage relation of memebers
     *
     * @access public
     * @return object
     */
    public function relationshipAction()
    {
        $root = $this->getLayout('retailer_members');
        return $root;
    }

    /**
     * memberDetailAction
     * Show member's detail infomation
     *
     * @access public
     * @return object
     */
    public function memberDetailAction()
    {
        $root = $this->getLayout('retailer_member_detail');
        return $root;
    }

    /**
     * recordAction
     * Show customer's transaction record'
     *
     * @access public
     * @return object
     */
    public function recordAction()
    {
        $root = $this->getLayout('retailer_transaction_record');
        return $root;
    }
}
