<?php

namespace Redseanet\Customer\Model\Api\Rest;

use Exception;
use Redseanet\Api\Model\Api\Rest\AbstractHandler;
use Redseanet\Oauth\Model\Token;
use Redseanet\Customer\Model\Collection\Address as Collection;
use Redseanet\Customer\Model\Address as Model;

class Address extends AbstractHandler
{
    public function getAddress()
    {
        $data = $this->getRequest()->getQuery();
        $attributes = $this->getAttributes(Model::ENTITY_TYPE);
        if ($attributes) {
            $attributes[] = 'id';
            if ($this->authOptions['validation'] == -1) {
                $collection = new Collection();
                $collection->columns($attributes);
                $this->filter($collection, $data);
                return $collection->load(true, true)->toArray();
            } elseif (isset($data['openId']) && $data['openId'] === $this->authOptions['open_id']) {
                $token = new Token();
                $token->load($data['openId'], 'open_id');
                $collection = new Collection();
                $collection->columns($attributes)
                        ->where(['customer_id' => $token['customer_id']]);
                return $collection->load(true, true)->toArray();
            } elseif (!empty($this->authOptions['user'])) {
                $collection = new Collection();
                $collection->columns($attributes)
                        ->where(['customer_id' => $this->authOptions['user']->getId()]);
                return $collection->load(true, true)->toArray();
            }
        }
        return $this->getResponse()->withStatus(403);
    }

    public function deleteAddress()
    {
        if (count($this->getAttributes(Address::ENTITY_TYPE, false))) {
            $data = $this->getRequest()->getQuery();
            if ($this->authOptions['validation'] === -1) {
                if ($data['id']) {
                    $address = new Model();
                    $address->setId($id)->remove();
                    return $this->getResponse()->withStatus(202);
                }
                return $this->getResponse()->withStatus(400);
            } elseif (isset($data['openId']) && $data['openId'] === $this->authOptions['open_id']) {
                if ($data['id']) {
                    $address = new Model();
                    $address->load($data['id']);
                    $token = new Token();
                    $token->load($data['openId'], 'open_id');
                    if ($address['customer_id'] == $token['customer_id']) {
                        $address->remove();
                        return $this->getResponse()->withStatus(202);
                    }
                }
                return $this->getResponse()->withStatus(400);
            } elseif (!empty($this->authOptions['user'])) {
                if ($data['id']) {
                    $address = new Model();
                    $address->load($data['id']);
                    if ($address['customer_id'] == $this->authOptions['user']['id']) {
                        $address->remove();
                        return $this->getResponse()->withStatus(202);
                    }
                }
                return $this->getResponse()->withStatus(400);
            }
        }
        return $this->getResponse()->withStatus(403);
    }

    public function putAddress()
    {
        $attributes = $this->getAttributes(Address::ENTITY_TYPE, false);
        $data = $this->getRequest()->getPost();
        $set = [];
        foreach ($attributes as $attribute) {
            if (isset($data[$attribute])) {
                $set[$attribute] = $data[$attribute];
            }
        }
        if ($set) {
            $id = $data['id'];
            $address = new Model();
            $address->load($id);
            if ($this->authOptions['validation'] > 0) {
                $flag = false;
                if (isset($data['openId']) && $data['openId'] === $this->authOptions['open_id']) {
                    $token = new Token();
                    $token->load($data['openId'], 'open_id');
                    if ($address['customer_id'] != $token['customer_id']) {
                        $flag = true;
                    }
                } elseif (!empty($this->authOptions['user'])) {
                    if ($address['customer_id'] != $this->authOptions['user']['id']) {
                        $flag = true;
                    }
                }
                if ($flag) {
                    throw new Exception('Not Allowed');
                }
            }
            $address->setData($set);
            $address->save();
            return $this->getResponse()->withStatus(202);
        }
        return $this->getResponse()->withStatus(403);
    }
}
