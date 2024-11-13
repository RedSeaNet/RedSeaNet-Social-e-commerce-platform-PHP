<?php

namespace Redseanet\Api\Model\Soap;

use Redseanet\Lib\Model\AbstractModel;
use Laminas\Crypt\Password\Bcrypt;
use Laminas\Crypt\PublicKey\RsaOptions;

class User extends AbstractModel
{
    protected $role = null;
    protected $store = null;

    public function construct()
    {
        $this->init('api_soap_user', 'id', ['id', 'role_id', 'username', 'email', 'password', 'public_key', 'private_key', 'phrase']);
    }

    public function _clone()
    {
        $storage = [
            'id' => $this->storage['id'],
            'role_id' => $this->storage['role_id'],
            'name' => $this->storage['name'],
            'email' => $this->storage['email']
        ];
        $this->storage = $storage;
        $this->isLoaded = false;
    }

    public function valid($username, $password)
    {
        if (!$this->isLoaded) {
            $this->load($username, 'username');
        } elseif ($this->storage['username'] !== $username) {
            $this->isLoaded = false;
            $this->load($username, 'username');
        }
        return (new Bcrypt())->verify($password, $this->storage['password']);
    }

    public function getRole()
    {
        if (is_null($this->role) && $this->offsetGet('role_id')) {
            $role = new Role();
            $role->load($this->offsetGet('role_id'));
            if ($role->getId()) {
                $this->role = $role;
            }
        }
        return $this->role;
    }

    protected function beforeSave()
    {
        if (isset($this->storage['password']) && strpos($this->storage['password'], '$2y$') !== 0) {
            $this->storage['password'] = (new Bcrypt())->create($this->storage['password']);
        }
        if (!empty($this->storage['encrypt']) && empty($this->storage['public_key'])) {
            $rsa = new RsaOptions();
            if (!empty($this->storage['phrase'])) {
                $rsa->setPassPhrase($this->storage['phrase']);
            }
            $rsa->generateKeys();
            $this->setData('public_key', $rsa->getPublicKey())
                    ->setData('private_key', $rsa->getPrivateKey());
        }
        parent::beforeSave();
    }
}
