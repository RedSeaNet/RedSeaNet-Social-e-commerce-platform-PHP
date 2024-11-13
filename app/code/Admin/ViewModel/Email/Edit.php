<?php

namespace Redseanet\Admin\ViewModel\Email;

use Redseanet\Admin\ViewModel\Edit as PEdit;
use Redseanet\Lib\Source\Language;

class Edit extends PEdit
{
    public function getSaveUrl()
    {
        return $this->getAdminUrl('email_template/save/');
    }

    public function getDeleteUrl()
    {
        $model = $this->getVariable('model');
        if ($model && $model->getId()) {
            return $this->getAdminUrl('email_template/delete/');
        }
        return false;
    }

    public function getTitle()
    {
        return $this->getQuery('id') ? 'Edit Template' : 'Add New Template';
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
            'code' => [
                'type' => 'text',
                'label' => 'Code',
                'required' => 'required'
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
            'subject' => [
                'type' => 'text',
                'label' => 'Subject',
                'required' => 'required'
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
            'css' => [
                'type' => 'textarea',
                'label' => 'CSS',
            ],
            'content' => [
                'type' => 'textarea',
                'label' => 'Content',
                'class' => 'htmleditor fullbar'
            ]
        ];
        return parent::prepareElements($columns);
    }

    public function getAdditionalButtons()
    {
        $model = $this->getVariable('model');
        if ($model && $model->getId()) {
            return '<button type="button" class="btn btn-theme" data-id="' .
                    $this->getVariable('model')->getId()
                    . '" data-toggle="modal" data-target="#modal-send-email" title="' .
                    $this->translate('Send') . '"><span>' .
                    $this->translate('Send') . '</span></button>';
        }
        return '';
    }
}
