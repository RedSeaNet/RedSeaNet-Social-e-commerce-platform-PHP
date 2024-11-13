<?php

namespace Redseanet\Resource\Traits\Remove;

use Redseanet\Resource\Model\Collection\Resource as Collection;

trait Local
{
    protected function beforeRemove()
    {
        if ($this->getId()) {
            if (!$this->isLoaded) {
                $this->load($this->getId());
                $type = $this->storage['file_type'];
                $collection = new Collection();
                $collection->where(['md5' => $this->storage['md5']])
                ->where->notEqualTo('id', $this->getId());
                if (count($collection) === 0) {
                    unlink(static::$options['path'] . substr($type, 0, strpos($type, '/') + 1) . $this->storage['real_name']);
                }
            }
        }
        parent::beforeRemove();
    }
}
