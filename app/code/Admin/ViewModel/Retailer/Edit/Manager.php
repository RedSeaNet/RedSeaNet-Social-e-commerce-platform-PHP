<?php

namespace Redseanet\Admin\ViewModel\Retailer\Edit;

use Redseanet\Admin\ViewModel\Edit as PEdit;
use Redseanet\Retailer\Source\Retailer;
use Redseanet\Customer\Model\Customer;

class Manager extends PEdit
{
    public function getSaveUrl()
    {
        return $this->getAdminUrl('retailer_manager/save/');
    }

    protected function prepareElements($columns = [])
    {
        $model = $this->getVariable('model');
        $columns = [
            'csrf' => [
                'type' => 'csrf'
            ],
            'id' => [
                'type' => 'hidden',
            ],
            'customer_id' => [
                'type' => 'jqueryselect2',
                'label' => 'Customer',
                'required' => 'required',
                'attrs' => [
                    'data-ajax--url' => $this->getAdminUrl('retailer_manager/getNotManagerCustomerList'),
                    'data-ajax--cache' => true
                ]
            ],
            'retailer_id' => [
                'type' => 'jquerynoajaxselect2',
                'required' => 'required',
                'label' => 'Retailer',
                'options' => (new Retailer())->getSourceArray(),
            ]
        ];
        return parent::prepareElements($columns);
    }
}
