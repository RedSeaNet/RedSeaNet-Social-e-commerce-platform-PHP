<?php

namespace Redseanet\Log\Model;

use Redseanet\Lib\Model\AbstractModel;

class SocialMedia extends AbstractModel
{
    protected function construct()
    {
        $this->init('social_media_share', 'id', ['id', 'customer_id', 'media_id', 'product_id']);
    }
}
