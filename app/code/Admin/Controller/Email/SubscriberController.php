<?php

namespace Redseanet\Admin\Controller\Email;

use Redseanet\Lib\Controller\AuthActionController;
use Redseanet\Email\Model\Subscriber as Model;

class SubscriberController extends AuthActionController
{
    public function indexAction()
    {
        return $this->getLayout('admin_email_subscriber_list');
    }

    public function deleteAction()
    {
        return $this->doDelete('\\Redseanet\\Email\\Model\\Subscriber', ':ADMIN/email_subscriber/');
    }
}
