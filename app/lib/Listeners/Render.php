<?php

namespace Redseanet\Lib\Listeners;

use Redseanet\Lib\ViewModel\AbstractViewModel;

/**
 * Listen render event
 */
class Render implements ListenerInterface
{
    use \Redseanet\Lib\Traits\Container;

    public function render($event)
    {
        $response = $event['response'];
        if (!is_object($response)) {
            $data = $response;
            if (is_scalar($response)) {
                $response = $this->getContainer()->get('response');
                $response->getBody()->write($data);
            } elseif (is_array($response)) {
                $callback = $this->getContainer()->get('request')->getQuery('callback');
                $response = $this->getContainer()->get('response');
                if ($callback) {
                    $response->withHeader('Content-Type', 'application/javascript; charset=UTF-8')
                            ->getBody()->write($callback . '(' . json_encode($data) . ');');
                } else {
                    $response->withHeader('Content-Type', 'application/json; charset=UTF-8')
                            ->getBody()->write(json_encode($data));
                }
            }
        } elseif ($response instanceof AbstractViewModel) {
            $rendered = $response->render();
            $response = $this->getContainer()->get('response');
            $response->getBody()->write($rendered);
        }
    }
}
