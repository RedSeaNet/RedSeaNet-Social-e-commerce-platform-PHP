<?php

namespace Redseanet\Forum\Controller;

use Redseanet\Lib\Controller\ActionController;
use Redseanet\Lib\Session\Segment;
use Redseanet\Forum\Model\CustomerLike;

class AccountController extends \Redseanet\Customer\Controller\AuthActionController
{
    use \Redseanet\Lib\Traits\DB;
    use \Redseanet\Notifications\Traits\NotificationsMethod;

    protected $allowedAction = ['like'];

    public function __construct()
    {
    }

    public function doDispatch($method = 'notFoundAction')
    {
        $action = strtolower(substr($method, 0, -6));
        $session = new Segment('customer');
        if (!in_array($action, $this->allowedAction) && !$session->get('hasLoggedIn', false)) {
            return $this->redirect('customer/account/login/');
        } elseif (in_array($action, $this->allowedAction) && $session->get('hasLoggedIn', false)) {
            if ($url = $this->getRequest()->getQuery('success_url')) {
                $data['success_url'] = urldecode($url);
                $customer = $session->get('customer');
                $data['data'] = ['id' => $customer['id'], 'username' => $customer['username'], 'email' => $customer['email']];
                if ($this->useSso($data)) {
                    return $this->redirect($data['success_url']);
                }
            }
        }
        return ActionController::doDispatch($method);
    }

    public function indexAction()
    {
        if ($this->getRequest()->isXmlHttpRequest() || $this->getRequest()->getQuery('is_json')) {
            $root = $this->getLayout('forum_account_dashboard_ajax');
            //$root->getChild('main', true)->setVariable('category', $this->getOption('category'));
            //echo '--ajax---:'. $this->getOption('category')->getId();
            $root->getChild('content', true)->setVariable('category', $this->getOption('category'));
        } else {
            $root = $this->getLayout('forum_account_dashboard' . ($this->getRequest()->isXmlHttpRequest() ? '_ajax' : ''));
            $root->getChild('main', true)->setVariable('category', $this->getOption('category'));
        }

        return $root;
    }

    public function likeAction()
    {
        $segment = new Segment('customer');
        if ($this->getRequest()->isXmlHttpRequest() && $this->getRequest()->isPost()) {
            $customer = $segment->get('customer');
            $customer_id = $customer['id'];
            $data = $this->getRequest()->getPost();
            $result = $this->validateForm($data, ['id']);
            if ($segment->get('hasLoggedIn') && $result['error'] === 0) {
                try {
                    $model = new CustomerLike();
                    ['data' => $model->setId($customer_id)->like($data['id'])];
                    $result['reload'] = 1;
                    $result['message'][] = ['message' => $this->translate('You have succeeded in paying attention to this user.'), 'level' => 'success'];
                    $notificationsData = ['params' => json_encode(['customerid' => $customer_id, 'urlkey' => 'customerid']), 'area' => 'forum', 'level' => 'success', 'is_app' => 1, 'status' => 0, 'customer_id' => $data['id'], 'sender_id' => $customer_id, 'type' => 0];
                    $notificationsData['title'] = $this->translate('%s just followed you', [$customer['username']]) . '.';
                    $notificationsData['content'] = $this->translate('%s just followed you', [$customer['username']]) . '.';
                    $this->addNotifications($notificationsData);
                } catch (Exception $e) {
                    $this->getContainer()->get('log')->logException($e);
                    $result['error'] = 1;
                    $result['message'][] = ['message' => $this->translate('An error detected. Please try again later.'), 'level' => 'danger'];
                }
            }
            return $this->response($result ?? ['error' => 0, 'message' => []], 'forum/post/', 'forum');
        } else {
            if (!$segment->get('hasLoggedIn')) {
                $get_query = $this->getRequest()->getQuery();
                if (isset($get_query['id']) && !empty($get_query['id'])) {
                    $form_segment = new Segment('forum');
                    $form_segment->set('forum_like_user_id', $get_query['id']);
                }
                if (isset($get_query['success_url']) && !empty($get_query['success_url'])) {
                    return $this->redirect('customer/account/login/?success_url=' . $get_query['success_url']);
                }
            }
        }
        return $this->notFoundAction();
    }
}
