<?php

namespace Redseanet\Forum\Controller;

use Redseanet\Forum\Model\Post as postModel;
use Redseanet\Forum\Model\Collection\Post as PostCollection;
use Redseanet\Lib\Controller\ActionController;
use Laminas\Db\Sql\Expression;
use Laminas\Db\Sql\Select;
use Exception;
use Redseanet\Lib\Session\Segment;
use Redseanet\Lib\Bootstrap;

class CategoryController extends ActionController
{
    use \Redseanet\Forum\Traits\Breadcrumb;
    use \Redseanet\Lib\Traits\Filter;

    public function dispatch($request = null, $routeMatch = null)
    {
        $cors = $this->getContainer()->get('config')['adapter']['cors'] ?? [];
        if ($cors && in_array($this->getRequest()->getUri()->getHost(), (array) $cors)) {
            $this->getResponse()->withHeader('Access-Control-Allow-Origin', $this->getRequest()->getUri()->getHost());
        }
        if ($this->getRequest()->isOptions() && $this->getRequest()->getHeader('Access-Control-Request-Method')['Access-Control-Request-Method']) {
            $this->getResponse()->withHeader('Access-Control-Allow-Methods', 'GET, POST');
            return $this->getResponse();
        } else {
            return parent::dispatch($request, $routeMatch);
        }
    }

    public function indexAction()
    {
        if ($this->getRequest()->isXmlHttpRequest() || $this->getRequest()->getQuery('is_json')) {
            $root = $this->getLayout('forum_category_ajax');
            $root->getChild('content', true)->setVariable('category', $this->getOption('category'))->setVariable('is_json', true);
        } else {
            $root = $this->getLayout('forum_category');
            $root->getChild('main', true)->setVariable('category', $this->getOption('category'));
            $root->getChild('list', true)->setVariable('category', $this->getOption('category'));
            $this->generateCrumbs($root->getChild('breadcrumb', true), $this->getOption('category'));
        }
        return $root;
    }

    public function productAction()
    {
        if ($id = $this->getRequest()->getQuery('product_id', false)) {
            $root = $this->getLayout('forum_product');
            $posts = new PostCollection();
            $posts->join('forum_product_relation', 'forum_product_relation.post_id=forum_post.id', [], 'left');
            $posts->where(['forum_post.status' => 1, 'forum_product_relation.product_id' => $id]);
            $root->getChild('content', true)->setVariable('product_id', $id)->setVariable('posts', $posts);
            return $root;
        }
        return $this->notFoundAction();
    }

    public function modalAction()
    {
        if ($this->getRequest()->isXmlHttpRequest() || $this->getRequest()->getQuery('is_json')) {
            $root = $this->getLayout('forum_category_modal_ajax');
            $root->getChild('content', true)->setVariable('category', $this->getOption('category'))->setVariable('is_json', true);
        } else {
            $root = $this->getLayout('forum_category_modal');
            $root->getChild('main', true)->setVariable('category', $this->getOption('category'));
            $root->getChild('list', true)->setVariable('category', $this->getOption('category'));
            $this->generateCrumbs($root->getChild('breadcrumb', true), $this->getOption('category'));
        }
        return $root;
    }
}
