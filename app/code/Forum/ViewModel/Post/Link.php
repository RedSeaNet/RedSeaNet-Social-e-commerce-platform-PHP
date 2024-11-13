<?php

namespace Redseanet\Forum\ViewModel\Post;

use Redseanet\Forum\Model\Post;
use Redseanet\Catalog\Model\Collection\Product as Collection;
use Redseanet\Catalog\Model\Product as Model;
use Redseanet\Lib\Session\Segment;

class Link extends Post
{
    protected $action = [];
    protected $type = '';
    protected $bannedFields = ['id', 'linktype'];

    public function getType()
    {
        return $this->type ?: $this->getQuery('linktype');
    }

    public function setType($type)
    {
        $this->type = $type;
        $this->query = [];
        return $this;
    }

    protected function prepareCollection($collection = null)
    {
        $collection = new Collection();
        $collection->getSelect()->where->notEqualTo('id', $this->getRequest()->getQuery('id'));
        if (!$this->getQuery('desc')) {
            $this->query['desc'] = 'created_at';
        }
        return parent::prepareCollection($collection);
    }

    public function getActiveIds()
    {
        $collection = (new Post())->setId($this->getRequest()->getQuery('id'))
                ->getLinkedProducts();
        $old = $this->getSegment('customer')->get('form_data_forum_product_relation', false);
        if ($old === false) {
            $activeIds = [];
        } else {
            $activeIds = $old[$this->getType()]['forum_product_relation'];
        }
        if (count($collection)) {
            foreach ($collection->toArray() as $item) {
                if ($old === false || !in_array($item['id'], $old[$this->getType()]['remove'])) {
                    $activeIds[] = $item['id'];
                }
            }
        }
        return $activeIds;
    }
}
