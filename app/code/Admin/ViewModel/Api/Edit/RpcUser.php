<?php

namespace Redseanet\Admin\ViewModel\Api\Edit;

use Redseanet\Admin\ViewModel\Edit as PEdit;
use Redseanet\Api\Source\RpcRole;

class RpcUser extends PEdit
{
    public function getSaveUrl()
    {
        return $this->getAdminUrl('api_rpc_user/save/');
    }

    public function getDeleteUrl()
    {
        $model = $this->getVariable('model');
        if ($model && $model->getId()) {
            return $this->getAdminUrl('api_rpc_user/delete/');
        }
        return false;
    }

    public function getTitle()
    {
        return $this->getQuery('id') ? 'Edit RPC User' : 'Add New RPC User';
    }

    public function prepareElements($columns = [])
    {
        $model = $this->getVariable('model');
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
                'required' => 'required'
            ],
            'role_id' => [
                'type' => 'select',
                'label' => 'Role',
                'options' => (new RpcRole())->getSourceArray(),
                'required' => 'required'
            ],
            'email' => [
                'type' => 'email',
                'label' => 'Email',
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
            'encrypt' => [
                'type' => 'checkbox',
                'label' => 'Encrypt Result',
                'value' => $model && $model['public_key'] ? 1 : 0,
                'options' => [
                    '1' => $model ? 'Modify Encryption Key' : ''
                ],
                'comment' => 'We use RSA cryptography to encrypt/decrypt data.'
            ],
            'public_key' => [
                'type' => 'sslkey',
                'method' => 'openssl_pkey_get_public',
                'label' => 'Public Key',
                'attrs' => [
                    'data-base' => '#encrypt-1',
                    'autocomplete' => 'off',
                    'spellcheck' => 'false'
                ],
                'comment' => 'Leave blank to generate keys automatically.'
            ],
            'private_key' => [
                'type' => 'sslkey',
                'method' => 'openssl_pkey_get_private',
                'phrase_key' => 'phrase',
                'label' => 'Private Key',
                'attrs' => [
                    'data-base' => '#encrypt-1',
                    'autocomplete' => 'off',
                    'spellcheck' => 'false'
                ]
            ],
            'phrase' => [
                'type' => 'text',
                'label' => 'Private Key Phrase',
                'attrs' => [
                    'data-base' => '#encrypt-1',
                    'autocomplete' => 'off',
                    'spellcheck' => 'false',
                    'maxlength' => '127'
                ]
            ]
        ];
        return parent::prepareElements($columns);
    }
}
