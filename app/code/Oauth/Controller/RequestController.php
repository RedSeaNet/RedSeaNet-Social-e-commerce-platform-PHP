<?php

namespace Redseanet\Oauth\Controller;

use Redseanet\Lib\Controller\ActionController;
use Redseanet\Lib\Session\Segment;
use Laminas\Math\Rand;

class RequestController extends ActionController
{
    public function indexAction()
    {
        if ($type = $this->getRequest()->getQuery('client')) {
            $name = $this->getContainer()->get('config')['oauth/' . $type . '/model'];
            $model = new $name();
            $segment = new Segment('oauth');
            $state = Rand::getString(8, '1234567890qwertyuiopasdfghjklzxcvbnmQWERTYUIOPASDFGHJKLZXCVBNM');
            $segment->set('server', $model::SERVER_NAME)
                    ->set('state', $state);
            return $this->redirect($model->redirect($state));
        }
        return $this->notFoundAction();
    }
}
