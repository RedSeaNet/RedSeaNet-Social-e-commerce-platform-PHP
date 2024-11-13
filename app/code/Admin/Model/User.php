<?php

namespace Redseanet\Admin\Model;

use Redseanet\Lib\Model\AbstractModel;
use Redseanet\Lib\Model\Store;
use Redseanet\Lib\Session\Segment;
use Laminas\Crypt\Password\Bcrypt;

/**
 * System backend user
 */
class User extends AbstractModel
{
    protected $role = null;
    protected $store = null;

    protected function construct()
    {
        $this->init('admin_user', 'id', ['id', 'role_id', 'status', 'username', 'password', 'email', 'logdate', 'lognum', 'rp_token', 'rp_token_created_at']);
    }

    public function __clone()
    {
        $storage = [
            'id' => $this->storage['id'],
            'role_id' => $this->storage['role_id'],
            'store_id' => $this->storage['store_id'],
            'username' => $this->storage['username'],
            'email' => $this->storage['email']
        ];
        $this->storage = $storage;
        $this->isLoaded = false;
    }

    public function login($username, $password)
    {
        if ($this->valid($username, $password)) {
            $segment = new Segment('admin');
            $segment->set('hasLoggedIn', true)
                    ->set('user', (clone $this)->toArray());
            $this->getEventDispatcher()->trigger('admin.user.login.after', ['model' => $this]);
            return true;
        }
        return false;
    }

    public function valid($username, $password)
    {
        if (!$this->isLoaded) {
            $this->load($username, 'username');
        } elseif ($this->storage['username'] !== $username) {
            $this->isLoaded = false;
            $this->load($username, 'username');
        }
        return $this->offsetGet('status') && (new Bcrypt())->verify($password, $this->offsetGet('password'));
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

    public function getStore()
    {
        if (is_null($this->store) && $this->offsetGet('store_id')) {
            $store = new Store();
            $store->load($this->offsetGet('store_id'));
            if ($store->getId()) {
                $this->store = $store;
            }
        }
        return $this->store;
    }

    protected function beforeSave()
    {
        if (isset($this->storage['password']) && strpos($this->storage['password'], '$2y$') !== 0) {
            $this->storage['password'] = (new Bcrypt())->create($this->storage['password']);
        }
        parent::beforeSave();
    }
}
