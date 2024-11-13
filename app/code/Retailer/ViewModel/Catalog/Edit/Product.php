<?php

namespace Redseanet\Retailer\ViewModel\Catalog\Edit;

use Redseanet\Lib\ViewModel\Eav\Edit as PEdit;
use Redseanet\Catalog\Source\Set;
use Redseanet\Lib\Session\Segment;
use Redseanet\Retailer\Model\Retailer;

class Product extends PEdit
{
    protected $hasUploadingFile = true;

    public function __construct()
    {
        $this->setTemplate('retailer/catalog/product/edit');
    }

    public function getSaveUrl()
    {
        return $this->getBaseUrl('retailer/product/save/');
    }

    protected function prepareElements($columns = [])
    {
        $model = $this->getVariable('model');
        $segment = new Segment('customer');
        $retailer = new Retailer();
        $retailer->load($segment->get('customer')['id'], 'customer_id');
        $columns = [
            'id' => [
                'type' => 'hidden'
            ],
            'csrf' => [
                'type' => 'csrf'
            ],
            'store_id' => [
                'type' => 'hidden',
                'value' => $retailer['store_id']
            ],
            'product_type_id' => [
                'type' => 'hidden',
                'value' => $this->getQuery('product_type', $model['product_type_id']),
            ],
            'attribute_set_id' => [
                'type' => 'select',
                'label' => 'Attribute Set',
                'required' => 'required',
                'options' => (new Set())->getSourceArray(),
                'value' => $this->getQuery('attribute_set', $model['attribute_set_id']),
                'attrs' => [
                    'onchange' => 'location.href=\'' . $this->getUri()->withQuery(http_build_query($query = array_diff_key($this->getQuery(), ['attribute_set' => '']))) . (empty($query) ? '?' : '&') . 'attribute_set=\'+this.value;'
                ]
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
