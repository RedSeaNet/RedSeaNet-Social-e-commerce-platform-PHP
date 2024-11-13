<?php

namespace Redseanet\Customer\Model\Api\Rest;

use Redseanet\Api\Model\Api\Rest\AbstractHandler;
use Redseanet\Customer\Model\Collection\Customer as Collection;
use Redseanet\Customer\Model\Customer as Model;
use Redseanet\Oauth\Model\Token;

class Customer extends AbstractHandler
{
    public function getCustomer()
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
                $customer = new Model();
                $customer->load($token['customer_id']);
                $result = [];
                foreach ($attributes as $attribute) {
                    if (isset($customer[$attribute])) {
                        $result[$attribute] = $customer[$attribute];
                    }
                }
                return $result;
            } elseif (!empty($this->authOptions['user'])) {
                $customer = $this->authOptions['user'];
                $result = [];
                foreach ($attributes as $attribute) {
                    if (isset($customer[$attribute])) {
                        $result[$attribute] = $customer[$attribute];
                    }
                }
                return $result;
            }
        }
        return $this->getResponse()->withStatus(403);
    }

    public function deleteCustomer()
    {
        if ($this->authOptions['validation'] === -1 &&
                count($this->getAttributes(Model::ENTITY_TYPE, false))) {
            $id = $this->getRequest()->getQuery('id');
            if ($id) {
                $customer = new Model();
                $customer->setId($id)->remove();
                return $this->getResponse()->withStatus(202);
            }
            return $this->getResponse()->withStatus(400);
        }
        return $this->getResponse()->withStatus(403);
    }

    public function putCustomer()
    {
        $attributes = $this->getAttributes(Model::ENTITY_TYPE, false);
        $data = $this->getRequest()->getPost();
        $set = [];
        foreach ($attributes as $attribute) {
            if (isset($data[$attribute])) {
                $set[$attribute] = $data[$attribute];
            }
        }
        if ($set) {
            if ($this->authOptions['validation'] === -1) {
                $id = $data['id'];
            } elseif (isset($data['openId']) && $data['openId'] === $this->authOptions['open_id']) {
                $token = new Token();
                $token->load($data['openId'], 'open_id');
                $id = $token['customer_id'];
            } elseif (!empty($this->authOptions['user'])) {
                $id = $this->authOptions['user']->getId();
            }
            $customer = new Model();
            $customer->load($id);
            $customer->setData($set);
            $customer->save();
            return $this->getResponse()->withStatus(202);
        }
        return $this->getResponse()->withStatus(403);
    }
}
