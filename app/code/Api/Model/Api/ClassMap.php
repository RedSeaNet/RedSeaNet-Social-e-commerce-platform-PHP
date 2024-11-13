<?php

namespace Redseanet\Api\Model\Api;

use Error;
use Exception;
use SoapFault;

class ClassMap
{
    use \Redseanet\Lib\Traits\Container;

    public function __call($name, $arguments)
    {
        $config = $this->getContainer()->get('config')['api']['soap'] ?? [];
        try {
            if (isset($config[$name]) && is_subclass_of($config[$name], '\\Redseanet\\Api\\Model\\Api\\HandlerInterface') && is_callable([$config[$name], $name])) {
                return call_user_func_array([new $config[$name](), $name], $arguments);
            }
        } catch (SoapFault $e) {
            $this->getContainer()->get('log')->log($e->getMessage());
            return $e;
        } catch (Error $e) {
            $this->getContainer()->get('log')->logError($e);
            return new SoapFault('Server', 'An error detected.');
        } catch (Exception $e) {
            $this->getContainer()->get('log')->logException($e);
            return new SoapFault('Server', 'An error detected.');
        }
        return new SoapFault('Client', 'Unknown method: ' . $name);
    }
}
