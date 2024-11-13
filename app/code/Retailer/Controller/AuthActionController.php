<?php

namespace Redseanet\Retailer\Controller;

use Redseanet\Lib\Controller\ActionController;
use Redseanet\Lib\Session\Segment;
use Redseanet\Retailer\Model\Application;
use Redseanet\Retailer\Model\Retailer;
use Redseanet\Retailer\Model\Manager;
use Redseanet\Retailer\Model\Collection\Manager as managerCollection;

abstract class AuthActionController extends ActionController
{
    use \Redseanet\Lib\Traits\DB;

    protected $retailer = null;

    public function dispatch($request = null, $routeMatch = null)
    {
        $options = $routeMatch->getOptions();
        $action = isset($options['action']) ? strtolower($options['action']) : 'index';
        $session = new Segment('customer');
        if (!$session->get('hasLoggedIn')) {
            return $this->redirect('customer/account/login/');
        } else {
            $manager = new managerCollection();
            $manager->where(['customer_id' => intval($session->get('customer')['id'])]);
            if (count($manager) == 0) {
                $model = new Application();
                $model->load($session->get('customer')['id']);
                if (in_array($action, ['apply', 'applypost', 'reapply', 'processing'])) {
                    if ($model->offsetGet('status')) {
                        return $this->redirect('retailer/store/setting/');
                    } elseif ($action === 'apply' && $model->getId()) {
                        return $this->redirect('retailer/account/processing/');
                    }
                } elseif (!$model->getId()) {
                    return $this->redirect('retailer/account/apply/');
                } elseif (!$model->offsetGet('status')) {
                    return $this->redirect('retailer/account/processing/');
                }
            }
        }
        return parent::dispatch($request, $routeMatch);
    }

    protected function getRetailer()
    {
        if (is_null($this->retailer)) {
            $session = new Segment('customer');
            $this->retailer = new Retailer();
            $customer = $session->get('customer');
            $this->retailer->load($customer['id'], 'customer_id');
        }
        return $this->retailer;
    }
}
