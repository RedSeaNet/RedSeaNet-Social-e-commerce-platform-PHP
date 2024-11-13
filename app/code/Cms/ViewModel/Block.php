<?php

namespace Redseanet\Cms\ViewModel;

use Redseanet\Lib\Bootstrap;
use Redseanet\Cms\Model\Block as BlockModel;
use Redseanet\Cms\Model\Collection\Block as BlockCollection;
use Redseanet\Lib\ViewModel\AbstractViewModel;

class Block extends AbstractViewModel
{
    use \Redseanet\Cms\Traits\Renderer;

    /**
     * @var int
     */
    protected $blockId = null;

    /**
     * @var BlockModel
     */
    protected $blockModel = null;

    public function getBlockModel()
    {
        if (is_null($this->blockModel) && !is_null($this->blockId)) {
            $this->blockModel = new BlockModel();
            $this->blockModel->load($this->blockId, 'code');
        }
        return $this->blockModel;
    }

    public function setBlockModel(BlockModel $blockModel)
    {
        $this->blockModel = $blockModel;
        $this->cacheKey = $blockModel['code'];
        return $this;
    }

    public function setBlockId($id)
    {
        $collection = new BlockCollection();
        $collection->where(['cms_block.code' => $id, 'language_id' => Bootstrap::getLanguage()->getId()]);
        if (count($collection)) {
            $this->blockId = $id;
            $this->blockModel = new BlockModel($collection[0]);
            $this->cacheKey = $id;
        }
        return $this;
    }

    public function render()
    {
        if (!is_null($this->getBlockModel()) && $this->getBlockModel()->getId()) {
            $rendered = $this->getBlockModel()['store_id'] ?
                    $this->getContainer()->get('htmlpurifier')
                            ->purify($this->getBlockModel()['content']) : $this->getBlockModel()['content'];
            return $this->replace($rendered, [
                'base_url' => $this->getBaseUrl(),
                'pub_url' => $this->getPubUrl(),
                'res_url' => $this->getResourceUrl()
            ]);
        }
        return '';
    }
}
