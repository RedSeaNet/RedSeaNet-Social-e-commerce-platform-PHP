<?php

namespace Redseanet\Admin\Controller\Catalog;

use Redseanet\Catalog\Model\Product as Model;
use Redseanet\Lib\Bootstrap;
use Redseanet\Lib\Controller\AuthActionController;
use Redseanet\Lib\Session\Segment;
use Zend\Math\Rand;
use Redseanet\Catalog\Model\PostRelation;
use Redseanet\Catalog\Model\Collection\PostRelation as postRelationCollection;

class ForumController extends AuthActionController
{
    use \Redseanet\Lib\Traits\DataCache;

    public function indexAction()
    {
        $root = $this->getLayout('admin_catalog_product_post_list');
        return $root;
    }

    public function editAction()
    {
        if ($id = $this->getRequest()->getQuery('id')) {
            $model = new Model();
            $model->load($id);
            if ($model->getId()) {
                $root = $this->getLayout('admin_catalog_product_post_edit');
                $collection = new postRelationCollection();
                $collection->join('forum_post', 'forum_post.id=forum_product_relation.post_id', ['title'], 'left');
                $collection->where(['forum_product_relation.product_id' => $id]);
                $root->getChild('edit', true)->setVariable('model', $model)->setVariable('relatePosts', $collection);
                return $root;
            }
        }
        return $this->getRequest()->getHeader('HTTP_REFERER') ? $this->redirectReferer() : $this->notFoundAction();
    }

    public function saveAction()
    {
        $data = $this->getRequest()->getPost();
        if (!empty($data['id'])) {
            if (!empty($data['choosenposts']) && count($data['choosenposts']) > 0) {
                $collection = new postRelationCollection();
                $collection->where(['product_id' => $data['id']]);
                $_databaseArray = [];
                foreach ($collection as $item) {
                    if (in_array($item['post_id'], $data['choosenposts'])) {
                        $_databaseArray[] = $item['post_id'];
                    } else {
                        $item->remove();
                    }
                }
                $_needAddArray = array_values(array_diff($data['choosenposts'], $_databaseArray));
                for ($p = 0; $p < count($_needAddArray); $p++) {
                    $relatePost = new PostRelation();
                    $relatePost->setData(['product_id' => $data['id'], 'post_id' => $_needAddArray[$p]]);
                    $relatePost->save();
                }
            } else {
                $collection = new postRelationCollection();
                $collection->where(['product_id' => $data['id']]);
                if (count($collection) > 0) {
                    foreach ($collection as $item) {
                        $item->remove();
                    }
                }
            }
            $result = ['error' => 0, 'message' => []];
            $result['message'][] = ['message' => $this->translate('add product relate post successfully.'), 'level' => 'success'];
            return $this->response($result, ':ADMIN/catalog_forum/');
        }
        return $this->getRequest()->getHeader('HTTP_REFERER') ? $this->redirectReferer() : $this->notFoundAction();
    }

    public function deleteAction()
    {
        return $this->doDelete('Redseanet\\Catalog\\Model\\PostRelation', ':ADMIN/catalog_forum/');
    }
}
