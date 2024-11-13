<?php

namespace Redseanet\Admin\ViewModel\Customer\Edit;

use Redseanet\Admin\ViewModel\Edit as PEdit;

class Notifications extends PEdit
{
    public function getSaveUrl()
    {
        return $this->getAdminUrl('customer_notifications/save/');
    }

    public function getTitle()
    {
        return $this->getQuery('id') ? 'Edit Notifications' : 'Add Notifications';
    }

    protected function prepareElements($columns = [])
    {
        $columns = [
            'csrf' => [
                'type' => 'csrf',
            ],
            'title' => [
                'type' => 'text',
                'label' => 'Title',
            ],
            'content' => [
                'type' => 'text',
                'label' => 'Content',
            ],
            'customers[]' => [
                'type' => 'jqueryselect2',
                'label' => 'Customer',
                'required' => 'required',
                'attrs' => [
                    'data-ajax--url' => $this->getAdminUrl('customer_manage/getlist'),
                    'data-ajax--cache' => true,
                    'multiple' => 'multiple'
                ]
            ],
            'is_app' => [
                'type' => 'select',
                'options' => [
                    1 => 'Yes',
                    0 => 'No'
                ],
                'label' => 'Is APP'
            ],
            'is_sms' => [
                'type' => 'select',
                'options' => [
                    1 => 'Yes',
                    0 => 'No'
                ],
                'label' => 'Is SMS'
            ],
        ];
        return parent::prepareElements($columns);
    }
}
