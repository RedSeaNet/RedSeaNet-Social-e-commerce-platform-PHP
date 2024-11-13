<?php

namespace Redseanet\Admin\ViewModel\I18n\Edit;

use Redseanet\Admin\ViewModel\Edit as PEdit;
use Redseanet\Lib\Model\Collection\Merchant as Collection;

class Merchant extends PEdit
{
    public function getSaveUrl()
    {
        return $this->getAdminUrl('i18n_merchant/save/');
    }

    public function getDeleteUrl()
    {
        $model = $this->getVariable('model');
        if ($model && $model->getId()) {
            $collection = new Collection();
            $collection->columns(['id'])
                    ->where('id <> ' . $model->getId());
            return $collection->count() ? $this->getAdminUrl('i18n_merchant/delete/') : false;
        }
        return false;
    }

    public function getTitle()
    {
        return $this->getQuery('id') ? 'Edit Merchant' : 'Add New Merchant';
    }

    protected function prepareElements($columns = [])
    {
        $columns = [
            'id' => [
                'type' => 'hidden',
            ],
            'csrf' => [
                'type' => 'csrf'
            ],
            'code' => [
                'type' => 'text',
                'label' => 'Name',
                'required' => 'required'
            ],
            'is_default' => [
                'type' => 'select',
                'label' => 'Is Default',
                'required' => 'required',
                'options' => ['No', 'Yes']
            ],
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
