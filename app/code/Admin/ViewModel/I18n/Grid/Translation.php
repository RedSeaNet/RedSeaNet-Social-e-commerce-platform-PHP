<?php

namespace Redseanet\Admin\ViewModel\I18n\Grid;

use Redseanet\Admin\ViewModel\Grid as PGrid;
use Redseanet\I18n\Source\Locale;
use Redseanet\I18n\Model\Collection\Translation as Collection;

class Translation extends PGrid
{
    protected $action = [
        'getEditAction' => 'Admin\\I18n\\Translation::edit',
        'getDeleteAction' => 'Admin\\I18n\\Translation::delete'
    ];

    public function getEditAction($item)
    {
        return '<a href="' . $this->getAdminUrl(':ADMIN/i18n_translation/edit/?id=') . $item['id'] . '" title="' . $this->translate('Edit') .
                '"><span class="fa fa-fw fa-file-text-o" aria-hidden="true"></span><span class="sr-only">' .
                $this->translate('Edit') . '</span></a>';
    }

    public function getDeleteAction($item)
    {
        return '<a href="' . $this->getAdminUrl(':ADMIN/i18n_translation/delete/') . '" data-method="delete" data-params="id=' .
                $item['id'] . '&csrf=' . $this->getCsrfKey() . '" title="' . $this->translate('Delete') .
                '"><span class="fa fa-fw fa-remove" aria-hidden="true"></span><span class="sr-only">' .
                $this->translate('Delete') . '</span></a>';
    }

    protected function prepareColumns()
    {
        return [
            'locale' => [
                'type' => 'select',
                'label' => 'Locale',
                'options' => (new Locale())->getSourceArray()
            ],
            'string' => [
                'label' => 'Original'
            ],
            'translate' => [
                'label' => 'Translated'
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
        if (!$this->getQuery('asc') && !$this->getQuery('desc')) {
            $this->query['asc'] = 'string';
        }
        return parent::prepareCollection(new Collection());
    }
}
