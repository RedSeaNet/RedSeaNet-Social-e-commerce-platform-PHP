<?php

namespace Redseanet\Catalog\Model\Api\Rest;

use Redseanet\Api\Model\Api\Rest\AbstractHandler;
use Redseanet\Catalog\Model\Category as Model;
use Redseanet\Catalog\Model\Collection\Category as Collection;

class Category extends AbstractHandler
{
    public function getCategory()
    {
        $data = $this->getRequest()->getQuery();
        $columns = $this->getAttributes(Model::ENTITY_TYPE);
        if (count($columns)) {
            $categories = new Collection();
            $categories->columns($columns);
            $this->filter($categories, $data);
            $categories->load(true, true);
            return $categories->toArray();
        }
        return $this->getResponse()->withStatus(403);
    }

    public function deleteCategory()
    {
        $attributes = $this->getAttributes(Model::ENTITY_TYPE, false);
        if ($this->authOptions['validation'] === -1 && count($attributes)) {
            $id = $this->getRequest()->getQuery('id');
            if ($id) {
                $category = new Model();
                $category->setId($id)->remove();
                return $this->getResponse()->withStatus(202);
            }
            return $this->getResponse()->withStatus(400);
        }
        return $this->getResponse()->withStatus(403);
    }

    public function putCategory()
    {
        $attributes = $this->getAttributes(Model::ENTITY_TYPE, false);
        if ($this->authOptions['validation'] === -1 && count($attributes)) {
            $id = $this->getRequest()->getQuery('id');
            $category = new Model();
            if ($id) {
                $category->load($id);
            }
            $data = $this->getRequest()->getPost();
            $set = [];
            foreach ($attributes as $attribute) {
                if (isset($data[$attribute])) {
                    $set[$attribute] = $data[$attribute];
                }
            }
            if ($set) {
                $category->setData($set);
                $category->save();
            }
            return $this->getResponse()->withStatus(202);
        }
        return $this->getResponse()->withStatus(403);
    }
}
