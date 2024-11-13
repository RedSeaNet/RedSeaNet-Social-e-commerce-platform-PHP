<?php

namespace Redseanet\Catalog\Model\Api\Rest;

use Redseanet\Api\Model\Api\Rest\AbstractHandler;
use Redseanet\Catalog\Model\Collection\Product as Collection;
use Redseanet\Catalog\Model\Product as Model;

class Product extends AbstractHandler
{
    public function getProduct()
    {
        $data = $this->getRequest()->getQuery();
        $columns = $this->getAttributes(Model::ENTITY_TYPE);
        if (count($columns)) {
            $products = new Collection();
            $products->columns($columns);
            $this->filter($products, $data);
            $result = [];
            foreach ($products as $product) {
                $options = [];
                foreach ($product->getOptions()->withLabel() as $option) {
                    $options[] = (
                        in_array($option['input'], ['select', 'radio', 'checkbox', 'multiselect']) ?
                            ['values' => $option->getValues()] : []
                    ) + $option->toArray();
                }
                $result[] = [
                    'absolute_url' => $product->getURl(),
                    'options' => $options
                ] + $product->toArray();
            }
            return $result;
        }
        return $this->getResponse()->withStatus(403);
    }

    public function deleteProduct()
    {
        $attributes = $this->getAttributes(Model::ENTITY_TYPE, false);
        if ($this->authOptions['validation'] === -1 && count($attributes)) {
            $id = $this->getRequest()->getQuery('id');
            if ($id) {
                $product = new Model();
                $product->setId($id)->remove();
                return $this->getResponse()->withStatus(202);
            }
            return $this->getResponse()->withStatus(400);
        }
        return $this->getResponse()->withStatus(403);
    }

    public function putProduct()
    {
        $attributes = $this->getAttributes(Model::ENTITY_TYPE, false);
        if ($this->authOptions['validation'] === -1 && count($attributes)) {
            $id = $this->getRequest()->getQuery('id');
            $product = new Model();
            if ($id) {
                $product->load($id);
            }
            $data = $this->getRequest()->getPost();
            $set = [];
            foreach ($attributes as $attribute) {
                if (isset($data[$attribute])) {
                    $set[$attribute] = $data[$attribute];
                }
            }
            if ($set) {
                $product->setData($set);
                $product->save();
            }
            return $this->getResponse()->withStatus(202);
        }
        return $this->getResponse()->withStatus(403);
    }
}
