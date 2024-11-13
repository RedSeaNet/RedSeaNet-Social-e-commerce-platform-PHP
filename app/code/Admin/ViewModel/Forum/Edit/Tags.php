<?php

namespace Redseanet\Admin\ViewModel\Forum\Edit;

use Redseanet\Admin\ViewModel\Edit as PEdit;
use Redseanet\Lib\Model\Collection\Language;

class Tags extends PEdit
{
    protected $hasUploadingFile = true;

    public function getSaveUrl()
    {
        return $this->getAdminUrl('forum_tags/save/');
    }

    public function getDeleteUrl()
    {
        $model = $this->getVariable('model');
        if ($model && $model->getId()) {
            return $this->getAdminUrl('forum_tags/delete/');
        }
        return false;
    }

    public function getTitle()
    {
        return $this->getQuery('id') ? 'Edit Hashtags' : 'Add hashtags';
    }

    protected function prepareElements($columns = [])
    {
        $model = $this->getVariable('model');
        $columns = [
            'id' => [
                'type' => 'hidden'
            ],
            'csrf' => [
                'type' => 'csrf'
            ],
            'sys_recommended' => [
                'type' => 'select',
                'label' => 'System recommended',
                'options' => [
                    1 => 'Yes',
                    0 => 'No'
                ],
                'required' => 'required'
            ],
            'sort_order' => [
                'type' => 'text',
                'label' => 'Sort Order'
            ]
        ];
        foreach (new Language() as $language) {
            $columns['name[' . $language->getId() . ']'] = [
                'type' => 'text',
                'required' => 'required',
                'label' => $this->translate('Name') . '[' . $language['name'] . ']',
                'value' => $model['name'][$language->getId()] ?? ''
            ];
        }
        return parent::prepareElements($columns);
    }
}
