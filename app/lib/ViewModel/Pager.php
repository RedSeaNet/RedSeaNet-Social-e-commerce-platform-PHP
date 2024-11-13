<?php

namespace Redseanet\Lib\ViewModel;

use Redseanet\Lib\Model\AbstractCollection;
use Laminas\Db\Sql\Expression;

/**
 * Pager of collection
 */
class Pager extends Template
{
    /**
     * @var AbstractCollection
     */
    protected $collection = null;

    /**
     * @var bool
     */
    protected $showLabel = true;

    /**
     * @var int
     */
    protected $total = null;

    /**
     * @var int
     */
    protected $limit = 20;

    /**
     * @var int
     */
    protected $page = 1;

    public function __construct()
    {
        $this->setTemplate('page/pager');
    }

    /**
     * Set and prepare collection
     *
     * @param AbstractCollection $collection
     * @return Pager
     */
    public function setCollection(AbstractCollection $collection)
    {
        $this->limit = (int) $collection->getRawState('limit') ?: 20;
        $this->page = (int) ($collection->getRawState('offset') / $this->limit + 1);
        $this->collection = clone $collection;
        $this->collection->reset('order')
                ->reset('limit')
                ->reset('offset');
        return $this;
    }

    /**
     * Get collection
     *
     * @return AbstractCollection
     */
    public function getCollection()
    {
        return $this->collection;
    }

    /**
     * Get items count
     *
     * @return int
     */
    public function getCount()
    {
        if (is_null($this->total)) {
            $collection = clone $this->getCollection();
            $collection->columns(['count' => new Expression('count(1)')]);
            if ($joins = $collection->getRawState('joins')) {
                $collection->reset('joins');
                foreach ($joins->getJoins() as $join) {
                    $collection->join($join['name'], $join['on'], [], $join['type']);
                }
            }
            $collection->load(true, true);
            $this->total = count($collection) ? $collection->toArray()[0]['count'] : 0;
        }
        return $this->total;
    }

    /**
     * Get sql limit
     *
     * @return int
     */
    public function getLimit()
    {
        return $this->getQuery('limit', $this->limit);
    }

    /**
     * Set default sql limit
     * @param int $limit
     * @return Pager
     */
    public function setDefaultLimit($limit)
    {
        $this->limit = $limit;
        return $this;
    }

    /**
     * Get count of pages
     *
     * @return int
     */
    public function getAllPages()
    {
        return ceil($this->getCount() / $this->getLimit());
    }

    /**
     * Get current page number
     *
     * @return int
     */
    public function getCurrentPage()
    {
        return (int) $this->getQuery('page', $this->page);
    }

    /**
     * Should label shown
     *
     * @param bool $flag
     * @return bool
     */
    public function showLabel($flag = null)
    {
        if (is_bool($flag)) {
            $this->showLabel = $flag;
        }
        return $this->showLabel;
    }

    /**
     * Get pager url
     *
     * @param int $pager
     * @return string
     */
    public function getPagerUrl($pager = null)
    {
        $query = $this->getQuery();
        if (is_null($pager)) {
            unset($query['page']);
        } else {
            $query['page'] = $pager;
        }
        return $this->getUri()->withQuery(http_build_query($query))->__toString();
    }
}
