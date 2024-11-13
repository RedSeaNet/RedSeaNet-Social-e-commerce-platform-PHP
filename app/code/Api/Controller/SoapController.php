<?php

namespace Redseanet\Api\Controller;

use Redseanet\Api\Model\Soap\Server;
use Redseanet\Api\Model\Soap\Wsdl;
use Redseanet\Api\Model\Soap\Wsdl\ComplexTypeStrategy\ComplexTypeWithEav;
use Redseanet\Lib\Bootstrap;
use Redseanet\Lib\Controller\AbstractController;

class SoapController extends AbstractController
{
    use \Redseanet\Lib\Traits\Url;

    protected $wsdl = null;

    /**
     * {@inhertdoc}
     */
    //    public function dispatch($request = null, $routeMatch = null)
    //    {
    //        $response = $this->getResponse();
    //        if (!isset($_SERVER['HTTPS'])) {
    //            return $response->withStatus(403, 'SSL required');
    //        }
    //        return parent::dispatch($request, $routeMatch);
    //    }

    protected function getWsdl()
    {
        $cache = $this->getContainer()->get('cache');
        $result = $cache->fetch('wsdl', 'API_');
        if (!$result) {
            $config = $this->getContainer()->get('config')['api']['wsdl'] ?? [];
            $ns = Bootstrap::getMerchant()['name'];
            $wsdl = new Wsdl($ns, 'urn:' . $ns);
            $wsdl->setComplexTypeStrategy(new ComplexTypeWithEav());
            if (!empty($config['message'])) {
                foreach ($config['message'] as $name => $params) {
                    if (is_array($params)) {
                        foreach ($params as &$param) {
                            $param = $wsdl->getType($param);
                        }
                    } else {
                        $params = [];
                    }
                    $wsdl->addMessage($name, $params);
                }
            }
            if (!empty($config['port'])) {
                $port = $wsdl->addPortType('PortType');
                $binding = $wsdl->addBinding('Binding', 'tns:PortType');
                $wsdl->addSoapBinding($binding, 'rpc');
                $bindingOperation = [
                    'namespace' => 'urn:' . $ns,
                    'use' => 'literal'
                ];
                foreach ($config['port'] as $operation) {
                    $op = $wsdl->addPortOperation($port, $operation['name'], $operation['input'] ?? false, $operation['output'] ?? false, $operation['fault'] ?? false);
                    if (isset($operation['documentation'])) {
                        $wsdl->addDocumentation($op, $operation['documentation']);
                    }
                    $wsdl->addBindingOperation($binding, $operation['name'], isset($operation['input']) ? $bindingOperation : false, isset($operation['output']) ? $bindingOperation : false, isset($operation['fault']) ? $bindingOperation : false);
                }
            }
            $wsdl->addService($ns, 'port', 'tns:Binding', $this->getBaseUrl('api/soap/'));
            $result = $wsdl->toXML();
            $cache->save('wsdl', $result, 'API_');
        }
        return $result;
    }

    public function indexAction()
    {
        if ($this->getRequest()->getQuery('wsdl', false) !== false) {
            $this->getResponse()->withHeader('Content-Type', 'text/xml; charset=UTF-8');
            return $this->getWsdl();
        } elseif ($this->getRequest()->isPost()) {
            $server = new Server($this->getBaseUrl('api/soap/?wsdl'), [
                'actor' => $this->getBaseUrl('api/soap/'),
                'uri' => $this->getBaseUrl('api/soap/'),
                'encoding' => 'UTF-8'
            ]);
            $server->setClass('\\Redseanet\\Api\\Model\\Api\\ClassMap');
            $server->handle($this->getRequest()->getBody()->getContents());
            exit;
        }
        return $this->getResponse()->withStatus(404);
    }
}
