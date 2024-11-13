<?php

namespace Redseanet\Admin\ViewModel\I18n\Grid;

use Redseanet\Admin\ViewModel\Grid as PGrid;
use Redseanet\Lib\Model\Collection\Language as Collection;
use Redseanet\Lib\Session\Segment;
use Redseanet\Admin\Model\User;
use Redseanet\Lib\Source\Merchant;

class Language extends PGrid
{
    protected $action = [
        'getEditAction' => 'Admin\\I18n\\Language::edit',
        'getDeleteAction' => 'Admin\\I18n\\Language::delete'
    ];

    public function getEditAction($item)
    {
        return '<a href="' . $this->getAdminUrl(':ADMIN/i18n_language/edit/?id=') . $item['id'] . '" title="' . $this->translate('Edit') .
                '"><span class="fa fa-fw fa-file-text-o" aria-hidden="true"></span><span class="sr-only">' .
                $this->translate('Edit') . '</span></a>';
    }

    public function getDeleteAction($item)
    {
        return '<a href="' . $this->getAdminUrl(':ADMIN/i18n_language/delete/') . '" data-method="delete" data-params="id=' . $item['id'] .
                '&csrf=' . $this->getCsrfKey() . '" title="' . $this->translate('Delete') .
                '"><span class="fa fa-fw fa-remove" aria-hidden="true"></span><span class="sr-only">' .
                $this->translate('Delete') . '</span></a>';
    }

    protected function prepareColumns()
    {
        return [
            'id' => [
                'label' => 'ID',
                'type' => 'number'
            ],
            'merchant_id' => [
                'type' => 'select',
                'label' => 'Merchant',
                'options' => (new Merchant())->getSourceArray()
            ],
            'code' => [
                'type' => 'text',
                'label' => 'Code'
            ],
            'name' => [
                'type' => 'text',
                'label' => 'Name'
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
                ]
            ]
        ];
    }

    protected function prepareCollection($collection = null)
    {
        $collection = new Collection();
        return $collection->load(true, true);
    }

    public function getUser()
    {
        $segment = new Segment('admin');
        $userArray = $segment->get('user');
        $user = new User();
        $user->load($userArray['id']);
        return $user;
    }
}
