<?php

namespace Redseanet\Admin\ViewModel\Email;

use Redseanet\Admin\ViewModel\Grid as PGrid;
use Redseanet\Email\Model\Collection\Template as Collection;

class Grid extends PGrid
{
    protected $action = [
        'getEditAction' => 'Admin\\Email\\Template::edit',
        'getSendAction' => 'Admin\\Email\\Queue::schedule',
        'getDeleteAction' => 'Admin\\Email\\Template::delete'
    ];
    protected $translateDomain = 'email';

    public function getEditAction($item)
    {
        return '<a href="' . $this->getAdminUrl(':ADMIN/email_template/edit/?id=') . $item['id'] . '" title="' . $this->translate('Edit') .
                '"><span class="fa fa-fw fa-file-text-o" aria-hidden="true"></span><span class="sr-only">' .
                $this->translate('Edit') . '</span></a>';
    }

    public function getDeleteAction($item)
    {
        return '<a href="' . $this->getAdminUrl(':ADMIN/email_template/delete/') . '" data-method="delete" data-params="id=' . $item['id'] .
                '&csrf=' . $this->getCsrfKey() . '" title="' . $this->translate('Delete') .
                '"><span class="fa fa-fw fa-remove" aria-hidden="true"></span><span class="sr-only">' .
                $this->translate('Delete') . '</span></a>';
    }

    public function getSendAction($item)
    {
        return '<button type="button" class="btn" data-id="' .
                $item['id'] .
                '" data-toggle="modal" data-target="#modal-send-email" title="' .
                $this->translate('Send') .
                '"><span class="fa fa-fw fa-paper-plane" aria-hidden="true"></span><span class="sr-only">' .
                $this->translate('Send') . '</span></button>';
    }

    protected function prepareColumns()
    {
        return [
            'id' => [
                'label' => 'ID',
            ],
            'code' => [
                'label' => 'Code',
            ],
            'subject' => [
                'label' => 'Subject',
                'class' => 'text-left'
            ],
            'language' => [
                'label' => 'Language',
                'use4sort' => false,
                'use4filter' => false
            ],
            'status' => [
                'label' => 'Status',
                'sortby' => 'status',
                'type' => 'select',
                'options' => [
                    'Disabled',
                    'Enabled'
                ]
            ]
        ];
    }

    protected function prepareCollection($collection = null)
    {
        if (!$this->getQuery('desc')) {
            $this->query['desc'] = 'created_at';
        }
        return parent::prepareCollection(new Collection());
    }
}
