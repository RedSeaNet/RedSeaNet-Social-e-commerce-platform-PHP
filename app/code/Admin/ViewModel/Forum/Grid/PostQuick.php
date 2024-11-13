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

class PostQuick extends Grid
{
    protected $action = [
    ];
    protected $messAction = [

    ];
    protected $translateDomain = 'forum';

    protected function prepareColumns($columns = [])
    {
        $postModelObject = new postModel();

        return [
            'id' => [
                'type' => 'hidden',
                'label' => 'ID',
                'handler' => function ($id, &$item) {
                    $model = new Customer();
                    $model->load($item['customer_id']);
                    $item['username'] = $this->hasPermission('Admin\Customer\Manage::edit') ? '<a href="' . $this->getAdminUrl('customer_manage/edit/?id=') . $item['customer_id'] . '">' . $model->offsetGet('username') . '</a>' : $model->offsetGet('username');
                    return $id;
                },
            ],
            'title' => [
                'type' => 'text',
                'label' => 'Title',
                'editable' => true,
                'comment' => '不要输入中文逗号和空格',
                'attrs' => [
                    'data-href' => $this->getAdminUrl(':ADMIN/forum_post/quicksave/'),
                    'data-method' => 'post',
                    'data-params' => 'csrf=' . $this->getCsrfKey() . '&column=title',
                ]
            ],
            //            'category_id' => [
            //                'type' => 'selecttree',
            //                'label' => 'Category',
            //                'use4sort' => false,
            //                'use4filter' => true,
            //                'options' => (new Category())->getSourceArrayTree(),
            //                'empty_string' => 'Please choose category',
            //            ],
            'username' => [
                'label' => 'Username',
                'type' => 'text',
                'use4sort' => false,
                'use4filter' => true,
            ],
            'tags' => [
                'label' => 'Hashtags',
                'type' => 'text',
                'use4sort' => false,
                'use4filter' => true,
                'editable' => true,
                'comment' => '多个标签用英文逗号分隔, 标签不要输入特殊符号',
                'attrs' => [
                    'data-href' => $this->getAdminUrl(':ADMIN/forum_post/quicksave/'),
                    'data-method' => 'post',
                    'data-params' => 'csrf=' . $this->getCsrfKey() . '&column=tags',
                ]
            ],
            'status' => [
                'label' => 'Status',
                'type' => 'select',
                'options' => [
                    3 => 'Impeached',
                    2 => 'Edited',
                    1 => 'Approved',
                    0 => 'New',
                    -1 => 'Closed',
                ],
            ],
            'created_at' => [
                'label' => 'Created at',
                'type' => 'daterange',
                'attrs' => [
                    'data-toggle' => 'datepicker',
                ],
            ]
        ];
    }

    protected function prepareCollection($collection = null)
    {
        $collection = new Collection();
        if (!$this->getQuery('desc')) {
            $this->query['desc'] = 'is_top';
        }

        if ($this->getQuery('title')) {
            $collection->where("title like '%" . $this->getQuery('title') . "%'");
            unset($this->query['title']);
        }
        if ($this->getQuery('tags')) {
            $collection->where("tags like '%" . $this->getQuery('tags') . "%'");
            unset($this->query['tags']);
        }
        if ($this->getQuery('username')) {
            $SubSelect = new Select();
            $SubSelect->from('customer_1_index');
            $SubSelect->columns(['id']);
            $SubSelect->where(['username' => $this->query['username']]);
            $collectionSelect = $collection->getSelect();
            $collectionSelect->where->in('customer_id', $SubSelect);
            unset($this->query['username']);
        }
        $views = new Select('log_visitor');
        $views->columns(['count' => new Expression('count(1)')])
                ->group('post_id')
        ->where->equalTo('post_id', 'forum_post.id', 'identifier', 'identifier');

        $username = new Select('customer_1_index');
        $username->columns(['count' => new Expression('count(1)')])
        ->where->equalTo('id', 'forum_post.customer_id', 'identifier', 'identifier');

        $collection->columns(['*', 'username' => $username]);

        return parent::prepareCollection($collection);
    }
}
