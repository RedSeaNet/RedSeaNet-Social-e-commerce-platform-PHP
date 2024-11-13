<?php

namespace Redseanet\Cms\ViewModel;

use Exception;
use Redseanet\Lib\Bootstrap;
use Redseanet\Cms\Model\Page as PageModel;
use Redseanet\Lib\ViewModel\AbstractViewModel;

class Page extends AbstractViewModel
{
    use \Redseanet\Cms\Traits\Renderer;

    /**
     * @var PageModel
     */
    protected $pageModel = null;

    public function getPageModel()
    {
        return $this->pageModel;
    }

    public function setPageModel(PageModel $pageModel)
    {
        $this->pageModel = $pageModel;
        return $this;
    }

    public function render()
    {
        if (!is_null($this->pageModel)) {
            try {
                $rendered = $this->pageModel['store_id'] ?
                        $this->getContainer()->get('htmlpurifier')
                                ->purify($this->pageModel['content']) : $this->pageModel['content'];
            } catch (Exception $e) {
                $this->getContainer()->get('log')->logException($e);
                $rendered = '';
            }
            return $rendered ? $this->replace($rendered, [
                'base_url' => $this->getBaseUrl(),
                'pub_url' => $this->getPubUrl(),
                'res_url' => $this->getResourceUrl()
            ]) : '';
        }
        return '';
    }
}
