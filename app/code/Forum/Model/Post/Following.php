<?php

namespace Redseanet\Forum\Model\Post;

use Redseanet\Lib\Model\AbstractModel;
use Laminas\Db\Sql\Expression;

class Following extends AbstractModel
{
    use \Redseanet\Lib\Traits\Url;

    protected $post_id = null;

    protected function construct()
    {
        $this->init('forum_customer_like', 'id', ['id', 'customer_id', 'like_customer_id']);
    }

    public function getThumbnail()
    {
        $images = json_decode($this->storage['images']);
        if (is_array($images) && !empty($images)) {
            return $images[0];
        } else {
            return '';
        }
    }

    public function getThumbnails()
    {
        $images = json_decode($this->storage['images']);
        if (is_array($images) && !empty($images)) {
            return $images;
        } else {
            return [];
        }
    }
}
