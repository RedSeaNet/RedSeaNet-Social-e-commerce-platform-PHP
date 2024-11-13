<?php

namespace Redseanet\Admin\Controller;

use Redseanet\Customer\Model\Customer;
use Redseanet\Lib\Controller\AuthActionController;
use Redseanet\Lib\ViewModel\Template;
use Laminas\Db\Sql\Expression;
use Laminas\Stdlib\SplQueue;
use Redseanet\Lib\Session\Segment;
use Redseanet\Lib\Bootstrap;
use Redseanet\Oauth\Model\Client;

class OauthController extends AuthActionController
{
    use \Redseanet\Lib\Traits\DataCache;

    public function indexAction()
    {
        $root = $this->getLayout('admin_oauth_list');
        return $root;
    }

    public function deleteAction()
    {
        $data = $this->getRequest()->getPost();
        $result = $this->validateForm($data, ['customer_id', 'oauth_server', 'open_id']);
        if ($result['error'] === 0) {
            $client = new Client();
            $client->doDelete(['customer_id' => $data['customer_id'], 'oauth_server' => $data['oauth_server'], 'open_id' => $data['open_id']]);
            $this->flushList('oauth_client');
            $result['message'][] = ['message' => $this->translate('item have been deleted successfully.'), 'level' => 'success'];
            $result['reload'] = 1;
        }
        return $this->response($result ?? ['error' => 0, 'message' => []], $this->getRequest()->getHeader('HTTP_REFERER')['HTTP_REFERER']);
    }
}
