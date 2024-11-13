<?php

namespace Redseanet\Forum\Controller;

use Redseanet\Lib\Controller\ActionController;

class PostListController extends ActionController
{
    public function listAction()
    {
        $root = $this->getLayout('forum_post_list');
        return $root;
    }
}
