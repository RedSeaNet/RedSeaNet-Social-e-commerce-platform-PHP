<?php

namespace Redseanet\Forum\Controller;

use Redseanet\Lib\Controller\ActionController;
use Redseanet\Customer\Model\Customer;

class SpaceController extends ActionController
{
    public function indexAction()
    {
        $user = new Customer();
        if ($this->getRequest()->isXmlHttpRequest() || (bool) $this->getRequest()->getQuery('is_json')) {
            $root = $this->getLayout('forum_user_space_ajax');
            $root->getChild('content', true)->setVariable('is_json', true);
        } else {
            $root = $this->getLayout('forum_user_space');
            $root->getChild('head')->setTitle('User Space');
            $root->getChild('main', true)->setVariable('user', $user);
        }
        return $root;
    }
}
