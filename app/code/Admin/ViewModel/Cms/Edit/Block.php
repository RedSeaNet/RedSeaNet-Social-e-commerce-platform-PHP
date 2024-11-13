<?php

namespace Redseanet\Admin\ViewModel\Cms\Edit;

use Redseanet\Admin\ViewModel\Edit as PEdit;
use Redseanet\Lib\Session\Segment;
use Redseanet\Lib\Source\Language;
use Redseanet\Lib\Source\Store;
use Redseanet\Admin\Model\User;

class Block extends PEdit
{
    public function getSaveUrl()
    {
        return $this->getAdminUrl('cms_block/save/');
    }

    public function getDeleteUrl()
    {
        $model = $this->getVariable('model');
        if ($model && $model->getId()) {
            return $this->getAdminUrl('cms_block/delete/');
        }
        return false;
    }

    public function getTitle()
    {
        return $this->getQuery('id') ? 'Edit Block' : 'Add New Block';
    }

    protected function prepareElements($columns = [])
    {
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
                'options' => (new Language())->getSourceArray(),
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
            'content' => [
                'type' => 'textarea',
                'label' => 'Content',
                'class' => 'htmleditor'
            ]
        ];
        return parent::prepareElements($columns);
    }
}
