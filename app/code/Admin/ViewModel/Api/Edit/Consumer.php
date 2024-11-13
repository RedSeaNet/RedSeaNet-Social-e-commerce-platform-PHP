<?php

namespace Redseanet\Admin\ViewModel\Api\Edit;

use Redseanet\Admin\ViewModel\Edit as PEdit;
use Redseanet\Api\Source\RestRole;
use Redseanet\Oauth\Model\Collection\Consumer as Collection;
use Laminas\Math\Rand;
use Laminas\Crypt\PublicKey\RsaOptions;

class Consumer extends PEdit
{
    public function getSaveUrl()
    {
        return $this->getAdminUrl('api_oauth_consumer/save/');
    }

    public function getDeleteUrl()
    {
        $model = $this->getVariable('model');
        if ($model && $model->getId()) {
            return $this->getAdminUrl('api_oauth_consumer/delete/');
        }
        return false;
    }

    public function getTitle()
    {
        return $this->getQuery('id') ? 'Edit Consumer' : 'Add New Consumer';
    }

    protected function prepareElements($columns = [])
    {
        $model = $this->getVariable('model');
        if ($model && $model->getId()) {
            $key = $model['key'];
            $secret = $model['secret'];
            $public = $model['public_key'];
            $private = $model['private_key'];
            $phrase = $model['phrase'];
        } else {
            $collection = new Collection();
            do {
                $key = Rand::getString(32, 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789');
                $collection->reset('where')->where(['key' => $key]);
            } while (count($collection));
            do {
                $secret = Rand::getString(32, 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789');
                $collection->reset('where')->where(['secret' => $secret]);
            } while (count($collection));
            $phrase = Rand::getString(Rand::getInteger(32, 40));
            $rsa = new RsaOptions();
            $rsa->setPassPhrase($phrase)
                    ->generateKeys();
            $public = $rsa->getPublicKey();
            $private = $rsa->getPrivateKey();
        }
        $columns = [
            'id' => [
                'type' => 'hidden',
            ],
            'csrf' => [
                'type' => 'csrf'
            ],
            'role_id' => [
                'type' => 'select',
                'label' => 'Role',
                'required' => 'required',
                'options' => (new RestRole())->getSourceArray()
            ],
            'name' => [
                'type' => 'text',
                'label' => 'Name',
                'required' => 'required'
            ],
            'key' => [
                'type' => 'text',
                'label' => 'Client Key',
                'required' => 'required',
                'attrs' => [
                    'readonly' => 'readonly',
                    'spellcheck' => 'false'
                ],
                'value' => $key
            ],
            'secret' => [
                'type' => 'text',
                'label' => 'Client Secret',
                'required' => 'required',
                'attrs' => [
                    'readonly' => 'readonly',
                    'spellcheck' => 'false'
                ],
                'value' => $secret
            ],
            'public_key' => [
                'type' => 'sslkey',
                'method' => 'openssl_pkey_get_public',
                'label' => 'Public Key',
                'attrs' => [
                    'readonly' => 'readonly',
                    'spellcheck' => 'false'
                ],
                'value' => $public
            ],
            'private_key' => [
                'type' => 'sslkey',
                'method' => 'openssl_pkey_get_private',
                'phrase' => $phrase,
                'label' => 'Private Key',
                'attrs' => [
                    'readonly' => 'readonly',
                    'spellcheck' => 'false'
                ],
                'value' => $private
            ],
            'phrase' => [
                'type' => 'text',
                'label' => 'Private Key Phrase',
                'attrs' => [
                    'readonly' => 'readonly',
                    'spellcheck' => 'false'
                ],
                'value' => $phrase
            ],
            'callback_url' => [
                'type' => 'url',
                'label' => 'Callback Url',
                'required' => 'required'
            ],
            'rejected_callback_url' => [
                'type' => 'url',
                'label' => 'Rejected Callback Url'
            ]
        ];
        return parent::prepareElements($columns);
    }
}
