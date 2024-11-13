<?php

namespace Redseanet\Admin\ViewModel\Api\Grid;

use Redseanet\Admin\ViewModel\Grid as PGrid;
use Redseanet\Oauth\Model\Collection\Token as Collection;

class Token extends PGrid
{
    protected $action = [
        'getRevokeAction' => 'Admin\\Api\\Oauth\\Token::revoke',
        'getDeleteAction' => 'Admin\\Api\\Oauth\\Token::delete'
    ];
    protected $messAction = [
        'getMessGrantAction' => 'Admin\\Api\\Oauth\\Token::grant',
        'getMessRevokeAction' => 'Admin\\Api\\Oauth\\Token::revoke',
        'getMessDeleteAction' => 'Admin\\Api\\Oauth\\Token::delete'
    ];
    protected $translateDomain = 'api';

    public function getRevokeAction($item)
    {
        if ($item['status']) {
            return '<a href="' . $this->getAdminUrl(':ADMIN/api_oauth_token/revoke/') . '" data-method="post" data-params="id=' . $item['id'] .
                    '&csrf=' . $this->getCsrfKey() . '" title="' . $this->translate('Revoke') .
                    '"><span class="fa fa-fw fa-user-times" aria-hidden="true"></span><span class="sr-only">' .
                    $this->translate('Revoke') . '</span></a>';
        } else {
            return '<a href="' . $this->getAdminUrl(':ADMIN/api_oauth_token/grant/') . '" data-method="post" data-params="id=' . $item['id'] .
                    '&csrf=' . $this->getCsrfKey() . '" title="' . $this->translate('Grant') .
                    '"><span class="fa fa-fw fa-user-plus" aria-hidden="true"></span><span class="sr-only">' .
                    $this->translate('Grant') . '</span></a>';
        }
    }

    public function getDeleteAction($item)
    {
        return '<a href="' . $this->getAdminUrl(':ADMIN/api_oauth_token/delete/') . '" data-method="delete" data-params="id=' . $item['id'] .
                '&csrf=' . $this->getCsrfKey() . '" title="' . $this->translate('Delete') .
                '"><span class="fa fa-fw fa-remove" aria-hidden="true"></span><span class="sr-only">' .
                $this->translate('Delete') . '</span></a>';
    }

    public function getMessDeleteAction()
    {
        return '<a href="' . $this->getAdminUrl(':ADMIN/api_oauth_token/delete/') . '" data-method="delete" data-serialize=".grid .table" title="' . $this->translate('Delete') .
                '"><span>' . $this->translate('Delete') . '</span></a>';
    }

    public function getMessRevokeAction()
    {
        return '<a href="' . $this->getAdminUrl(':ADMIN/api_oauth_token/revoke/') . '" data-method="post" data-serialize=".grid .table" title="' . $this->translate('Revoke') .
                '"><span>' . $this->translate('Revoke') . '</span></a>';
    }

    public function getMessGrantAction()
    {
        return '<a href="' . $this->getAdminUrl(':ADMIN/api_oauth_token/grant/') . '" data-method="post" data-serialize=".grid .table" title="' . $this->translate('Grant') .
                '"><span>' . $this->translate('Grant') . '</span></a>';
    }

    protected function prepareColumns()
    {
        return [
            'name' => [
                'label' => 'Consumer'
            ],
            'open_id' => [
                'label' => 'Username'
            ],
            'customer_id' => [
                'label' => 'Customer ID'
            ],
            'admin_id' => [
                'label' => 'Admin ID'
            ],
            'status' => [
                'label' => 'Status',
                'type' => 'select',
                'options' => [
                    'Revoked', 'Granted'
                ]
            ]
        ];
    }

    protected function prepareCollection($collection = null)
    {
        $collection = new Collection();
        $collection->join('oauth_consumer', 'oauth_consumer.id=oauth_token.consumer_id', ['name'], 'left');
        if (!$this->getQuery('desc')) {
            $this->query['desc'] = 'oauth_token.created_at';
        }
        return parent::prepareCollection($collection);
    }
}
