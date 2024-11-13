<?php

namespace Redseanet\Admin\ViewModel\Catalog\Edit;

use Redseanet\Admin\ViewModel\Eav\Edit as PEdit;
use Redseanet\Lib\Source\Store;
use Redseanet\Lib\Session\Segment;
use Redseanet\Admin\Model\User;

class Category extends PEdit
{
    protected $hasUploadingFile = true;

    public function getSaveUrl()
    {
        return $this->getAdminUrl('catalog_category/save/');
    }

    public function getDeleteUrl()
    {
        $model = $this->getVariable('model');
        if ($model && $model->getId()) {
            return $this->getAdminUrl('catalog_category/delete/');
        }
        return false;
    }

    public function getTitle()
    {
        return $this->getQuery('id') ? 'Edit Category' : 'Add New Category';
    }

    protected function prepareElements($columns = [])
    {
        $userArray = (new Segment('admin'))->get('user');
        $user = new User();
        $user->load($userArray['id']);
        $model = $this->getVariable('model');
        $columns = [
            'id' => [
                'type' => 'hidden'
            ],
            'csrf' => [
                'type' => 'csrf'
            ],
            'parent_id' => [
                'type' => 'hidden',
                'value' => $this->getQuery('pid', '')
            ],
            'store_id' => ($user->getStore() ? [
                'type' => 'hidden',
                'value' => $user->getStore()->getId()
            ] : [
                'type' => 'select',
                'options' => (new Store())->getSourceArray(),
                'label' => 'Store',
                'required' => 'required'
            ]),
            'status' => [
                'type' => 'select',
                'label' => 'Status',
                'options' => [
                    1 => 'Enabled',
                    0 => 'Disabled'
                ],
                'required' => 'required'
            ]
        ];
        return parent::prepareElements($columns);
    }
}
