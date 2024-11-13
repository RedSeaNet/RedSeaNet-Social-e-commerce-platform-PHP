<?php

namespace Redseanet\Log\ViewModel;

use Redseanet\Lib\Bootstrap;
use Redseanet\Lib\Session\Segment;
use Redseanet\Lib\ViewModel\AbstractViewModel;

class Visitor extends AbstractViewModel
{
    public function render()
    {
        $customer = new Segment('customer');
        $config = $this->getConfig();
        if ($config['log/enabled'] &&
                ($config['log/target'] == 0 || $customer->get('hasLoggedIn')) &&
                ($config['log/dnt_check'] == 0 || !$this->getRequest()->getHeader('DNT')['DNT'])) {
            $catalog = new Segment('catalog');
            $result = '
        <script>(function() {
var os = document.createElement("script");
os.src = "' . ($config['log/url'] ?: ($this->getBaseUrl('log/') . str_replace(
                ['+', '/', '='],
                ['-', '_', ''],
                base64_encode((
                    $customer->get('hasLoggedIn') ? $customer->get('customer')['id'] : 'n'
                ) . '-' .
                                        Bootstrap::getStore()->getId() . '-' .
                                        $catalog->get('product_id', 'n'))
            ))) . '.js";
var s = document.getElementsByTagName("script")[0];
s.parentNode.insertBefore(os, s);
})();</script>';
            $catalog->offsetUnset('product_id');
        }
        return $result ?? '';
    }
}
