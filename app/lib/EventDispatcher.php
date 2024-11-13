<?php

namespace Redseanet\Lib;

use Error;
use Redseanet\Lib\EventDispatcher\Event;
use Redseanet\Lib\Stdlib\Singleton;
use Symfony\Contracts\EventDispatcher\Event as SymfonyEvent;
use Symfony\Component\EventDispatcher\EventDispatcher as SymfonyEventDispatcher;

/**
 * Event Dispatcher
 */
class EventDispatcher extends SymfonyEventDispatcher implements Singleton
{
    protected static $instance = null;
    protected static $singleton = [];

    /**
     * @param string $eventName
     * @param Event|array $event
     * @return Event
     */
    public function trigger($eventName, $event = [])
    {
        if (is_array($event)) {
            $event = new Event($event);
        }
        return $this->dispatch($event, $eventName);
    }

    /**
     * {@inheritdoc}
     */
    protected function doDispatch($listeners, $eventName, SymfonyEvent $event)
    {
        foreach ($listeners as $listener) {
            try {
                if (is_array($listener) && is_subclass_of($listener[0], '\\Redseanet\\Lib\\Listeners\\ListenerInterface')) {
                    if (!isset(static::$singleton[$listener[0]])) {
                        static::$singleton[$listener[0]] = new $listener[0]();
                    }
                    $listener[0] = static::$singleton[$listener[0]];
                }
                if (is_callable($listener)) {
                    call_user_func($listener, $event, $eventName, $this);
                }
                if ($event->isPropagationStopped()) {
                    break;
                }
            } catch (Error $e) {
                Bootstrap::getContainer()->get('log')->logError($e);
            }
        }
    }

    public static function instance()
    {
        if (is_null(static::$instance)) {
            static::$instance = new static();
        }
        return static::$instance;
    }
}
