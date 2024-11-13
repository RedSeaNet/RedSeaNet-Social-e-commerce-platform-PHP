<?php

namespace Redseanet\Admin\Controller\Forum;

use Exception;
use Redseanet\Forum\Model\Poll;
use Redseanet\Forum\Model\Post;
use Redseanet\Forum\Model\Collection\Poll as PollCollection;
use Redseanet\Lib\Controller\AuthActionController;

class PollController extends AuthActionController
{
    public function indexAction()
    {
        return $this->getLayout('admin_forum_poll_list');
    }

    public function deleteAction()
    {
        return $this->doDelete('Redseanet\\Forum\\Model\\Poll', ':ADMIN/forum_poll/');
    }

    public function editAction()
    {
        if ($id = $this->getRequest()->getQuery('id')) {
            $model = new Poll();
            $model->load($id);
            if ($model->getId()) {
                $root = $this->getLayout('admin_forum_poll_edit');
                $root->getChild('edit', true)->setVariable('model', $model);
                return $root;
            }
        }
        return $this->getRequest()->getHeader('HTTP_REFERER')['HTTP_REFERER'] ? $this->redirectReferer() : $this->notFoundAction();
    }

    public function voterAction()
    {
        return $this->getLayout('admin_forum_poll_voter');
    }
}
