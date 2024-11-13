<?php

namespace Redseanet\Search\Model;

use Redseanet\Search\Model\Collection\Term as Collection;
use Redseanet\Lib\Model\AbstractModel;

abstract class Term extends AbstractModel
{
    /**
     * @return Collection
     */
    abstract protected function getCollection();

    public function getSynonym()
    {
        $synonym = $this->getCollection();
        $synonym->where(['synonym' => $this->storage['term']]);
        return $synonym;
    }

    public function getPopularity()
    {
        $popularity = $this->storage['count'];
        $this->getSynonym()->walk(function ($item) use (&$popularity) {
            $popularity += $item->getPopularity();
        });
        return $popularity;
    }
}
