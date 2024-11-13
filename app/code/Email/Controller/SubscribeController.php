<?php

namespace Redseanet\Email\Controller;

use Exception;
use Redseanet\Email\Model\Subscriber as Model;
use Redseanet\Lib\Bootstrap;
use Redseanet\Lib\Controller\ActionController;

class SubscribeController extends ActionController
{
    public function indexAction()
    {
        $result = ['error' => 0, 'message' => []];
        if ($this->getRequest()->isPost()) {
            $data = $this->getRequest()->getPost();
            $result = $this->validateForm($data);
            if (!$result['error']) {
                try {
                    $model = new Model();
                    $model->load($data['email'], 'email');
                    $model->setData([
                        'email' => $data['email'],
                        'language_id' => Bootstrap::getLanguage()->getId(),
                        'status' => 1
                    ])->save();
                    $result['message'][] = ['message' => $this->translate('Thank you for your subscription.'), 'level' => 'success'];
                } catch (Exception $e) {
                    $this->getContainer()->get('log')->logException($e);
                    $result['error'] = 1;
                    $result['message'][] = ['message' => $this->translate('Subscribe failed. Please try again later.'), 'level' => 'danger'];
                }
            }
        }
        return $this->response($result, $this->getRequest()->getHeader('HTTP_REFERER')['HTTP_REFERER']);
    }
}
