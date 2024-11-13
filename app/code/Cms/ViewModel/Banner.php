<?php

namespace Redseanet\Cms\ViewModel;

use Redseanet\Lib\ViewModel\Template;
use Redseanet\Banner\Model\Collection\Banner as bannerCollection;
use Redseanet\Lib\Bootstrap;

class Banner extends Template
{
    protected $banners = null;

    public function __construct()
    {
        $this->setTemplate('cms/banner');
    }

    public function getBanners($code, $limit = 8)
    {
        if (is_null($this->banners)) {
            if (Bootstrap::isMobile()) {
                $collection = new bannerCollection();
                $collection->where("banner.status=1 and banner.code='" . $code . 'mobile' . "' and banner_language.language_id=" . Bootstrap::getLanguage()->getId());
                $collection->order('banner.sort_order desc');
                $collection->limit($limit);
                $banners = $collection->load(true, true);
                if (count($banners) > 0) {
                    $this->banners = $banners;
                } else {
                    $collection = new bannerCollection();
                    $collection->where("banner.status=1 and banner.code='" . $code . "' and banner_language.language_id=" . Bootstrap::getLanguage()->getId());
                    $collection->order('banner.sort_order desc');
                    $collection->limit($limit);
                    $this->banners = $collection->load(true, true);
                }
            } else {
                $collection = new bannerCollection();
                $collection->where("banner.status=1 and banner.code='" . $code . "' and banner_language.language_id=" . Bootstrap::getLanguage()->getId());
                $collection->order('banner.sort_order desc');
                $collection->limit($limit);
                $this->banners = $collection->load(true, true);
            }
        }
        return $this->banners;
    }

    public function getLanguageId()
    {
        return Bootstrap::getLanguage()->getId();
    }
}
