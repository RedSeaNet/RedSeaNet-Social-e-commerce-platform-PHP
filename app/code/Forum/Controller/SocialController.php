<?php

namespace Redseanet\Forum\Controller;

use Redseanet\Customer\Controller\AuthActionController;
use Redseanet\Forum\Model\Post\Like;
use Redseanet\Forum\Model\Post\Favorite;
use Redseanet\Lib\Session\Segment;

class SocialController extends AuthActionController
{
    use \Redseanet\Lib\Traits\DB;

    use \Redseanet\Lib\Traits\DataCache;

    public function replyAction()
    {
        return $this->getLayout('forum_post_reply');
    }

    public function likedAction()
    {
        return $this->getLayout('forum_post_liked');
    }

    public function followingAction()
    {
        return $this->getLayout('forum_post_following');
    }

    public function dynamicAction()
    {
        return $this->getLayout('forum_post_dynamic');
    }

    public function fansAction()
    {
        return $this->getLayout('forum_post_fans');
    }

    public function beLikeAction()
    {
        return $this->getLayout('forum_post_belike');
    }

    public function beCollectedAction()
    {
        return $this->getLayout('forum_post_becollected');
    }

    public function likedRemoveAction()
    {
        if ($this->getRequest()->isDelete()) {
            $data = $this->getRequest()->getPost();
            $result = $this->validateForm($data, ['id']);
            if ($result['error'] === 0) {
                try {
                    $model = new Like();
                    $model->setId($data['id'])->remove();
                    $result['removeLine'] = $data['id'];
                } catch (Exception $e) {
                    $result['error'] = 1;
                }
            }
        }
        return $this->response($result ?? ['error' => 0, 'message' => []], $this->getRequest()->getHeader('HTTP_REFERER')['HTTP_REFERER'], 'forum');
    }

    public function followRemoveAction()
    {
        if ($this->getRequest()->isDelete()) {
            $data = $this->getRequest()->getPost();
            $result = $this->validateForm($data, ['id']);
            if ($result['error'] === 0) {
                try {
                    $model = new \Redseanet\Forum\Model\CustomerLike();
                    $model->setId($data['id'])->remove();
                    $result['removeLine'] = $data['id'];
                } catch (Exception $e) {
                    $result['error'] = 1;
                }
            }
        }
        return $this->response($result ?? ['error' => 0, 'message' => []], $this->getRequest()->getHeader('HTTP_REFERER')['HTTP_REFERER'], 'forum');
    }

    public function fansRemoveAction()
    {
        if ($this->getRequest()->isDelete()) {
            $data = $this->getRequest()->getPost();
            $result = $this->validateForm($data, ['id']);
            if ($result['error'] === 0) {
                try {
                    $model = new \Redseanet\Forum\Model\CustomerLike();
                    $model->setId($data['id'])->remove();
                    $result['removeLine'] = $data['id'];
                } catch (Exception $e) {
                    $result['error'] = 1;
                }
            }
        }
        return $this->response($result ?? ['error' => 0, 'message' => []], $this->getRequest()->getHeader('HTTP_REFERER')['HTTP_REFERER'], 'forum');
    }

    public function belikeRemoveAction()
    {
        if ($this->getRequest()->isDelete()) {
            $data = $this->getRequest()->getPost();
            $result = $this->validateForm($data, ['id']);
            if ($result['error'] === 0) {
                try {
                    $model = new Like();
                    $model->setId($data['id'])->remove();
                    $result['removeLine'] = $data['id'];
                } catch (Exception $e) {
                    $result['error'] = 1;
                }
            }
        }
        return $this->response($result ?? ['error' => 0, 'message' => []], $this->getRequest()->getHeader('HTTP_REFERER')['HTTP_REFERER'], 'forum');
    }

    public function favoritedRemoveAction()
    {
        $segment = new Segment('customer');
        $customer_id = $segment->get('customer')['id'];
        if ($this->getRequest()->isDelete()) {
            $data = $this->getRequest()->getPost();
            $result = $this->validateForm($data, ['id']);
            if ($result['error'] === 0) {
                try {
                    $model = new Favorite();
                    $model->load($data['id']);
                    if ($model['customer_id'] == $customer_id) {
                        $model->remove();
                        $this->flushList('forum_post');
                    }
                    $result['removeLine'] = $data['id'];
                } catch (Exception $e) {
                    $result['error'] = 1;
                }
            }
        }
        return $this->response($result ?? ['error' => 0, 'message' => []], $this->getRequest()->getHeader('HTTP_REFERER')['HTTP_REFERER'], 'forum');
    }

    public function favoritedListAction()
    {
        if ($this->getRequest()->isXmlHttpRequest() || $this->getRequest()->getQuery('is_json')) {
            $root = $this->getLayout('forum_post_favorited_ajax');
            $root->getChild('content', true)->setVariable('is_json', true);
        } else {
            $root = $this->getLayout('forum_post_favorited');
        }
        return $root;
    }
}
