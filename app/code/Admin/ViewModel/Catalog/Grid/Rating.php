<?php

namespace Redseanet\Admin\ViewModel\Catalog\Grid;

use Redseanet\Admin\ViewModel\Grid as PGrid;
use Redseanet\Catalog\Model\Collection\Product\Rating as Collection;
use Redseanet\Lib\Session\Segment;
use Redseanet\Admin\Model\User;

class Rating extends PGrid
{
    protected $action = [
        'getEditAction' => 'Admin\\Catalog\\Product\\Rating::edit',
        'getDeleteAction' => 'Admin\\Catalog\\Product\\Rating::delete'
    ];
    protected $translateDomain = 'rating';

    public function getEditAction($item)
    {
        return '<a href="' . $this->getAdminUrl(':ADMIN/catalog_product_rating/edit/?id=') . $item['id'] . '"title="'
                . $this->translate('Edit') . '"><span class="fa fa-fw fa-file-text-o" aria-hidden="true"></span>'
                . '<span class="sr-only">' . $this->translate('Edit') . '</span></a>';
    }

    public function getDeleteAction($item)
    {
        return '<a href="' . $this->getAdminUrl(':ADMIN/catalog_product_rating/delete/') . '" data-method="delete" data-params="id=' . $item['id'] .
                '&csrf=' . $this->getCsrfKey() . '" title="' . $this->translate('Delete') .
                '"><span class="fa fa-fw fa-remove" aria-hidden="true"></span><span class="sr-only">' .
                $this->translate('Delete') . '</span></a>';
    }

    protected function prepareColumns($columns = [])
    {
        return [
            'id' => [
                'label' => 'ID',
                'type' => 'hidden'
            ],
            'type' => [
                'label' => 'Type',
                'type' => 'select',
                'options' => [
                    'Product', 'Order'
                ]
            ],
            'title' => [
                'type' => 'text',
                'label' => 'Title'
            ],
            'status' => [
                'type' => 'select',
                'label' => 'Status',
                'options' => [
                    1 => 'Enabled',
                    0 => 'Disabled'
                ],
            ]
        ];
    }

    protected function prepareCollection($collection = null)
    {
        $collection = new Collection();
        $userArray = (new Segment('admin'))->get('user');
        $user = new User();
        $user->load($userArray['id']);
        if ($user->getStore()) {
            $collection->where(['store_id' => $user->getStore()->getId()]);
        }
        if (!$this->getQuery('desc')) {
            $this->query['desc'] = 'created_at';
        }
        return parent::prepareCollection($collection);
    }
}
