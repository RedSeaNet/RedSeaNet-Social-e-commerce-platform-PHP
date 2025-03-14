<?php

namespace Redseanet\Lib\Cache;

use Doctrine\Common\Cache\Cache;
use Doctrine\Common\Cache\CacheProvider;
use MongoDB\BSON\Binary;
use MongoDB\BSON\UTCDateTime;
use MongoDB\Driver\Exception\Exception;
use MongoDB\Collection;

/**
 * MongoDB cache provider with ext-mongodb.
 */
class MongoDBCache extends CacheProvider
{
    public const DATA_FIELD = 'd';
    public const EXPIRATION_FIELD = 'e';

    /**
     * @var Collection
     */
    protected $collection;

    public function __construct(Collection $collection)
    {
        $this->collection = $collection;
    }

    /**
     * {@inheritdoc}
     */
    protected function doFetch($id)
    {
        $document = $this->collection->findOne(['_id' => $id]);

        if ($document === null) {
            return false;
        }

        if ($this->isExpired($document)) {
            $this->doDelete($id);
            return false;
        }

        return is_object($document[self::DATA_FIELD]) ? $document[self::DATA_FIELD]->getData() : $document[self::DATA_FIELD];
    }

    /**
     * {@inheritdoc}
     */
    protected function doContains($id)
    {
        $document = $this->collection->findOne(['_id' => $id]);

        if ($document === null) {
            return false;
        }

        if ($this->isExpired($document)) {
            $this->doDelete($id);
            return false;
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    protected function doSave($id, $data, $lifeTime = 0)
    {
        try {
            $result = $this->collection->updateOne(
                ['_id' => $id],
                ['$set' => [
                    self::EXPIRATION_FIELD => ($lifeTime > 0 ? new UTCDateTime((time() + $lifeTime) * 1000) : null),
                    self::DATA_FIELD => new Binary($data, Binary::TYPE_OLD_BINARY)
                ]],
                ['upsert' => true]
            );
        } catch (Exception $e) {
            return false;
        }

        return $result->isAcknowledged();
    }

    /**
     * {@inheritdoc}
     */
    protected function doDelete($id)
    {
        $result = $this->collection->deleteOne(['_id' => $id]);
        return $result->isAcknowledged();
    }

    /**
     * {@inheritdoc}
     */
    protected function doFlush()
    {
        $result = $this->collection->deleteMany([1]);
        return $result->isAcknowledged();
    }

    /**
     * {@inheritdoc}
     */
    protected function doGetStats()
    {
        return [
            Cache::STATS_HITS => null,
            Cache::STATS_MISSES => null,
            Cache::STATS_UPTIME => null,
            Cache::STATS_MEMORY_USAGE => null,
            Cache::STATS_MEMORY_AVAILABLE => null,
        ];
    }

    /**
     * Check if the document is expired.
     *
     * @param array|object $document
     * @return bool
     */
    private function isExpired($document)
    {
        return isset($document[self::EXPIRATION_FIELD]) &&
                $document[self::EXPIRATION_FIELD] instanceof UTCDateTime &&
                $document[self::EXPIRATION_FIELD]->__toString() < time() * 1000;
    }
}
