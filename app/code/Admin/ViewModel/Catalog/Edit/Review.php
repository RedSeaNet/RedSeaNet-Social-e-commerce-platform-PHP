<?php

namespace Redseanet\Admin\ViewModel\Catalog\Edit;

use Redseanet\Admin\ViewModel\Edit as PEdit;
use Redseanet\Catalog\Model\Collection\Product\Rating as RatingCollection;
use Redseanet\Catalog\Source\Product;
use Redseanet\Customer\Source\Customer;
use Redseanet\Lib\Source\Language;

class Review extends PEdit
{
    public function getSaveUrl()
    {
        return $this->getAdminUrl('catalog_product_review/save/');
    }

    public function getDeleteUrl()
    {
        $model = $this->getVariable('model');
        if ($model && $model->getId()) {
            return $this->getAdminUrl('catalog_product_review/delete/');
        }
        return false;
    }

    public function getTitle()
    {
        return $this->getQuery('id') ? 'Edit Review' : 'Add New Review';
    }

    protected function prepareElements($columns = [])
    {
        $columns = [
            'id' => [
                'label' => 'ID',
                'type' => 'hidden'
            ],
            'csrf' => [
                'type' => 'csrf'
            ],
            'product_id' => [
                'type' => 'select',
                'label' => 'Product',
                'required' => 'required',
                'options' => (new Product())->getSourceArray()
            ],
            'customer_id' => [
                'type' => 'select',
                'label' => 'Customer',
                'options' => (new Customer())->getSourceArray()
            ],
            'language_id' => [
                'type' => 'select',
                'label' => 'Language',
                'options' => (new Language())->getSourceArray()
            ],
            'order_id' => [
                'type' => 'text',
                'label' => 'Order'
            ]
        ];
        $collection = new RatingCollection();
        $model = $this->getVariable('model');
        if ($model && $model->getId()) {
            $collection->join('review_rating', 'review_rating.rating_id=rating.id', ['value'], 'left')
                    ->where(['review_rating.review_id' => $model->getId()]);
        }
        foreach ($collection as $item) {
            $column = [
                'type' => 'radio',
                'label' => $item['title'],
                'options' => [
                    1 => '1',
                    2 => '2',
                    3 => '3',
                    4 => '4',
                    5 => '5'
                ]
            ];
            if (isset($item['value'])) {
                $column['value'] = (string) (float) $item['value'];
            }
            $columns['rating[' . $item['id'] . ']'] = $column;
        }
        $columns += [
            'subject' => [
                'type' => 'text',
                'label' => 'Subject'
            ],
            'content' => [
                'type' => 'textarea',
                'label' => 'Content'
            ],
            'reply' => [
                'type' => 'textarea',
                'label' => 'Reply'
            ],
            'status' => [
                'type' => 'select',
                'label' => 'Status',
                'required' => 'required',
                'options' => [
                    1 => 'Enabled',
                    0 => 'Disabled'
                ]
            ]
        ];
        return parent::prepareElements($columns);
    }
}
