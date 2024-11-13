<?php

namespace Redseanet\Admin\ViewModel\Banner\Edit;

use Redseanet\Admin\ViewModel\Edit as PEdit;
use Redseanet\Lib\Session\Segment;
use Redseanet\Lib\Source\Language;
use Redseanet\Lib\Source\Store;
use Redseanet\Admin\Model\User;

class Banner extends PEdit
{
    public function getSaveUrl()
    {
        return $this->getAdminUrl('banner/save/');
    }

    public function getDeleteUrl()
    {
        $model = $this->getVariable('model');
        if ($model && $model->getId()) {
            return $this->getAdminUrl('banner/delete/');
        }
        return false;
    }

    public function getTitle()
    {
        return $this->getQuery('id') ? 'Edit Banner' : 'Add New Banner';
    }

    protected function prepareElements($columns = [])
    {
        $languages = (new Language())->getSourceArray();
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
            'code' => [
                'type' => 'text',
                'label' => 'Code',
                'required' => 'required'
            ],
            'url' => [
                'type' => 'text',
                'label' => 'Url'
            ],
            'app_url' => [
                'type' => 'text',
                'label' => 'App Url'
            ],
            'mini_program_url' => [
                'type' => 'text',
                'label' => 'Wechat Mini Program Url'
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
            'language_id[]' => [
                'type' => 'select',
                'label' => 'Language',
                'required' => 'required',
                'options' => $languages,
                'attrs' => [
                    'multiple' => 'multiple'
                ]
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
            'sort_order' => [
                'type' => 'tel',
                'label' => 'Sort Order'
            ],
            'title' => [
                'type' => 'multitext',
                'label' => 'Title',
                'required' => 'required',
                'base' => '#language_id',
                'options' => $languages
            ],
            'content' => [
                'type' => 'multitext',
                'label' => 'Content',
                'base' => '#language_id',
                'options' => $languages
            ],
            'image' => [
                'type' => 'multilanguageimageupload',
                'label' => 'Image',
                'required' => 'required',
                'base' => '#language_id',
                'options' => $languages
            ]
        ];
        return parent::prepareElements($columns);
    }
}
