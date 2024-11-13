<?php

namespace Redseanet\Admin\Controller;

use DOMDocument;
use DOMXPath;
use Redseanet\Banner\Model\Banner;
use Redseanet\Lib\Controller\AuthActionController;
use Redseanet\Lib\ViewModel\Template;
use Laminas\Db\Sql\Expression;
use Laminas\Stdlib\SplQueue;
use Redseanet\Banner\Model\Collection\Banner as Collection;
use Redseanet\Lib\Session\Segment;
use Redseanet\Lib\Bootstrap;

class BannerController extends AuthActionController
{
    use \Redseanet\Lib\Traits\DB;

    use \Redseanet\Lib\Traits\DataCache;

    use \Redseanet\Admin\Traits\Stat;

    public function indexAction()
    {
        $root = $this->getLayout('admin_banner_list');
        return $root;
    }

    public function editAction()
    {
        $root = $this->getLayout('admin_banner_edit');
        if ($id = $this->getRequest()->getQuery('id')) {
            $model = new Banner();
            $model->load($id);
            $root->getChild('edit', true)->setVariable('model', $model);
            $root->getChild('head')->setTitle('Edit Banner / Banner');
        } else {
            $root->getChild('head')->setTitle('Add New Banner / Banner');
        }
        return $root;
    }

    public function saveAction()
    {
        $response = $this->doSave('\\Redseanet\\Banner\\Model\\Banner', ':ADMIN/banner/', ['language_id', 'code', 'title']);
        return $response;
    }
}
