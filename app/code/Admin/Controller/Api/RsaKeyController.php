<?php

namespace Redseanet\Admin\Controller\Api;

use Redseanet\Lib\Session\Segment;
use Redseanet\Lib\Controller\AuthActionController;
use Laminas\Crypt\PublicKey\{
    Rsa
};
use Laminas\Crypt\PublicKey\RsaOptions;

class RsakeyController extends AuthActionController
{
    public function indexAction()
    {
        $query = $this->getRequest()->getQuery();
        $data = [];
        if (isset($query['phrase']) && $query['phrase'] != '') {
            $data['phrase'] = $query['phrase'];
        } else {
            $data['phrase'] = 'redseanet';
        }
        if (isset($query['action']) && $query['action'] == 'generatenewkey') {
            $rsaOptions = new RsaOptions([
                'pass_phrase' => $data['phrase']
            ]);
            $rsaOptions->generateKeys([
                'private_key_bits' => 1024,
            ]);
            $data['privatekey'] = $rsaOptions->getPrivateKey();
            $data['publickey'] = $rsaOptions->getPublicKey();
        } elseif (isset($query['action']) && $query['action'] == 'encryptedtext') {
            if (isset($query['privatekey']) && $query['privatekey'] != '' && isset($query['publickey']) && $query['publickey'] != '' && isset($query['testtext']) && $query['testtext'] != '') {
                $rsa = Rsa::factory([
                    'public_key' => $query['publickey'],
                    'private_key' => $query['privatekey'],
                    'pass_phrase' => $data['phrase'],
                    'binary_output' => false,
                    'openssl_padding' => OPENSSL_PKCS1_PADDING
                ]);
                $data['privatekey'] = $query['privatekey'];
                $data['publickey'] = $query['publickey'];
                $data['testtext'] = $query['testtext'];
                $data['encryptedtext'] = $rsa->encrypt($data['testtext']);
            }
        } elseif (isset($query['action']) && $query['action'] == 'dencryptedtext') {
            if (isset($query['privatekey']) && $query['privatekey'] != '' && isset($query['publickey']) && $query['publickey'] != '' && isset($query['encryptedtext']) && $query['encryptedtext'] != '') {
                $rsa = Rsa::factory([
                    'public_key' => $query['publickey'],
                    'private_key' => $query['privatekey'],
                    'pass_phrase' => $data['phrase'],
                    'binary_output' => false,
                    'openssl_padding' => OPENSSL_PKCS1_PADDING
                ]);
                $data['privatekey'] = $query['privatekey'];
                $data['publickey'] = $query['publickey'];
                $data['encryptedtext'] = $query['encryptedtext'];
                $data['testtext'] = $rsa->decrypt($query['encryptedtext']);
            }
        }
        $root = $this->getLayout('admin_rsa_key');
        $root->getChild('edit', true)->setVariable('data', $data);

        return $root;
    }
}
