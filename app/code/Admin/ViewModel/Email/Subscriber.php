<?php

namespace Redseanet\Admin\ViewModel\Email;

use Redseanet\Admin\ViewModel\Grid;
use Redseanet\Email\Model\Collection\Subscriber as Collection;

class Subscriber extends Grid
{
    protected $action = ['getDeleteAction' => 'Admin\\Email\\Subscriber::delete'];
    protected $translateDomain = 'email';

    public function getDeleteAction($item)
    {
        return '<a href="' . $this->getAdminUrl(':ADMIN/email_subscriber/delete/') . '" data-method="delete" data-params="id=' . $item['id'] .
                '&csrf=' . $this->getCsrfKey() . '" title="' . $this->translate('Delete') .
                '"><span class="fa fa-fw fa-remove" aria-hidden="true"></span><span class="sr-only">' .
                $this->translate('Delete') . '</span></a>';
    }

    protected function prepareColumns()
    {
        return [
            'email' => [
                'label' => 'Email',
            ],
            'status' => [
                'label' => 'Status',
                'sortby' => 'status',
                'type' => 'select',
                'options' => [
                    'Unsubscribed',
                    'Subscribed'
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
