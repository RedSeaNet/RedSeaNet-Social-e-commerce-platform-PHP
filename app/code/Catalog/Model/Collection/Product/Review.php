<?php

namespace Redseanet\Catalog\Model\Collection\Product;

use Redseanet\Lib\Model\AbstractCollection;

class Review extends AbstractCollection
{
    protected function construct()
    {
        $this->init('review');
    }

    protected function afterLoad(&$result)
    {
        foreach ($result as &$item) {
            $content = @gzdecode($item['content']);
            if ($content !== false) {
                $item['content'] = $content;
            }
            $reply = @gzdecode($item['reply']);
            if ($reply !== false) {
                $item['reply'] = $reply;
            }
        }
        return parent::afterLoad($result);
    }
}
