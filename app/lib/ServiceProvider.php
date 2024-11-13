<?php

namespace Redseanet\Lib;

use Pimple\Container as PimpleContainer;
use Pimple\ServiceProviderInterface;
use Redseanet\Lib\Http\Request;
use Redseanet\Lib\Http\Response;
use Laminas\Db\Adapter\Adapter;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

/**
 * Pimple service provider interface.
 */
class ServiceProvider implements ServiceProviderInterface
{
    /**
     * @param PimpleContainer $container
     */
    public function register(PimpleContainer $container)
    {
        $config = Config::instance($container);
        if (!$container->has('config')) {
            $container['config'] = $config;
        }
        if (!$container->has('cache')) {
            $container['cache'] = Cache::instance($config['adapter']['cache'] ?? $container);
        }
        if (!$container->has('indexer')) {
            $container['indexer'] = function ($container) {
                return Indexer::instance($container);
            };
        }
        if (!$container->has('eventDispatcher')) {
            $container['eventDispatcher'] = EventDispatcher::instance();
        }
        if (!$container->has('layout')) {
            $container['layout'] = function ($container) {
                return Layout::instance($container);
            };
        }
        if (!$container->has('request') && isset($_SERVER['REQUEST_METHOD'])) {
            try {
                $request = new Request();
            } catch (\Redseanet\Lib\Exception\InvalidRequestMethod $e) {
                header('HTTP/1.1 405 Method Not Allowed');
                exit;
            }
            $container['request'] = $request;
        }
        if (!$container->has('response')) {
            $response = new Response();
            if (isset($request)) {
                $response->withProtocolVersion($request->getProtocolVersion());
            }
            $response->withStatus(200)
                    ->withHeader('Content-Type', 'text/html; charset=UTF-8');
            $container['response'] = $response;
        }
        if (!$container->has('session')) {
            $container['session'] = Session::instance($config['adapter']['session'] ?? $container);
        }
        if (!$container->has('base_currency')) {
            $container['base_currency'] = function ($container) {
                $currency = new \Redseanet\I18n\Model\Currency();
                $currency->load($container->get('config')['i18n/currency/base'], 'code');
                return $currency;
            };
        }
        if (!$container->has('currency')) {
            $container['currency'] = function ($container) {
                $currency = new \Redseanet\I18n\Model\Currency();
                $currency->load($container->get('request')->getCookie('currency', $container->get('config')['i18n/currency/base']), 'code');
                return $currency;
            };
        }
        if (!$container->has('translator')) {
            $container['translator'] = Translator::instance($container);
        }
        if (!$container->has('dbAdapter')) {
            $dbConfig = $config['adapter']['db'];
            if (Bootstrap::isDeveloperMode()) {
                $dbConfig['profiler'] = true;
            }
            $container['dbAdapter'] = new Adapter($dbConfig);
        }
        if (!$container->has('log')) {
            $container['log'] = function ($container) {
                return new Log($container);
            };
        }
        if (!$container->has('mailer')) {
            $container['mailer'] = function ($container) {
                return new Mailer($container);
            };
        }
        if (!$container->has('imagine')) {
            $container['imagine'] = function ($container) {
                if (extension_loaded('gmagick')) {
                    return new \Imagine\Gmagick\Imagine();
                } elseif (extension_loaded('imagick')) {
                    return new \Imagine\Imagick\Imagine();
                } else {
                    return new \Imagine\Gd\Imagine();
                }
            };
        }
        if (!$container->has('geoip')) {
            $container['geoip'] = function ($container) {
                $config = $container->get('config');
                if (isset($config['adapter']['geoip'])) {
                    $db = BP . 'var/geoip/' . $config['adapter']['geoip'];
                    if (file_exists($db)) {
                        return new \MaxMind\Db\Reader($db);
                    }
                }
                if (is_dir(BP . 'var/geoip/')) {
                    $finder = new \Symfony\Component\Finder\Finder();
                    $finder->files()->in(BP . 'var/geoip/')->name('*.mmdb');
                    foreach ($finder as $file) {
                        return new \MaxMind\Db\Reader($file->getRealPath());
                    }
                }
                return null;
            };
        }
        if (!$container->has('htmlpurifier')) {
            $container['htmlpurifier'] = function ($container) {
                $config = \HTMLPurifier_Config::create([
                    'Attr.AllowedRel' => 'nofollow',
                    'Attr.EnableID' => true,
                    'Attr.ID.HTML5' => true,
                    'Attr.IDPrefix' => 'user-',
                    'AutoFormat.RemoveEmpty' => true,
                    'CSS.AllowImportant' => true,
                    'CSS.MaxImgLength' => null,
                    'Cache.SerializerPath' => BP . 'var/cache/',
                    'Cache.SerializerPermissions' => 0777,
                    'HTML.DefinitionID' => 'html5-definitions',
                    'HTML.DefinitionRev' => 1,
                    'HTML.MaxImgLength' => null,
                    'HTML.SafeEmbed' => true,
                    'HTML.SafeObject' => true,
                    'URI.AllowedSchemes' => [
                        'http' => true,
                        'https' => true,
                        'mailto' => true,
                        'ftp' => true,
                        'tel' => true,
                        'data' => true
                    ]
                ]);
                $def = $config->maybeGetRawHTMLDefinition();
                if ($def) {
                    $def->addElement('section', 'Block', 'Flow', 'Common');
                    $def->addElement('nav', 'Block', 'Flow', 'Common');
                    $def->addElement('article', 'Block', 'Flow', 'Common');
                    $def->addElement('aside', 'Block', 'Flow', 'Common');
                    $def->addElement('header', 'Block', 'Flow', 'Common');
                    $def->addElement('footer', 'Block', 'Flow', 'Common');
                    $def->addElement('address', 'Block', 'Flow', 'Common');
                    $def->addElement('hgroup', 'Block', 'Required: h1 | h2 | h3 | h4 | h5 | h6', 'Common');
                    $def->addElement('figure', 'Block', 'Optional: (figcaption, Flow) | (Flow, figcaption) | Flow', 'Common');
                    $def->addElement('figcaption', 'Inline', 'Flow', 'Common');
                    $def->addElement('video', 'Block', 'Optional: (source, Flow) | (Flow, source) | Flow', 'Common', [
                        'src' => 'URI',
                        'type' => 'Text',
                        'width' => 'Length',
                        'height' => 'Length',
                        'poster' => 'URI',
                        'preload' => 'Enum#auto,metadata,none',
                        'controls' => 'Bool',
                        'autoplay' => 'Bool',
                        'loop' => 'Bool'
                    ]);
                    $def->addElement('audio', 'Block', 'Optional: (source, Flow) | (Flow, source) | Flow', 'Common', [
                        'src' => 'URI',
                        'type' => 'Text',
                        'controls' => 'Bool',
                        'autoplay' => 'Bool',
                        'preload' => 'Enum#auto,metadata,none',
                        'loop' => 'Bool'
                    ]);
                    $def->addElement('source', 'Block', 'Flow', 'Common', [
                        'src' => 'URI',
                        'type' => 'Text',
                    ]);
                    $def->addElement('sub', 'Inline', 'Inline', 'Common');
                    $def->addElement('sup', 'Inline', 'Inline', 'Common');
                    $def->addElement('mark', 'Inline', 'Inline', 'Common');
                    $def->addElement('wbr', 'Inline', 'Empty', 'Core');
                    $def->addElement('button', 'Inline', 'Inline', 'Common', [
                        'type' => 'Enum#submit,reset,button',
                        'disabled' => 'Text',
                        'data-dismiss' => 'Text',
                        'data-toggle' => 'Text',
                        'data-placement' => 'Text',
                        'title' => 'Text',
                        'data-content' => 'Text',
                        'data-target' => 'Text'
                    ]);
                    $def->addElement('input', 'Inline', 'Empty', 'Common', [
                        'type' => 'Enum#button,checkbox,date,datetime,email,file,hidden,image,number,password,radio,range,reset,submit,tel,text,time',
                        'placeholder' => 'Text',
                        'checked' => 'Text',
                        'disabled' => 'Text',
                        'readonly' => 'Text',
                        'autofocus' => 'Text',
                        'name' => 'Text',
                        'value' => 'CDATA',
                        'required' => 'Text',
                        'min' => 'Number',
                        'max' => 'Number',
                        'minlength' => 'Number',
                        'maxlength' => 'Number',
                        'data-rule-range' => 'Text',
                        'data-rule-rangelength' => 'Text',
                        'data-rule-equalto' => 'CDATA',
                        'data-rule-remote' => 'URI'
                    ]);
                    $def->addElement('select', 'Inline', 'Required: option', 'Common', [
                        'disabled' => 'Text',
                        'required' => 'Text',
                        'name' => 'Text',
                        'multiple' => 'Text'
                    ]);
                    $def->addElement('textarea', 'Inline', 'Inline', 'Common', [
                        'disabled' => 'Text',
                        'minlength' => 'Number',
                        'maxlength' => 'Number',
                        'readonly' => 'Text',
                        'name' => 'Text',
                        'placeholder' => 'Text',
                        'required' => 'Text',
                        'cols' => 'Number',
                        'rows' => 'Number',
                        'wrap' => 'Enum#hard,soft'
                    ]);
                    $def->addElement('form', 'Inline', 'Flow', 'Common', [
                        'action' => 'URI',
                        'method' => 'Enum#get,post,delete',
                        'enctype' => 'Enum#application/x-www-form-urlencoded,multipart/form-data,text/plain',
                        'target' => 'FrameTarget'
                    ]);
                    $def->addElement('legend', 'Block', 'Inline', 'Common');
                    $def->addElement('fieldset', 'Block', 'Required: legend', 'Common');
                    $def->addAttribute('a', 'data-toggle', 'Text');
                    $def->addAttribute('a', 'data-target', 'Text');
                    $def->addAttribute('a', 'data-content', 'Text');
                    $def->addAttribute('a', 'data-slide', 'Text');
                    $def->addAttribute('li', 'data-slide-to', 'Text');
                    $def->addAttribute('li', 'data-target', 'Text');
                    $def->addAttribute('div', 'data-spy', 'Text');
                    $def->addAttribute('div', 'data-offset-top', 'Number');
                    $def->addAttribute('div', 'data-offset-bottom', 'Number');
                }
                return new \HTMLPurifier($config);
            };
        }
        if (!empty($config['adapter']['mq'])) {
            if (!$container->has('mqAdapter')) {
                $mqConfig = $config['adapter']['mq'];
                $container['mqAdapter'] = AMQPStreamConnection::create_connection(
                    [
                        ['host' => $mqConfig['host'], 'port' => $mqConfig['port'], 'user' => $mqConfig['login'], 'password' => $mqConfig['password'], 'vhost' => $mqConfig['vhost']],
                    ],
                    [
                        'insist' => false,
                        'login_method' => 'AMQPLAIN',
                        'login_response' => null,
                        'locale' => 'en_US',
                        'connection_timeout' => 3.0,
                        'read_write_timeout' => 10.0,
                        'context' => null,
                        'keepalive' => false,
                        'heartbeat' => 5
                    ]
                );
            }
        }
    }
}
