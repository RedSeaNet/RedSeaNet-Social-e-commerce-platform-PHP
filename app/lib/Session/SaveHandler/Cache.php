<?php

namespace Redseanet\Lib\Session\SaveHandler;

use Redseanet\Lib\Cache as CacheHandler;
use SessionHandlerInterface;

/**
 * Handle session storage with cache
 */
class Cache implements SessionHandlerInterface
{
    /**
     * @var CacheHandler
     */
    protected $cache = null;

    protected function getCache()
    {
        if (is_null($this->cache)) {
            $this->cache = CacheHandler::instance();
        }
        return $this->cache;
    }

    public function close(): bool
    {
        return true;
    }

    public function destroy($session_id): bool
    {
        return $this->getCache()->delete('SESS_' . $session_id);
    }

    #[\ReturnTypeWillChange]
    public function gc(int $max_lifetime): int|bool
    {
        return true;
    }

    public function open($save_path, $name): bool
    {
        return !is_null($this->getCache());
    }

    public function read($session_id): string
    {
        return $this->getCache()->fetch('SESS_' . $session_id);
    }

    public function write($session_id, $session_data): bool
    {
        $this->getCache()->save('SESS_' . $session_id, $session_data);
        return true;
    }
}
