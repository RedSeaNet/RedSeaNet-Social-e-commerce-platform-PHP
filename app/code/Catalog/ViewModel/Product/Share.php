<?php

namespace Redseanet\Catalog\ViewModel\Product;

use Redseanet\Customer\Model\Collection\Media;

class Share extends View
{
    public function getMedia()
    {
        return new Media();
    }

    public function getLink($media)
    {
        return $this->getBaseUrl('catalog/product/share/?media_id=' . $media['id'] . '&product_id=' . $this->getProduct()->getId());
    }

    public function getSharingUrl()
    {
        $uri = $this->getUri()->withFragment('');
        $segment = $this->getSegment('customer');
        if ($segment->get('hasLoggedIn')) {
            $customerArray = $segment->get('customer');
            $uri = $uri->withQuery('referer=' . $customerArray['increment_id']);
        }
        return $uri->__toString();
    }
}
