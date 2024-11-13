<?php

namespace Redseanet\Admin\Controller\Customer;

use Redseanet\Customer\Model\CreditCard as Model;
use Redseanet\Lib\Controller\AuthActionController;

class CreditcardController extends AuthActionController
{
    public function indexAction()
    {
        $query = $this->getRequest()->getQuery();
        if (isset($query['id'])) {
            $model = new Model();
            $model->load($query['id']);
            if ($model->getId()) {
                $root = $this->getLayout('admin_customer_creditcard');
                $root->getChild('main', true)->setVariable('model', $model);
                return $root;
            }
        }
        return $this->notFoundAction();
    }
}
