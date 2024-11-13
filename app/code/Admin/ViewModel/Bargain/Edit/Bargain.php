<?php

namespace Redseanet\Admin\ViewModel\Bargain\Edit;

use Redseanet\Admin\ViewModel\Edit;
use Redseanet\Lib\Source\Store;
use Redseanet\Lib\Session\Segment;
use Redseanet\Lib\Model\Collection\Language;

class Bargain extends Edit
{
    public function getSaveUrl()
    {
        return $this->getAdminUrl('bargain/save/');
    }

    public function getDeleteUrl()
    {
        $model = $this->getVariable('model');
        if ($model && $model->getId()) {
            return $this->getAdminUrl('bargain/delete/');
        }
        return false;
    }

    public function getTitle()
    {
        return $this->getQuery('id') ? 'Edit Bargain' : 'Add New Bargain';
    }

    protected function prepareElements($columns = [])
    {
        $model = $this->getVariable('model');
        $columns = ['chooseproduct' => [
            'type' => 'link',
            'label' => 'Product',
            'content' => 'Please choose product',
            'attrs' => ['data-toggle' => 'modal', 'data-target' => '#productListModal', 'class' => 'btn btn-submit']
        ],
            'thumbnail' => [
                'type' => 'widget',
                'label' => 'Thumbnail',
                'widget' => 'upload',
                'attrs' => ['id' => 'widgetthumbnail']
            ],
            'images' => [
                'type' => 'widget',
                'label' => 'Image',
                'widget' => 'upload',
                'multiple' => true,
                'attrs' => ['id' => 'widgetimage']
            ],
            'productoptions' => [
                'type' => 'widget',
                'label' => 'Options',
                'widget' => 'productoptions',
                'attrs' => ['id' => 'productoptiosdiv']
            ]];
        foreach (new Language() as $language) {
            $columns['name[' . $language->getId() . ']'] = [
                'type' => 'text',
                'required' => 'required',
                'label' => $this->translate('Name') . '[' . $language['name'] . ']',
                'value' => $model['name'][$language->getId()] ?? ''
            ];
        }
        foreach (new Language() as $language) {
            $columns['description[' . $language->getId() . ']'] = [
                'type' => 'text',
                'required' => 'required',
                'label' => $this->translate('Description') . '[' . $language['name'] . ']',
                'value' => $model['description'][$language->getId()] ?? ''
            ];
        }
        foreach (new Language() as $language) {
            $columns['content[' . $language->getId() . ']'] = [
                'type' => 'htmltextarea',
                'required' => 'required',
                'label' => $this->translate('Content') . '[' . $language['name'] . ']',
                'value' => $model['content'][$language->getId()] ?? ''
            ];
        }
        $columns['id'] = [
            'type' => 'hidden'
        ];
        $columns['product_id'] = [
            'type' => 'hidden',
            'attrs' => ['id' => 'choosen_product_id']
        ];
        $columns['store_id'] = [
            'type' => 'hidden',
            'attrs' => ['id' => 'choosen_store_id']
        ];
        $columns['csrf'] = [
            'type' => 'csrf'
        ];
        $columns['start_time'] = [
            'type' => 'datetime',
            'label' => 'Start Time',
            'required' => 'required',
            'attrs' => [
                'data-toggle' => 'datetimepicker'
            ],
            'comment' => 'Set the bargain start time, Cutomers can initiate participation in bargaining after the set time.'
        ];
        $columns['stop_time'] = [
            'type' => 'datetime',
            'label' => 'End Time',
            'required' => 'required',
            'attrs' => [
                'data-toggle' => 'datetimepicker'
            ],
            'comment' => 'Set the end time of the bargain, Customers can participate in bargaining before launching within the set time.'
        ];
        $columns['people_num'] = [
            'type' => 'tel',
            'label' => 'People number',
            'required' => 'required',
            'default' => 1,
            'comment' => 'Number of people needed to bargain successfully. eg. In 3, 3 customers to help the bargain, It will be successfully.'
        ];
        $columns['stock'] = [
            'type' => 'tel',
            'label' => 'Stock',
            'required' => 'required',
            'default' => 1,
            'comment' => 'Total stock of the bargain.'
        ];
        $columns['num'] = [
            'type' => 'tel',
            'label' => 'Max quantity for purchase',
            'required' => 'required',
            'default' => 1,
            'comment' => 'The customer can purchase max quantity.'
        ];
        $columns['bargain_num'] = [
            'type' => 'tel',
            'label' => 'Bargain Number',
            'required' => 'required',
            'default' => 0,
            'comment' => 'The number of times that a single product user can help, for example: Set the number of times to 1, A and B send the bargaining link of product P to C at the same time, and C can only help one of A or B to bargain.'
        ];
        $columns['price'] = [
            'type' => 'price',
            'label' => 'Price',
            'required' => 'required',
            'default' => 0,
            'comment' => 'Price'
        ];
        $columns['min_price'] = [
            'type' => 'price',
            'label' => 'Min Price',
            'required' => 'required',
            'default' => 1,
            'comment' => 'Lowest bargain price.'
        ];
        $columns['bargain_max_price'] = [
            'type' => 'price',
            'label' => 'Bargain max price',
            'default' => 0,
            'comment' => 'Bargain max price with every cutomer.'
        ];
        $columns['bargain_min_price'] = [
            'type' => 'price',
            'label' => 'Bargain min price',
            'default' => 0,
            'comment' => 'Bargain min price with every cutomer.'
        ];
        $columns['original_price'] = [
            'type' => 'price',
            'label' => 'Original Price',
            'default' => 0,
            'comment' => 'Original Price'
        ];
        $columns['sort_order'] = [
            'type' => 'tel',
            'label' => 'Sort Order',
            'default' => 1,
            'comment' => 'Sort Order'
        ];
        $columns['is_recommend'] = [
            'type' => 'select',
            'label' => 'Is Recommend',
            'options' => [
                1 => 'Yes',
                0 => 'No'
            ]
        ];
        $columns['status'] = [
            'type' => 'select',
            'label' => 'Status',
            'options' => [
                1 => 'Enabled',
                0 => 'Disabled'
            ],
            'required' => 'required'
        ];
        $columns['free_shipping'] = [
            'type' => 'hidden',
            'attrs' => ['id' => 'choosen_free_shipping']
        ];
        $columns['weight'] = [
            'type' => 'hidden',
            'attrs' => ['id' => 'choosen_weight']
        ];
        $columns['warehouse_id'] = [
            'type' => 'hidden',
            'attrs' => ['vale' => 1, 'id' => 'choosen_warehouse_id']
        ];
        return parent::prepareElements($columns);
    }

    public function getImages()
    {
        if ($this->getVariable('model')) {
            return $this->getVariable('model')->getImages();
        } else {
            return [];
        }
    }

    public function getThumbnail()
    {
        if ($this->getVariable('model')) {
            return $this->getVariable('model')->getThumbnail();
        } else {
            return [];
        }
    }

    public function getImageOnly()
    {
        return true;
    }
}
