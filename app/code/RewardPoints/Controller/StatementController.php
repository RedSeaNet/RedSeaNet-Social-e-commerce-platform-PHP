<?php

namespace Redseanet\RewardPoints\Controller;

use Redseanet\Customer\Controller\AuthActionController;

class StatementController extends AuthActionController
{
    public function indexAction()
    {
        return $this->getLayout('rewardpoints_statement');
    }
}
