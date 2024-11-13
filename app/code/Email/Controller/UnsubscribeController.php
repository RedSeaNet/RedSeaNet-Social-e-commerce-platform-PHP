<?php

namespace Redseanet\Email\Controller;

use Exception;
use Redseanet\Email\Model\Subscriber as Model;
use Redseanet\Lib\Bootstrap;
use Redseanet\Lib\Controller\ActionController;

class UnsubscribeController extends ActionController
{
    public function indexAction()
    {
        $data = $this->getRequest()->getQuery();
        $result = ['error' => 0, 'message' => []];
        if (isset($data['id']) && isset($data['code'])) {
            try {
                $model = new Model();
                $model->load($data['id']);
                if ($model->getId() && $model['code'] == $data['code']) {
                    $model->unsubscribe();
                    $result['message'][] = ['message' => $this->translate('Unsubscribe successfully.'), 'level' => 'success'];
                } else {
                    $result['message'][] = ['message' => $this->translate('Unsubscribe failed. Please try again later.'), 'level' => 'danger'];
                }
            } catch (Exception $e) {
                $this->getContainer()->get('log')->logException($e);
                $result['error'] = 1;
                $result['message'][] = ['message' => $this->translate('Unsubscribe failed. Please try again later.'), 'level' => 'danger'];
            }
        }
        return $this->response($result, $this->getBaseUrl());
    }
}
