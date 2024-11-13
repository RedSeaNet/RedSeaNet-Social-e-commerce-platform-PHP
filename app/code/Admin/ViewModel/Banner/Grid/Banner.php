<?php

namespace Redseanet\Admin\ViewModel\Banner\Grid;

use Redseanet\Admin\ViewModel\Grid as PGrid;
use Redseanet\Banner\Model\Collection\Banner as Collection;
use Redseanet\Lib\Session\Segment;
use Redseanet\Admin\Model\User;
use Redseanet\Lib\Bootstrap;

class Banner extends PGrid
{
    use \Redseanet\Lib\Traits\Url;

    protected $translateDomain = 'banner';
    protected $action = [
        'getEditAction' => 'Admin\\Cms\\Banner::edit',
        'getDeleteAction' => 'Admin\\Cms\\Banner::delete'
    ];

    public function getEditAction($item)
    {
        return '<a href="' . $this->getAdminUrl(':ADMIN/banner/edit/?id=') . $item['id'] . '" title="' . $this->translate('Edit') .
                '"><span class="fa fa-fw fa-file-text-o" aria-hidden="true"></span><span class="sr-only">' .
                $this->translate('Edit') . '</span></a>';
    }

    public function getDeleteAction($item)
    {
        return '<a href="' . $this->getAdminUrl(':ADMIN/banner/delete/') . '" data-method="delete" data-params="id=' . $item['id'] .
                '&csrf=' . $this->getCsrfKey() . '" title="' . $this->translate('Delete') .
                '"><span class="fa fa-fw fa-remove" aria-hidden="true"></span><span class="sr-only">' .
                $this->translate('Delete') . '</span></a>';
    }

    protected function prepareColumns()
    {
        return [
            'id' => [
                'label' => 'ID',
            ],
            'code' => [
                'label' => 'Identifier',
                'class' => 'text-left'
            ],
            'image' => [
                'label' => 'Image',
                'use4sort' => false,
                'use4filter' => false,
                'handler' => function ($value) {
                    if (empty($value)) {
                        return '';
                    } else {
                        return '<img src="' . $this->getResourceUrl('image/' . $value) . '" style="max-height:80px;">';
                    }
                }
            ],
            'status' => [
                'label' => 'Status',
                'sortby' => 'banner:status',
                'type' => 'select',
                'options' => [
                    'Disabled',
                    'Enabled'
                ]
            ],
            'sort_order' => [
                'label' => 'Sort Order'
            ],
        ];
    }

    protected function prepareCollection($collection = null)
    {
        $userArray = (new Segment('admin'))->get('user');
        $user = new User();
        $user->load($userArray['id']);
        $collection = new Collection();

        if ($user->getStore()) {
            $collection->where(['store_id' => $user->getStore()->getId()]);
        }
        if (!$this->getQuery('desc')) {
            $this->query['desc'] = 'created_at';
        }
        $collection->where(['banner_language.language_id' => Bootstrap::getLanguage()['id']]);
        return parent::prepareCollection($collection);
    }
}
