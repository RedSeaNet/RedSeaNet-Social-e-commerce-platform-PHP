<?php

namespace Redseanet\Admin\ViewModel\Forum\Grid;

use Redseanet\Admin\ViewModel\Grid;
use Redseanet\Customer\Model\Customer;
use Redseanet\Forum\Model\Collection\Post as Collection;
use Redseanet\Lib\Source\Language;
use Redseanet\Forum\Model\Post as postModel;
use Redseanet\Forum\Source\Category;
use Laminas\Db\Sql\Select;
use Laminas\Db\Sql\Where;
use Laminas\Db\Sql\Expression;

class Post extends Grid
{
    protected $action = [
        'getReviewAction' => 'Admin\\Forum\\Review::index',
        'getEditAction' => 'Admin\\Forum\\Post::edit',
        'getTopAction' => 'Admin\\Forum\\Post::top',
        'getHotAction' => 'Admin\\Forum\\Post::hot',
        'getCloseAction' => 'Admin\\Forum\\Post::close',
        'getDeleteAction' => 'Admin\\Forum\\Post::delete'
    ];
    protected $messAction = [
        'getMessDeleteAction' => 'Admin\\Forum\\Post::delete',
        'getMessCloseAction' => 'Admin\\Forum\\Post::close'
    ];
    protected $translateDomain = 'forum';

    public function getReviewAction($item)
    {
        return '<a href="' . $this->getAdminUrl(':ADMIN/forum_review/?post_id=') . $item['id'] . '"title="' . $this->translate('View Reviews') .
                '"><span class="fa fa-fw fa-comments-o" aria-hidden="true"></span><span class="sr-only">'
                . $this->translate('View Reviews') . '</span></a>';
    }

    public function getEditAction($item)
    {
        $page = 1;
        if (!empty($this->query['page'])) {
            $page = $this->query['page'];
        }
        return '<a href="' . $this->getAdminUrl(':ADMIN/forum_post/edit/?id=' . $item['id'] . '&page=' . $page) . '"title="' . $this->translate('Edit') .
                '"><span class="fa fa-fw fa-file-text-o" aria-hidden="true"></span><span class="sr-only">'
                . $this->translate('Edit') . '</span></a>';
    }

    public function getDeleteAction($item)
    {
        return '<a href="' . $this->getAdminUrl(':ADMIN/forum_post/delete/') . '" data-method="delete" data-params="id=' . $item['id'] .
                '&csrf=' . $this->getCsrfKey() . '" title="' . $this->translate('Delete') .
                '"><span class="fa fa-fw fa-remove" aria-hidden="true"></span><span class="sr-only">' .
                $this->translate('Delete') . '</span></a>';
    }

    public function getTopAction($item)
    {
        return '<a href="' . $this->getAdminUrl(':ADMIN/forum_post/top/?id=') . $item['id'] . '" title="' . $this->translate('Stick') .
                '"><span class="fa fa-fw fa-step-forward fa-rotate-' . ($item['is_top'] ? 90 : 270) . '" aria-hidden="true"></span><span class="sr-only">' .
                $this->translate('Stick') . '</span></a>';
    }

    public function getHotAction($item)
    {
        return '<a href="' . $this->getAdminUrl(':ADMIN/forum_post/hot/?id=') . $item['id'] . '" title="' . $this->translate('Set as Hot') .
                '"><span class="fa fa-fw fa-' . ($item['is_hot'] ? 'fire-extinguisher' : 'fire') . '" aria-hidden="true"></span><span class="sr-only">' .
                $this->translate('Set as Hot') . '</span></a>';
    }

    public function getCloseAction($item)
    {
        if ($item['status'] >= 0) {
            return '<a href="' . $this->getAdminUrl(':ADMIN/forum_post/close/') .
                    '" data-method="post" data-params="id=' . $item['id'] . '" title="' . $this->translate('Close') .
                    '"><span class="fa fa-fw fa-pause" aria-hidden="true"></span><span class="sr-only">' .
                    $this->translate('Close') . '</span></a>';
        }
        return '';
    }

    public function getMessDeleteAction()
    {
        return '<a href="' . $this->getAdminUrl(':ADMIN/forum_post/delete/') . '" data-method="delete" data-serialize=".grid .table" title="' . $this->translate('Delete') .
                '"><span>' . $this->translate('Delete') . '</span></a>';
    }

    public function getMessCloseAction()
    {
        return '<a href="' . $this->getAdminUrl(':ADMIN/forum_post/close/') . '" data-method="post" data-serialize=".grid .table" title="' . $this->translate('Delete') .
                '"><span>' . $this->translate('Close') . '</span></a>';
    }

    protected function prepareColumns($columns = [])
    {
        $postModelObject = new postModel();
        return [
            'id' => [
                'type' => 'hidden',
                'label' => 'ID'
            ],
            'title' => [
                'type' => 'text',
                'label' => 'Title'
            ],
            'category_id' => [
                'type' => 'selecttree',
                'label' => 'Category',
                'use4sort' => false,
                'use4filter' => true,
                'options' => (new Category())->getSourceArrayTree(),
                'empty_string' => 'Please choose category'
            ],
            'customer_id' => [
                'label' => 'Customer ID',
                'handler' => function ($id) {
                    return $this->hasPermission('Admin\Customer\Manage::edit') ? '<a href="' . $this->getAdminUrl('customer_manage/edit/?id=' . $id) . '">' . $id . '</a>' : $id;
                }
            ],
            'username' => [
                'label' => 'Username',
                'handler' => function ($id, &$item) {
                    return $this->hasPermission('Admin\Customer\Manage::edit') ? '<a href="' . $this->getAdminUrl('customer_manage/edit/?id=') . $item['customer_id'] . '">' . $id . '</a>' : $id;
                }
            ],
            'language_id' => [
                'type' => 'select',
                'label' => 'Language',
                'options' => (new Language())->getSourceArray()
            ],
            'is_top' => [
                'label' => 'Stick',
                'type' => 'select',
                'options' => [
                    1 => 'Yes',
                    0 => 'No'
                ],
                'use4sort' => false,
                'use4filter' => false
            ],
            'is_hot' => [
                'label' => 'Hot',
                'type' => 'select',
                'options' => [
                    1 => 'Yes',
                    0 => 'No'
                ]
            ],
            'like' => [
                'label' => 'Like',
                'type' => 'text',
                'use4sort' => true,
                'use4filter' => false
            ],
            'reviews' => [
                'label' => 'Review',
                'type' => 'text',
                'use4sort' => true,
                'use4filter' => false
            ],
            'favorited' => [
                'label' => 'Favorites',
                'type' => 'text',
                'use4sort' => true,
                'use4filter' => false
            ],
            'status' => [
                'label' => 'Status',
                'type' => 'select',
                'options' => [
                    3 => 'Impeached',
                    2 => 'Edited',
                    1 => 'Approved',
                    0 => 'New',
                    -1 => 'Closed'
                ]
            ],
            'product_id' => [
                'label' => 'Product',
                'use4sort' => false,
                'use4filter' => true
            ],
            'created_at' => [
                'label' => 'Created at',
                'type' => 'daterange',
                'attrs' => [
                    'data-toggle' => 'datepicker'
                ]
            ],
            'updated_at' => [
                'label' => 'Updated at',
                'type' => 'daterange',
                'attrs' => [
                    'data-toggle' => 'datepicker'
                ]
            ],
        ];
    }

    protected function prepareCollection($collection = null)
    {
        $collection = new Collection();
        if (!$this->getQuery('desc')) {
            $this->query['desc'] = 'created_at';
        }
        $favorited = new Select('forum_post_favorite');
        $favorited->columns(['count' => new Expression('count(1)')])->group('post_id')->where->equalTo('post_id', 'forum_post.id', 'identifier', 'identifier');
        $collection->columns(['*', 'favorited' => $favorited]);
        $collection->join('customer_1_index', 'customer_1_index.id=forum_post.customer_id', ['username'], 'left');
        return parent::prepareCollection($collection);
    }
}
