<?php

namespace Redseanet\Admin\ViewModel\Api\Edit;

use Redseanet\Admin\ViewModel\Edit as PEdit;

class RsaKey extends PEdit
{
    public function getTitle()
    {
        return $this->getQuery('id') ? 'test RSA KEY' : 'test RSA KEY';
    }

    public function getSaveUrl()
    {
        return false;
    }

    public function getAdditionalButtons()
    {
        return false;
    }

    protected function prepareElements($columns = [])
    {
        $data = $this->getVariable('data');
        $columnsData = [];
        if (isset($data['privatekey']) && $data['privatekey'] != '') {
            $columnsData['privatekey'] = $data['privatekey'];
        } else {
            $columnsData['privatekey'] = '';
        }
        if (isset($data['publickey']) && $data['publickey'] != '') {
            $columnsData['publickey'] = $data['publickey'];
        } else {
            $columnsData['publickey'] = '';
        }
        if (isset($data['testtext']) && $data['testtext'] != '') {
            $columnsData['testtext'] = $data['testtext'];
        } else {
            $columnsData['testtext'] = '';
        }
        if (isset($data['encryptedtext']) && $data['encryptedtext'] != '') {
            $columnsData['encryptedtext'] = $data['encryptedtext'];
        } else {
            $columnsData['encryptedtext'] = '';
        }
        $columns = [
            'privatekey' => [
                'type' => 'textarea',
                'label' => 'Private key',
                'value' => $columnsData['privatekey']
            ],
            'publickey' => [
                'type' => 'textarea',
                'label' => 'Public key',
                'value' => $columnsData['publickey']
            ],
            'testtext' => [
                'type' => 'textarea',
                'label' => 'Test Text',
                'value' => $columnsData['testtext']
            ],
            'encryptedtext' => [
                'type' => 'textarea',
                'label' => 'Encripted Text',
                'value' => $columnsData['encryptedtext']
            ],
            'action' => [
                'type' => 'select',
                'label' => 'Action ',
                'options' => ['generatenewkey' => 'Generate New Key', 'encryptedtext' => 'Encrypted Text', 'dencryptedtext' => 'Dencrypted Test Text']
            ],
            'phrase' => [
                'type' => 'text',
                'label' => 'Key Phrase',
                'value' => 'redseanet'
            ]
        ];
        return parent::prepareElements($columns);
    }
}
