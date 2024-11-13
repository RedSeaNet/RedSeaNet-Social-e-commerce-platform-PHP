<?php

namespace Redseanet\Oauth\Controller;

use Redseanet\Customer\Model\Customer;
use Redseanet\Lib\Controller\ActionController;
use Redseanet\Lib\Session\Segment;

class ResponseController extends ActionController
{
    public function indexAction()
    {
        $data = $this->getRequest()->getQuery();
        $config = $this->getContainer()->get('config');
        $segment = new Segment('oauth');
        if (isset($data['state']) && $segment->get('state', false) === $data['state'] && !empty($data['code'])) {
            $client = new $config['oauth/' . $segment->get('server') . '/model']();
            list($token, $openId) = $client->access($data['code']);
            if ($customerId = $client->valid($openId)) {
                $segment = new Segment('customer');
                $customer = new Customer();
                $customer->load($customerId);
                $segment->set('hasLoggedIn', true)
                        ->set('customer', (clone $customer)->toArray());
                $segment->addMessage([['message' => $this->translate($config['theme/frontend/welcome_loggedin'], [$customer['username']], 'customer'), 'level' => 'success']]);
            } else {
                $segment->set('open_id', $openId);
                $segment = new Segment('customer');
                $segment->set('form_data', $client->getInfo($token, $openId))
                        ->addMessage([['message' => $this->translate('Please bind an account or register a new account.', [], 'oauth'), 'level' => 'info']]);
            }
            return $this->redirect('customer/account/login/');
        }
        return $this->notFoundAction();
    }
}
