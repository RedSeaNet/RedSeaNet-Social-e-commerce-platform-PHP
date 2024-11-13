<?php

namespace Redseanet\Admin\ViewModel\Forum\Edit;

use Redseanet\Admin\ViewModel\Edit;
use Redseanet\Catalog\Model\Product;
use Redseanet\Lib\Bootstrap;
use Redseanet\Lib\Source\Language;

class Post extends Edit
{
    protected $hasUploadingFile = true;

    public function getSaveUrl()
    {
        return $this->getAdminUrl('forum_post/save/');
    }

    public function getDeleteUrl()
    {
        $model = $this->getVariable('model');
        if ($model && $model->getId()) {
            return $this->getAdminUrl('forum_post/delete/');
        }
        return false;
    }

    public function getTitle()
    {
        return 'Edit Post';
    }

    protected function prepareElements($columns = [])
    {
        $model = $this->getVariable('model');
        $columns = [
            'id' => [
                'type' => 'hidden',
            ],
            'page' => [
                'type' => 'hidden',
                'value' => (!empty($this->getQuery('page')) ? $this->getQuery('page') : 1)
            ],
            'csrf' => [
                'type' => 'csrf'
            ],
            'category_id' => [
                'type' => 'label',
                'label' => 'Category',
                'content' => $model->getCategory()['name'][Bootstrap::getLanguage()->getId()]
            ],
            'customer_id' => [
                'type' => 'link',
                'label' => 'Customer',
                'link' => ':ADMIN/customer_manage/edit/?id=' . $model['customer_id'],
                'content' => $model->getCustomer()['username']
            ],
            'product_id' => [
                'type' => 'link',
                'label' => 'Product',
                'link' => ':ADMIN/catalog_product/edit/?id=' . $model['product_id'],
                'content' => (new Product())->load($model['product_id'])['name']
            ],
            'language_id' => [
                'type' => 'label',
                'label' => 'Language',
                'content' => (new Language())->getSourceArray()[$model['language_id']]
            ],
            'like' => [
                'type' => 'label',
                'label' => 'Like/Dislike/Review',
                'content' => $model['like'] . '/' . $model['dislike'] . '/' . $model['reviews']
            ],
            'is_hot' => [
                'label' => 'Hot',
                'required' => 'required',
                'type' => 'select',
                'options' => [
                    'No', 'Yes'
                ]
            ],
            'is_top' => [
                'label' => 'Stick',
                'required' => 'required',
                'type' => 'select',
                'options' => [
                    'No', 'Yes'
                ]
            ],
            'status' => [
                'type' => 'select',
                'label' => 'Status',
                'options' => [
                    3 => 'Impeached',
                    2 => 'Edited',
                    1 => 'Approved',
                    0 => 'New',
                    -1 => 'Closed'
                ],
                'required' => 'required'
            ],
            'title' => [
                'type' => 'label',
                'label' => 'Title'
            ],
            'description' => [
                'type' => 'label',
                'label' => 'Description'
            ],
            'content' => [
                'type' => 'label',
                'label' => 'Content',
                'content' => $model['temp_content'] ?: $model['content']
            ],
            'created_at' => [
                'type' => 'label',
                'label' => 'Created at'
            ],
            'updated_at' => [
                'type' => 'label',
                'label' => 'Updated at'
            ]
        ];
        if ($model['status'] == 2) {
            $columns['temp_content'] = [
                'type' => 'hidden'
            ];
        }
        return parent::prepareElements($columns);
    }
}
