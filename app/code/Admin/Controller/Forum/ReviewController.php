<?php

namespace Redseanet\Admin\Controller\Forum;

use Exception;
use Redseanet\Forum\Model\Post\Review as Model;
use Redseanet\Lib\Controller\AuthActionController;

class ReviewController extends AuthActionController
{
    public function indexAction()
    {
        $root = $this->getLayout('admin_forum_review_list');
        return $root;
    }

    public function closeAction()
    {
        $result = ['error' => 0, 'message' => []];
        if ($id = $this->getRequest()->getPost('id')) {
            try {
                foreach ((array) $id as $i) {
                    $review = new Model();
                    $review->setId($i)->setData('status', -1)->save();
                }
                $result['reload'] = 1;
                $result['message'][] = ['message' => $this->translate('The review has been closed successfully.'), 'level' => 'success'];
            } catch (Exception $e) {
                $result['error'] = 1;
                $result['message'][] = ['message' => $this->translate('An error detected while saving. Please check the log report or try again.'), 'level' => 'danger'];
            }
        }
        return $this->response($result, $this->getRequest()->getHeader('HTTP_REFERER')['HTTP_REFERER']);
    }

    public function deleteAction()
    {
        return $this->doDelete('Redseanet\\Forum\\Model\\Post\\Review', ':ADMIN/forum_review/');
    }

    public function editAction()
    {
        if ($id = $this->getRequest()->getQuery('id')) {
            $model = new Model();
            $model->load($id);
            if ($model->getId()) {
                $root = $this->getLayout('admin_forum_review_edit');
                $root->getChild('edit', true)->setVariable('model', $model);
                return $root;
            }
        }
        return $this->getRequest()->getHeader('HTTP_REFERER')['HTTP_REFERER'] ? $this->redirectReferer() : $this->notFoundAction();
    }

    public function saveAction()
    {
        return $this->doSave('Redseanet\\Forum\\Model\\Post\\Review', ':ADMIN/forum_review/', ['id', 'status'], function ($model, $data) {
            if (isset($data['temp_content']) && $data['status'] == 1) {
                $model->setData([
                    'content' => $data['temp_content'],
                    'temp_content' => null
                ]);
            }
        });
    }
}
