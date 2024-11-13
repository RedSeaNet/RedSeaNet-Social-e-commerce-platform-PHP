<?php

namespace Redseanet\Customer\Model;

use Redseanet\Customer\Model\Collection\Group;
use Redseanet\Lib\Model\Collection\Language;
use Redseanet\Lib\Model\Eav\Entity;
use Redseanet\Lib\Model\Language as LanguageModel;
use Redseanet\Lib\Model\Store;
use Redseanet\Lib\Session\Segment;
use Laminas\Crypt\Password\Bcrypt;

class Customer extends Entity
{
    public const ENTITY_TYPE = 'customer';

    public static $attrForLogin = [
        'username',
        'email',
        'cel'
    ];

    protected function construct()
    {
        $this->init('id', ['id', 'type_id', 'attribute_set_id', 'store_id', 'language_id', 'increment_id', 'confirm_token', 'confirm_token_created_at', 'status']);
    }

    public function __clone()
    {
        $this->storage = array_diff_key($this->storage, ['password', 'confirm_token', 'confirm_token_created_at']);
        $this->isLoaded = false;
    }

    public function getLanguage()
    {
        if (!empty($this->storage['language_id'])) {
            $language = new LanguageModel();
            $language->load($this->storage['language_id']);
            return $language;
        }
        return null;
    }

    protected function beforeSave()
    {
        if (isset($this->storage['password']) && strpos($this->storage['password'], '$2y$') !== 0) {
            $this->storage['password'] = (new Bcrypt())->create($this->storage['password']);
        }
        parent::beforeSave();
        if ($this['cel'] == '') {
            $this['cel'] = $this['increment_id'];
        }
        if ($this['email'] == '') {
            $this['email'] = $this['increment_id'];
        }
    }

    protected function afterSave()
    {
        parent::afterSave();
        if (isset($this->storage['group_id'])) {
            $tableGateway = $this->getTableGateway('customer_in_group');
            $tableGateway->delete(['customer_id' => $this->getId()]);
            $groups = is_string($this->storage['group_id']) ? explode(',', $this->storage['group_id']) : (array) $this->storage['group_id'];
            foreach ($groups as $id) {
                $tableGateway->insert(['group_id' => $id, 'customer_id' => $this->getId()]);
            }
            $this->flushList('customer_group');
        }
        if ($this->getId() && !empty($this->languageId) && $this->languageId != 0 && empty($this->storage['_sync'])) {
            $languages = new Language();
            $languages->getSelect()->where->notEqualTo('id', $this->languageId);
            foreach ($languages as $language) {
                $model = new static($language->getId());
                $model->setData(['_sync' => true] + $this->storage)->save();
            }
        }
    }

    protected function afterLoad(&$result)
    {
        $id = $result[$this->primaryKey] ?? ($result[0][$this->primaryKey] ?? null);
        if ($id) {
            $tableGateway = $this->getTableGateway('customer_in_group');
            $groups = [];
            foreach ($tableGateway->select(['customer_id' => (int) $id])->toArray() as $item) {
                $groups[] = $item['group_id'];
            }
            if (isset($result[0])) {
                $result[0]['group_id'] = $groups;
            } else {
                $result['group_id'] = $groups;
            }
        }
        parent::afterLoad($result);
    }

    public function login($username, $password)
    {
        if ($this->valid($username, $password)) {
            $segment = new Segment('customer');
            $segment->set('hasLoggedIn', true)
                    ->set('customer', (clone $this)->toArray());
            //$this->getEventDispatcher()->trigger('customer.login.after', ['model' => $this]);
            return true;
        }
        return false;
    }

    public function valid($username, $password)
    {
        foreach (static::$attrForLogin as $attr) {
            if (!$this->isLoaded) {
                $this->load($username, $attr);
            } elseif ($this->storage[$attr] !== $username) {
                $this->isLoaded = false;
                $this->load($username, $attr);
            }
            if ($this->getId()) {
                break;
            }
        }
        return $this->offsetGet('status') && (new Bcrypt())->verify($password, $this->offsetGet('password'));
    }

    public function getGroup()
    {
        if ($this->getId()) {
            $groups = new Group();
            $groups->join('customer_in_group', 'customer_in_group.group_id=customer_group.id', [], 'left')
                    ->where(['customer_in_group.customer_id' => $this->getId()]);
            return $groups;
        }
        return [];
    }

    public function getLevel()
    {
        if (empty($this->storage['level'])) {
            $this->getEventDispatcher()->trigger('customer.level.calc', ['customer' => $this]);
        } else {
            $this->storage['level'] = (new Level())->load($this->storage['level']);
        }
        return empty($this->storage['level']) ? 0 : $this->storage['level']->getName();
    }

    public function getBalance()
    {
        if (empty($this->storage['balance'])) {
            $this->getEventDispatcher()->trigger('customer.balance.calc', ['customer' => $this]);
        }
        return (float) (empty($this->storage['balance']) ? 0 : $this->storage['balance']);
    }

    public function getPoints()
    {
        if (empty($this->storage['rewardpoints'])) {
            $this->getEventDispatcher()->trigger('customer.rewardpoints.calc', ['model' => $this]);
        }
        return (float) (empty($this->storage['rewardpoints']) ? 0 : $this->storage['rewardpoints']);
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
}
