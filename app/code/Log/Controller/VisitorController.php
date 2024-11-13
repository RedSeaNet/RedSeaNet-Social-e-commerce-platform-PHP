<?php

namespace Redseanet\Log\Controller;

use Error;
use Exception;
use Redseanet\Lib\Controller\ActionController;
use Redseanet\Log\Model\Visitor as Model;

class VisitorController extends ActionController
{
    use \Redseanet\Log\Traits\Ip;

    public function indexAction()
    {
        try {
            $parts = explode('-', base64_decode($this->getOption('file')));
            if (count($parts) === 3) {
                list($customerId, $storeId, $productId) = $parts;
                $postId = null;
            } else {
                list($customerId, $storeId, $productId, $postId) = $parts;
            }
            $request = $this->getRequest();
            $model = new Model();
            $model->setData([
                'customer_id' => $customerId === 'n' ? null : $customerId,
                'store_id' => $storeId === 'n' ? null : $storeId,
                'product_id' => $productId === 'n' ? null : $productId,
                'post_id' => $postId,
                'session_id' => $this->getContainer()->get('session')['id'],
                'remote_addr' => $this->getRealIp(),
                'http_referer' => $request->getHeader('HTTP_REFERER'),
                'http_user_agent' => $request->getHeader('HTTP_USER_AGENT'),
                'http_accept_charset' => $request->getHeader('HTTP_ACCEPT_CHARSET'),
                'http_accept_language' => $request->getHeader('HTTP_ACCEPT_LANGUAGE')
            ])->save();
        } catch (Error $e) {
        } catch (Exception $e) {
        }
        return $this->getResponse()->withHeader('Content-Type', 'application/javascript');
    }
}
