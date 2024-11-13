<?php

namespace Redseanet\Customer\Model;

use Redseanet\Catalog\Model\Product;
use Redseanet\Lib\Model\AbstractModel;

class Media extends AbstractModel
{
    use \Redseanet\Lib\Traits\Url;

    protected function construct()
    {
        $this->init('social_media', 'id', ['id', 'label', 'link', 'icon']);
    }

    public function getUrl($replace = [], $product = false)
    {
        if (!empty($this->storage['link'])) {
            if (!empty($product)) {
                if (is_numeric($product)) {
                    $product = (new Product())->load($product);
                }
                $thumbnail = $product->getThumbnail();
                $replace['{image}'] = rawurlencode(strpos($thumbnail, '//') === false ? $this->getResourceUrl('image/' . $thumbnail) : $thumbnail);
                $replace['{title}'] = rawurlencode($product['name']);
                preg_match_all('/\{([^\}]+)\}/', $this->storage['link'], $matches);
                foreach ($matches[1] as $attr) {
                    if (!isset($replace['{' . $attr . '}'])) {
                        $value = $product->offsetGet($attr);
                        if (is_scalar($value)) {
                            $replace['{' . $attr . '}'] = rawurlencode($value);
                        }
                    }
                }
            }
            return empty($replace) ?
                    preg_replace('/\{([^\}]+)\}/', '', $this->storage['link']) :
                    str_replace(array_keys($replace), array_values($replace), $this->storage['link']);
        }
        return '/';
    }
}
