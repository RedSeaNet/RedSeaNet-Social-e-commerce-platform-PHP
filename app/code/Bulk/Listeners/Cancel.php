<?php

namespace Redseanet\Bulk\Listeners;

use Redseanet\Lib\Listeners\ListenerInterface;
use Redseanet\Sales\Model\Cart;

class Cancel implements ListenerInterface
{
    use \Redseanet\Lib\Traits\Container;

    use \Redseanet\Lib\Traits\DB;

    use \Redseanet\Lib\Traits\DataCache;

    use \Redseanet\Bulk\Traits\Cancel;

    protected $allowedController = [
        'Redseanet\\Checkout\\Controller\\OrderController' => true,
        'Redseanet\\Balance\\Controller\\IndexController' => true,
        'Redseanet\\RewardPoints\\Controller\\IndexController' => true,
        'Redseanet\\Bulk\\Controller\\ProcessController' => true,
        'Redseanet\\Resource\\Controller\\ResizeController' => true
    ];

    public function cancel($e)
    {
        $controller = get_class($e['controller']);
        if (Cart::instance()->getAdditional('bulk') &&
                (!isset($this->allowedController[$controller]) ||
                $this->allowedController[$controller] !== true &&
                !in_array($e['method'], $this->allowedController[$controller]))) {
            $this->doCancel();
        }
    }
}
