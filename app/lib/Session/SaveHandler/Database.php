<?php

namespace Redseanet\Lib\Session\SaveHandler;

use Exception;
use SessionHandlerInterface;
use Laminas\Db\TableGateway\TableGateway;

/**
 * Handle session storage with database
 */
class Database implements SessionHandlerInterface
{
    use \Redseanet\Lib\Traits\Container;

    /**
     * @var TableGateway
     */
    private static $tableGateway = null;

    private function getTableGateway()
    {
        if (is_null(static::$tableGateway)) {
            static::$tableGateway = new TableGateway('core_session', $this->getContainer()->get('dbAdapter'));
        }
        return static::$tableGateway;
    }

    public function close()
    {
        return true;
    }

    public function destroy($session_id)
    {
        try {
            $this->getTableGateway()->delete(['id' => $session_id]);
            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    public function gc($maxlifetime)
    {
        return true;
    }

    public function open($save_path, $name)
    {
        return !is_null($this->getTableGateway());
    }

    public function read($session_id)
    {
        $result = $this->getTableGateway()->select(['id' => $session_id])->toArray();
        return count($result) ? $result[0]['data'] : null;
    }

    public function write($session_id, $session_data)
    {
        try {
            if ($this->read($session_id)) {
                $this->getTableGateway()->update(['data' => $session_data], ['id' => $session_id]);
            } else {
                $this->getTableGateway()->insert(['data' => $session_data, 'id' => $session_id]);
            }
            return true;
        } catch (Exception $e) {
            return false;
        }
    }
}
