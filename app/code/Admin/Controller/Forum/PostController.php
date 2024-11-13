<?php

namespace Redseanet\Admin\Controller\Forum;

use Exception;
use Redseanet\Forum\Model\Post as Model;
use Redseanet\Lib\Controller\AuthActionController;
use Redseanet\Forum\Model\Collection\Post as PostCollection;
use Redseanet\Forum\Model\Collection\Tags as TagsCollection;
use Redseanet\Lib\Model\Collection\Language;
use Redseanet\Forum\Model\Tags;

class PostController extends AuthActionController
{
    use \Redseanet\Lib\Traits\Filter;

    public function indexAction()
    {
        return $this->getLayout('admin_forum_post_list');
    }

    public function quickeditAction()
    {
        return $this->getLayout('admin_forum_post_list_quickedit');
    }

    public function topAction()
    {
        $result = ['error' => 0, 'message' => []];
        if ($id = $this->getRequest()->getQuery('id')) {
            try {
                $post = new Model();
                $post->load($id);
                if ($post['is_top']) {
                    $post->setData('is_top', 0)->save();
                    $result['message'][] = ['message' => $this->translate('The post has been canceled to stick successfully.'), 'level' => 'success'];
                } else {
                    $post->setData('is_top', 1)->save();
                    $result['message'][] = ['message' => $this->translate('The post has been stuck successfully.'), 'level' => 'success'];
                }
            } catch (Exception $e) {
                $result['error'] = 1;
                $result['message'][] = ['message' => $this->translate('An error detected while saving. Please check the log report or try again.'), 'level' => 'danger'];
            }
        }
        return $this->response($result, $this->getRequest()->getHeader('HTTP_REFERER')['HTTP_REFERER']);
    }

    public function hotAction()
    {
        $result = ['error' => 0, 'message' => []];
        if ($id = $this->getRequest()->getQuery('id')) {
            try {
                $post = new Model();
                $post->load($id);
                if ($post['is_hot']) {
                    $post->setData('is_hot', 0)->save();
                    $result['message'][] = ['message' => $this->translate('The post has been canceled the hot status successfully.'), 'level' => 'success'];
                } else {
                    $post->setData('is_hot', 1)->save();
                    $result['message'][] = ['message' => $this->translate('The post has been set as hot successfully.'), 'level' => 'success'];
                }
            } catch (Exception $e) {
                $result['error'] = 1;
                $result['message'][] = ['message' => $this->translate('An error detected while saving. Please check the log report or try again.'), 'level' => 'danger'];
            }
        }
        return $this->response($result, $this->getRequest()->getHeader('HTTP_REFERER')['HTTP_REFERER']);
    }

    public function closeAction()
    {
        $result = ['error' => 0, 'message' => []];
        if ($id = $this->getRequest()->getPost('id')) {
            try {
                foreach ((array) $id as $i) {
                    $post = new Model();
                    $post->setId($i)->setData('status', -1)->save();
                }
                $result['reload'] = 1;
                $result['message'][] = ['message' => $this->translate('The post has been closed successfully.'), 'level' => 'success'];
            } catch (Exception $e) {
                $result['error'] = 1;
                $result['message'][] = ['message' => $this->translate('An error detected while saving. Please check the log report or try again.'), 'level' => 'danger'];
            }
        }
        return $this->response($result, $this->getRequest()->getHeader('HTTP_REFERER')['HTTP_REFERER']);
    }

    public function deleteAction()
    {
        return $this->doDelete('Redseanet\\Forum\\Model\\Post', ':ADMIN/forum_post/');
    }

    public function editAction()
    {
        if ($id = $this->getRequest()->getQuery('id')) {
            $model = new Model();
            $model->load($id);
            if ($model->getId()) {
                $root = $this->getLayout('admin_forum_post_edit');
                $root->getChild('edit', true)->setVariable('model', $model);
                return $root;
            }
        }
        return $this->getRequest()->getHeader('HTTP_REFERER')['HTTP_REFERER'] ? $this->redirectReferer() : $this->notFoundAction();
    }

    public function saveAction()
    {
        $data = $this->getRequest()->getPost();
        return $this->doSave('Redseanet\\Forum\\Model\\Post', ':ADMIN/forum_post/?page=' . (!empty($data['page']) ? $data['page'] : 1), ['id', 'status', 'is_hot', 'is_top'], function ($model, $data) {
            if (isset($data['temp_content']) && $data['status'] == 1) {
                $model->setData([
                    'content' => $data['temp_content'],
                    'temp_content' => null
                ]);
            }
        });
    }

    public function likeListAction()
    {
        return $this->getLayout('admin_forum_post_like_list');
    }

    public function deleteLikeAction()
    {
        return $this->doDelete('Redseanet\\Forum\\Model\\Post\\Like', ':ADMIN/forum_post/likelist');
    }

    public function getPostListAction()
    {
        $data = $this->getRequest()->getQuery();
        $quaryData = $data;

        $posts = new PostCollection();
        $posts->join('customer_1_index', 'customer_1_index.id=forum_post.customer_id', ['username'], 'left');

        if (!empty($quaryData['title'])) {
            $posts->where("forum_post.title like '%" . $quaryData['title'] . "%'");
        }
        unset($quaryData['title']);
        if (!empty($quaryData['username'])) {
            $posts->where("customer_1_index.username like '%" . $quaryData['username'] . "%'");
        }
        unset($quaryData['username']);
        $posts->order('forum_post.created_at desc');
        $this->filter($posts, $quaryData);
        //echo $posts->getSqlString(Bootstrap::getContainer()->get("dbAdapter")->getPlatform());
        $root = $this->getLayout('admin_forum_post_list_modal');
        $root->getChild('main', true)->setVariable('posts', $posts)->setVariable('query', $data);
        return $root;
    }

    public function quicksaveAction()
    {
        if ($this->getRequest()->isPost()) {
            $data = $this->getRequest()->getPost();
            $required = ['id', 'value', 'column'];
            $result = $this->validateForm($data, $required);
            if (0 === $result['error']) {
                $model = new Model();
                $model->load($data['id']);
                $model->setData($data['column'], $data['value']);
                $model->save();
                if ($data['column'] == 'tags') {
                    $tags = array_filter(
                        array_map('strip_tags', explode(',', $data['value'])),
                        function ($tag) {
                            return strlen($tag) > 0 && strlen($tag) <= 200;
                        }
                    );
                    foreach ($tags as $tag) {
                        $tagsCollect = new TagsCollection();
                        $tagsCollect->withName();
                        $tagsCollect->where('forum_post_tags_language.name = "' . $tag . '"');
                        if (!count($tagsCollect) > 0) {
                            $tagName = [];
                            $languages = new Language();
                            $languages->where('status=1');
                            foreach ($languages as $lang) {
                                $tagName[$lang->id] = $tag;
                            }
                            $tagData = [
                                'sort_order' => 0,
                                'sys_recommended' => 0,
                                'name' => $tagName,
                            ];
                            $tagModel = new Tags();
                            $tagModel->setData($tagData);
                            $tagModel->save();
                        }
                    }
                }
                $result['message'][] = [
                    'message' => $this->translate('update successfully ID:' . $data['id']),
                    'level' => 'success'];
            }
        }
        return $this->response($result, ':ADMIN/forum_post/quickedit');
    }

    public function statisticsAction()
    {
        return $this->getLayout('admin_forum_post_statistics');
    }
}
