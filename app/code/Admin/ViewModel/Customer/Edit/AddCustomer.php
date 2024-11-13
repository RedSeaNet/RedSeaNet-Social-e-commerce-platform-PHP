<?php

namespace Redseanet\Admin\ViewModel\Customer\Edit;

use Redseanet\Admin\ViewModel\Edit as PEdit;
use Redseanet\Customer\Source\Group;

class AddCustomer extends PEdit
{
    public function getSaveUrl()
    {
        return $this->getAdminUrl('customer_group/AddCustomerSave/');
    }

    public function getTitle()
    {
        return $this->getQuery('id') ? 'Edit Customer in Group' : 'Add Customer in Group';
    }

    protected function prepareElements($columns = [])
    {
        $columns = [
            'csrf' => [
                'type' => 'csrf',
            ],
            'group' => [
                'type' => 'select',
                'label' => 'Customer Group',
                'options' => (new Group())->getSourceArray(),
                'required' => 'required',
                'value' => $this->getQuery('group_name'),
            ],
            'customers[]' => [
                'type' => 'jqueryselect2',
                'label' => 'Customer',
                'required' => 'required',
                'attrs' => [
                    'data-ajax--url' => $this->getAdminUrl('customer_manage/getlist'),
                    'data-ajax--cache' => true,
                    'multiple' => 'multiple'
                ]
            ]
        ];

        return parent::prepareElements($columns);
    }
}
