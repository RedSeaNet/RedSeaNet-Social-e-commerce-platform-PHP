<?php

namespace Redseanet\Admin\ViewModel\Forum\Grid;

use Redseanet\Admin\ViewModel\Grid;
use Redseanet\Customer\Model\Customer;
use Redseanet\Forum\Model\Collection\Post as Collection;
use Redseanet\Forum\Model\Post as postModel;
use Redseanet\Forum\Source\Category;
use Redseanet\Lib\Source\Language;
use Laminas\Db\Sql\Expression;
use Laminas\Db\Sql\Select;
use Laminas\Db\Sql\Where;

class Statistics extends Grid
{
    protected $action = [
        'getDeleteAction' => 'Admin\\Forum\\Post::delete',
    ];
    protected $messAction = [
        'getMessDeleteAction' => 'Admin\\Forum\\Post::delete',
    ];
    protected $translateDomain = 'forum';

    public function getDeleteAction($item)
    {
        return <<<HTML
        <a href="{$this->getAdminUrl(':ADMIN/forum_post/delete/')}" 
            data-method="delete" 
            data-params="id={$item['id']}&csrf={$this->getCsrfKey()}" 
            title="{$this->translate('Delete')}">
            <span class="fa fa-fw fa-remove" aria-hidden="true">
            </span>
            <span class="sr-only">
                {$this->translate('Delete')}
            </span>
        </a>
HTML;
    }

    public function getMessDeleteAction()
    {
        return <<<HTML
            <a href="{$this->getAdminUrl(':ADMIN/forum_post/delete/')}"
                data-method="delete"
                data-serialize=".grid .table"
                title="{$this->translate('Delete')}">
                {$this->translate('Delete')}
            </a>
HTML;
    }

    public function getMessCloseAction()
    {
        return <<<HTML
        <a href="{$this->getAdminUrl(':ADMIN/forum_post/close/')}"
            data-method="post"
            data-serialize=".grid .table"
            title="{$this->translate('Close')}">
            {$this->translate('Close')}
        </a>
HTML;
    }

    protected function prepareColumns($columns = [])
    {
        $postModelObject = new postModel();

        return [
            'id' => [
                'type' => 'hidden',
                'label' => 'ID',
            ],
            'title' => [
                'type' => 'text',
                'label' => 'Title',
            ],
            'category_id' => [
                'type' => 'selecttree',
                'label' => 'Category',
                'use4sort' => false,
                'use4filter' => true,
                'options' => (new Category())->getSourceArrayTree(),
                'empty_string' => 'Please choose category',
            ],
            'customer_id' => [
                'label' => 'Customer ID',
                'handler' => function ($id, &$item) {
                    $model = new Customer();
                    $model->load($id);
                    $item['username'] = $this->hasPermission('Admin\Customer\Manage::edit') ? '<a href="' . $this->getAdminUrl('customer_manage/edit/?id=') . $id . '">' . $model->offsetGet('username') . '</a>' : $model->offsetGet('username');

                    return $id;
                },
            ],
            'username' => [
                'label' => 'Customer',
                'type' => 'text',
                'use4sort' => false,
                'use4filter' => true,
            ],
            'tags' => [
                'label' => 'Tags',
                'type' => 'text',
                'use4sort' => false,
                'use4filter' => true,
            ],
            'liked' => [
                'label' => 'Like',
                'type' => 'daterange',
                'use4sort' => true,
                'use4filter' => false
            ],
            'reviewed' => [
                'label' => 'Review',
                'type' => 'text',
                'use4sort' => true,
                'use4filter' => false,
            ],
            'favorited' => [
                'label' => 'Favorites',
                'type' => 'text',
                'use4sort' => true,
                'use4filter' => false,
            ],
            'views' => [
                'label' => 'Views',
                'use4sort' => true,
                'use4filter' => false,
            ],
            'created_at' => [
                'label' => 'Created at',
                'type' => 'daterange',
                'attrs' => [
                    'data-toggle' => 'datepicker',
                ],
            ],
            'updated_at' => [
                'label' => 'Updated at',
                'type' => 'daterange',
                'use4sort' => true,
                'use4filter' => false,
                'attrs' => [
                    'data-toggle' => 'datepicker',
                ],
            ],
        ];
    }

    protected function prepareCollection($collection = null)
    {
        $collection = new Collection();
        $liked = new Select('forum_like');
        $liked->columns(['count' => new Expression('count(1)')])
                ->group('post_id')
        ->where->equalTo('post_id', 'forum_post.id', 'identifier', 'identifier');

        $reviewed = new Select('forum_post_review');
        $reviewed->columns(['count' => new Expression('count(1)')])
                ->group('post_id')
        ->where->equalTo('post_id', 'forum_post.id', 'identifier', 'identifier');

        $favorited = new Select('forum_post_favorite');
        $favorited->columns(['count' => new Expression('count(1)')])
                ->group('post_id')
        ->where->equalTo('post_id', 'forum_post.id', 'identifier', 'identifier');

        $views = new Select('log_visitor');
        $views->columns(['count' => new Expression('count(1)')])
                ->group('post_id')
        ->where->equalTo('post_id', 'forum_post.id', 'identifier', 'identifier');

        $collection->columns(['*', 'views' => $views, 'liked' => $liked, 'reviewed' => $reviewed, 'favorited' => $favorited]);
        $collection->join('customer_1_index', 'customer_id=customer_1_index.id', ['username'], 'left');

        $condition = $this->getQuery();
        $select = $collection instanceof AbstractCollection ? $collection->getSelect() : $collection;
        if (!isset($skip['limit'])) {
            if (isset($condition['limit']) && 'all' === $condition['limit']) {
                $select->reset('limit')->reset('offset');
            } else {
                $limit = $condition['limit'] ?? 20;
                if (isset($condition['page'])) {
                    $select->offset(($condition['page'] > 0 ? ($condition['page'] - 1) : 0) * $limit);
                    unset($condition['page']);
                }
                $select->limit((int) $limit);
            }
        }
        if (!isset($skip['order'])) {
            if (isset($condition['asc'])) {
                $select->order((strpos($condition['asc'], ':') ?
                                str_replace(':', '.', $condition['asc']) :
                                $condition['asc']) . ' ASC');
            } elseif (isset($condition['desc'])) {
                $select->order((strpos($condition['desc'], ':') ?
                                str_replace(':', '.', $condition['desc']) :
                                $condition['desc']) . ' DESC');
            }
        }

        if (isset($condition['title']) && $condition['title'] != '') {
            $select->where('forum_post.title LIKE \'%' . $condition['title'] . '%\'');
        }
        if (isset($condition['tags']) && $condition['tags'] != '') {
            $select->where('forum_post.tags LIKE \'%' . $condition['tags'] . '%\'');
        }
        if (isset($condition['category_id']) && $condition['category_id'] != '') {
            $select->where('forum_post.category_id =' . intval($condition['category_id']));
        }
        if (isset($condition['customer_id']) && $condition['customer_id'] != '') {
            $select->where('forum_post.customer_id =' . intval($condition['customer_id']));
        }
        if (isset($condition['is_top']) && $condition['is_top'] != '') {
            $select->where('forum_post.is_top =' . intval($condition['is_top']));
        }
        if (isset($condition['is_hot']) && $condition['is_hot'] != '') {
            $select->where('forum_post.is_hot =' . intval($condition['is_hot']));
        }
        if (isset($condition['status']) && $condition['status'] != '') {
            $select->where('forum_post.status =' . intval($condition['status']));
        }
        if (isset($condition['status']) && $condition['status'] != '') {
            $select->where('forum_post.status =' . intval($condition['status']));
        }
        if (isset($condition['username']) && $condition['username'] != '') {
            $select->where('customer_1_index.username=\'' . $condition['username'] . '\'');
        }

        if (isset($condition['created_at']['lte']) && $condition['created_at']['lte'] != '' && isset($condition['created_at']['gte']) && $condition['created_at']['gte'] != '') {
            $select->where('1=1')->where->lessThan('forum_post.created_at', $condition['created_at']['lte'])->greaterThanOrEqualTo('forum_post.created_at', $condition['created_at']['gte']);
        }
        if (isset($condition['liked']['lte']) && $condition['liked']['lte'] != '' && isset($condition['liked']['gte']) && $condition['liked']['gte'] != '') {
            $liked->where('1=1')->where->lessThan('forum_like.created_at', $condition['liked']['lte'])->greaterThanOrEqualTo('forum_like.created_at', $condition['liked']['gte']);
        }

        return $collection;
    }
}
