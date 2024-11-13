<?php

namespace Redseanet\Admin\Controller\Cms;

use Redseanet\Cms\Model\Page as Model;
use Redseanet\Lib\Controller\AuthActionController;
use Redseanet\Lib\Session\Segment;
use Laminas\Math\Rand;
use Redseanet\Admin\Model\User;

class PageController extends AuthActionController
{
    public function indexAction()
    {
        $root = $this->getLayout('admin_cms_page_list');
        return $root;
    }

    public function editAction()
    {
        $root = $this->getLayout('admin_cms_page_edit');
        $model = new Model();
        if ($id = $this->getRequest()->getQuery('id')) {
            $model->load($id);
            $root->getChild('edit', true)->setVariable('model', $model);
            $root->getChild('head')->setTitle('Edit Page / CMS');
        } else {
            $model->setData('content', '<div class="container"></div>');
            $root->getChild('edit', true)->setVariable('model', $model);
            $root->getChild('head')->setTitle('Add New Page / CMS');
        }
        return $root;
    }

    public function deleteAction()
    {
        return $this->doDelete('\\Redseanet\\Cms\\Model\\Page', ':ADMIN/cms_page/');
    }

    public function saveAction()
    {
        $tmpkey = Rand::getString(200);
        $response = $this->doSave('\\Redseanet\\Cms\\Model\\Page', ':ADMIN/cms_page/', ['language_id', 'uri_key', 'title'], function ($model, $data) use ($tmpkey) {
            $userArray = (new Segment('admin'))->get('user');
            $user = new User();
            $user->load($userArray['id']);
            if ($user->getStore()) {
                if ($model->getId() && $model->offsetGet('store_id') != $user->getStore()->getId()) {
                    throw new \Exception('Not allowed to save.');
                }
                $model->setData('store_id', $user->getStore()->getId());
            } elseif (empty($data['store_id'])) {
                $model->setData('store_id', null);
            }
            if (empty($data['uri_key'])) {
                $model->setData('uri_key', $tmpkey);
            }
        }, false, function ($model) use ($tmpkey) {
            if ($model->offsetGet('uri_key') === $tmpkey) {
                $model->setData('uri_key', $model->getId())->save();
            }
            $this->reindex($model);
        });
        return $response;
    }

    protected function reindex($model)
    {
        $values = [];
        $categories = $model->getCategories();
        $indexer = $this->getContainer()->get('indexer');
        if (count($categories)) {
            foreach ($categories as $category) {
                if ($category['uri_key']) {
                    $path = [$category['uri_key'], $model['uri_key']];
                    $tmp = $category;
                    while (($tmp = $tmp->getParentCategory()) && $tmp['uri_key']) {
                        array_unshift($path, $tmp['uri_key']);
                    }
                    $values[] = ['page_id' => $model->getId(), 'category_id' => $category['id'], 'path' => implode('/', $path)];
                }
            }
        } else {
            $values[] = ['page_id' => $model->getId(), 'category_id' => null, 'path' => $model['uri_key']];
        }
        foreach ((array) $model['language_id'] as $languageId) {
            $indexer->replace('cms_url', $languageId, $values, ['page_id' => $model->getId()]);
        }
    }
}
