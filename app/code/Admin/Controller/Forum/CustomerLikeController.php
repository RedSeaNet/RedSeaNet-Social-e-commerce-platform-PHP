<?php

namespace Redseanet\Admin\Controller\Forum;

use Exception;
use Redseanet\Forum\Model\CustomerLike as Model;
use Redseanet\Lib\Controller\AuthActionController;

class CustomerLikeController extends AuthActionController
{
    public function indexAction()
    {
        $root = $this->getLayout('admin_forum_customerlike_list');
        return $root;
    }

    public function deleteAction()
    {
        return $this->doDelete('Redseanet\\Forum\\Model\\CustomerLike', ':ADMIN/forum_customerlike/');
    }

    public function saveAction()
    {
        return $this->doSave('Redseanet\\Forum\\Model\CustomerLike', ':ADMIN/forum_customerlike/', ['id', 'status'], function ($model, $data) {
            if (isset($data['temp_content']) && $data['status'] == 1) {
                $model->setData([
                    'content' => $data['temp_content'],
                    'temp_content' => null
                ]);
            }
        });
    }
}
