<?php

namespace Redseanet\Bulk\Model\Api\Soap;

use Exception;
use Redseanet\Api\Model\Api\AbstractHandler;
use Redseanet\Bulk\Model\Collection\Bulk as Collection;
use Redseanet\Bulk\Model\Bulk as Model;
use Redseanet\Lib\Model\Eav\Type;
use Redseanet\Lib\Model\Eav\Attribute\Set;

class Bulk extends AbstractHandler
{
    /**
     * @param string $sessionId
     * @param int $customerId
     * @return array
     */
    public function BulkList($sessionId, $customerId = '')
    {
        $this->validateSessionId($sessionId, __FUNCTION__);
        $collection = new Collection();
        $collection->where(['customer_id' => $customerId]);
        $collection->load(true, true);
        $result = [];
        foreach ($collection as $item) {
            $result[] = (object) $item;
        }
        return $result;
    }
}
