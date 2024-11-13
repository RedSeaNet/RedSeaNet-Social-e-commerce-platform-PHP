<?php

namespace Redseanet\Admin\ViewModel\User;

use Redseanet\Admin\ViewModel\Edit;

class AccountEdit extends Edit
{
    public function getTitle()
    {
        return 'My Account';
    }

    public function getSaveUrl()
    {
        return $this->getAdminUrl('user/save/');
    }

    protected function prepareElements($columns = [])
    {
        $columns = [
            'id' => [
                'type' => 'hidden'
            ],
            'csrf' => [
                'type' => 'csrf'
            ],
            'username' => [
                'type' => 'text',
                'label' => 'Username',
                'required' => 'required',
                'attrs' => [
                    'spellcheck' => 'false'
                ]
            ],
            'email' => [
                'type' => 'email',
                'label' => 'Email',
                'required' => 'required',
                'class' => 'email'
            ],
            'crpassword' => [
                'type' => 'password',
                'label' => 'Current Password',
                'value' => '',
                'required' => 'required',
                'attrs' => [
                    'minlength' => 6,
                    'autocomplete' => 'off'
                ]
            ],
            'password' => [
                'type' => 'password',
                'label' => 'Password',
                'value' => '',
                'required' => 'required',
                'attrs' => [
                    'minlength' => 6,
                    'autocomplete' => 'off'
                ]
            ],
            'cpassword' => [
                'type' => 'password',
                'label' => 'Confirm Password',
                'value' => '',
                'required' => 'required',
                'attrs' => [
                    'minlength' => 6,
                    'data-rule-equalto' => '#password',
                    'autocomplete' => 'off'
                ]
            ],
        ];
        return parent::prepareElements($columns);
    }
}
