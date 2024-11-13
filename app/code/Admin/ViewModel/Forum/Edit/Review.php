<?php

namespace Redseanet\Admin\ViewModel\Forum\Edit;

use Redseanet\Admin\ViewModel\Edit;

class Review extends Edit
{
    protected $hasUploadingFile = true;

    public function getSaveUrl()
    {
        return $this->getAdminUrl('forum_review/save/');
    }

    public function getDeleteUrl()
    {
        $model = $this->getVariable('model');
        if ($model && $model->getId()) {
            return $this->getAdminUrl('forum_review/delete/');
        }
        return false;
    }

    public function getTitle()
    {
        return 'Edit Review';
    }

    protected function prepareElements($columns = [])
    {
        $model = $this->getVariable('model');
        $columns = [
            'id' => [
                'type' => 'hidden',
            ],
            'csrf' => [
                'type' => 'csrf'
            ],
            'post_id' => [
                'type' => 'link',
                'label' => 'Post',
                'link' => ':ADMIN/forum_post/edit/?id=' . $model['post_id'],
                'content' => $model->getPost()['title']
            ],
            'customer_id' => [
                'type' => 'link',
                'label' => 'Customer',
                'link' => ':ADMIN/customer_manage/edit/?id=' . $model['customer_id'],
                'content' => $model->getCustomer()['username']
            ],
            'like' => [
                'type' => 'label',
                'label' => 'Like/Dislike',
                'content' => $model['like'] . '/' . $model['dislike']
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
            'subject' => [
                'type' => 'label',
                'label' => 'Subject'
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
