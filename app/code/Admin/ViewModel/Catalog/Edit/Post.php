<?php

namespace Redseanet\Admin\ViewModel\Catalog\Edit;

use Redseanet\Admin\ViewModel\Edit as PEdit;
use Redseanet\Lib\Session\Segment;

class Post extends PEdit
{
    protected $hasUploadingFile = true;

    public function getSaveUrl()
    {
        return $this->getAdminUrl('catalog_product/save/');
    }

    public function getDeleteUrl()
    {
        $model = $this->getVariable('model');
        if ($model && $model->getId()) {
            return $this->getAdminUrl('catalog_forum/delete/');
        }

        return false;
    }

    public function getTitle()
    {
        return $this->getQuery('id') ? 'Edit Product relate posts' : 'Add Product relate posts';
    }

    protected function prepareElements($columns = [])
    {
        $user = (new Segment('admin'))->get('user');
        $model = $this->getVariable('model');
        $columns = [

        ];

        return parent::prepareElements($columns);
    }
}
