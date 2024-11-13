<?php

namespace Redseanet\Debug\Controller;

use Redseanet\Lib\Bootstrap;
use Redseanet\Lib\Controller\ActionController;

class SqlController extends ActionController
{
    public function explainAction()
    {
        if (!Bootstrap::isDeveloperMode()) {
            return $this->notFoundAction();
        }
        $sql = base64_decode($this->getRequest()->getPost('sql'));
        $adapter = $this->getContainer()->get('dbAdapter');
        return $adapter->query('EXPLAIN ' . $sql, 'execute')->toArray();
    }
}
