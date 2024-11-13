<?php

namespace Redseanet\Admin\ViewModel\Cms\Edit;

use Redseanet\Admin\ViewModel\Edit as PEdit;
use Redseanet\Cms\Source\Category;
use Redseanet\Lib\Session\Segment;
use Redseanet\Lib\Source\Language;
use Redseanet\Lib\Source\Store;
use Redseanet\Admin\Model\User;

class Page extends PEdit
{
    public function getSaveUrl()
    {
        return $this->getAdminUrl('cms_page/save/');
    }

    public function getDeleteUrl()
    {
        $model = $this->getVariable('model');
        if ($model && $model->getId()) {
            return $this->getAdminUrl('cms_page/delete/');
        }
        return false;
    }

    public function getTitle()
    {
        return $this->getQuery('id') ? 'Edit Page' : 'Add New Page';
    }

    protected function prepareElements($columns = [])
    {
        $model = $this->getVariable('model');
        $userArray = (new Segment('admin'))->get('user');
        $user = new User();
        $user->load($userArray['id']);
        $columns = [
            'id' => [
                'type' => 'hidden',
            ],
            'csrf' => [
                'type' => 'csrf'
            ],
            'store_id' => ($user->getStore() ? [
                'type' => 'hidden',
                'value' => $user->getStore()->getId()
            ] : [
                'type' => 'select',
                'options' => (new Store())->getSourceArray(),
                'label' => 'Store',
                'empty_string' => '(NULL)'
            ]),
            'title' => [
                'type' => 'text',
                'label' => 'Title',
                'required' => 'required'
            ],
            'category_id[]' => [
                'type' => 'selecttree',
                'options' => (new Category())->getSourceArrayTree(),
                'label' => 'Category',
                'attrs' => [
                    'multiple' => 'multiple'
                ],
                'value' => $model['category_id'] ?? ''
            ],
            'language_id[]' => [
                'type' => 'select',
                'label' => 'Language',
                'required' => 'required',
                'options' => (new Language())->getSourceArray(),
                'attrs' => [
                    'multiple' => 'multiple'
                ]
            ],
            'uri_key' => [
                'type' => 'text',
                'label' => 'Uri Key',
                'value' => empty($model['uri_key']) ? '' : rawurldecode($model['uri_key'])
            ],
            'status' => [
                'type' => 'select',
                'label' => 'Status',
                'options' => [
                    1 => 'Enabled',
                    0 => 'Disabled'
                ],
                'required' => 'required'
            ],
            'keywords' => [
                'type' => 'text',
                'label' => 'Meta Keywords'
            ],
            'description' => [
                'type' => 'text',
                'label' => 'Meta Description'
            ],
            'thumbnail' => [
                'type' => 'widget',
                'label' => 'Thumbnail',
                'widget' => 'upload'
            ],
            'image' => [
                'type' => 'widget',
                'label' => 'Image',
                'widget' => 'upload'
            ],
            'content' => [
                'type' => 'textarea',
                'label' => 'Content',
                'class' => 'htmleditor fullbar'
            ]
        ];
        return parent::prepareElements($columns);
    }

    public function getImage()
    {
        if ($this->getVariable('model')) {
            return $this->getVariable('model')->getImage();
        } else {
            return [];
        }
    }

    public function getThumbnail()
    {
        if ($this->getVariable('model')) {
            return $this->getVariable('model')->getThumbnail();
        } else {
            return [];
        }
    }

    public function getImageOnly()
    {
        return true;
    }
}
