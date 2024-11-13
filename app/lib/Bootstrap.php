<?php

namespace Redseanet\Lib;

use Psr\Container\ContainerInterface;
use Redseanet\Lib\Model\Merchant;
use Redseanet\Lib\Model\Store;
use Redseanet\Lib\Model\Language;
use Symfony\Component\Yaml\Yaml;

/**
 * Bootstrap main system
 */
final class Bootstrap
{
    /**
     * @var ContainerInterface
     */
    private static $container = null;

    /**
     * @var EventDispatcher
     */
    private static $eventDispatcher = null;

    /**
     * @var Merchant
     */
    private static $merchant = null;

    /**
     * @var Store
     */
    private static $store = null;

    /**
     * @var Language
     */
    private static $language = null;

    /**
     * @var bool
     */
    private static $isMobile = null;

    /**
     * @var bool
     */
    private static $developerMode = false;

    /**
     * Prepare or get container singleton
     *
     * @return ContainerInterface
     */
    public static function getContainer()
    {
        if (is_null(static::$container)) {
            static::$container = new Container();
            static::$container->register(new ServiceProvider());
        }
        return static::$container;
    }

    /**
     * Initialize system veriables
     *
     * @param array $server
     * @throws Exception\MissingFileException
     */
    public static function init($server)
    {
        if (!file_exists(BP . 'app/config/adapter.yml')) {
            throw new Exception\MissingFileException(BP . 'app/config/adapter.yml');
        }
        $config = static::prepareConfig();
        static::handleConfig($config);
        date_default_timezone_set($config['global/locale/timezone'] ?: 'UTC');
        $segment = new Session\Segment('core');
        $language = static::getLanguage($server, $segment);
        static::$container['language'] = $language;
        static::$container['translator']->setLocale($language['code']);
    }

    /**
     * Run system
     *
     * @param array $server
     */
    public static function run($server)
    {
        if (is_null(static::$container)) {
            static::init($server);
            static::$developerMode = (bool) ($server['DEVELOPER_MODE'] ?? false);
        }
        static::$eventDispatcher->trigger('route', ['routers' => static::getContainer()->get('config')['route']]);
        static::$eventDispatcher->trigger('render', ['response' => static::getContainer()->get('response')->getData()]);
        static::$eventDispatcher->trigger('respond');
    }

    /**
     * Prepare config from cache
     *
     * @return Config
     */
    private static function prepareConfig()
    {
        $adapter = Yaml::parse(file_get_contents(BP . 'app/config/adapter.yml'));
        $cache = Cache::instance($adapter['cache'] ?? ['adapter' => '']);
        $config = ['adapter' => $adapter];
        $config['db'] = $cache->fetch('db', 'SYSTEM_CONFIG');
        if ($config['db']) {
            $config = Config::instance($config);
            static::getContainer();
        } else {
            unset($config['db']);
            $config = Config::instance($config);
            static::getContainer();
            $cache->save('db', $config->loadFromDB(), 'SYSTEM_CONFIG');
        }
        return $config;
    }

    /**
     * Handle the main system configuration
     *
     * @param Config $config
     */
    private static function handleConfig($config)
    {
        static::$eventDispatcher = static::getContainer()->get('eventDispatcher');
        if (!empty($config['event'])) {
            foreach ($config['event'] as $name => $events) {
                foreach ((array) $events as $event) {
                    $listenerArray = $event['listener'] ?? $event;
                    static::$eventDispatcher->addListener($name, [new $listenerArray[0](), $listenerArray[1]], -(int) ($event['priority'] ?? 0));
                }
            }
        }
    }

    public static function getLanguage($server = null, $segment = null)
    {
        if (is_null(static::$language)) {
            if (is_null($server)) {
                $server = $_SERVER;
            }
            if (is_null($segment)) {
                $segment = new Session\Segment('core');
            }
            if (!isset($server['language']) || $server['language'] == '') {
                if (isset($server['HTTP_ACCEPT_LANGUAGE'])) {
                    $lang = substr($server['HTTP_ACCEPT_LANGUAGE'], 0, 4);
                    if (preg_match('/zh-c/i', $lang)) {
                        $server['language'] = 'zh-CN';
                    } elseif (preg_match('/zh/i', $lang)) {
                        $server['language'] = 'zh-HK';
                    } elseif (preg_match('/en/i', $lang)) {
                        $server['language'] = 'en-US';
                    }
                }
            }
            $code = $segment->get('language') ?:
                    ($_COOKIE['language'] ??
                    ($server['language'] ?? null));
            if (is_string($code)) {
                $language = new Language();
                $language->load($code, 'code');
                if ($language->getId()) {
                    static::$language = $language;
                }
            }
            if (is_null(static::$language)) {
                static::$language = static::getMerchant($server, $segment)->getLanguage();
                $code = static::$language['code'];
            }
            $segment->set('language', $code);
            if (!isset($_COOKIE['language']) || $_COOKIE['language'] !== $code) {
                static::getContainer()->get('response')->withCookie('language', ['value' => $code, 'path' => '/']);
            }
        }
        return static::$language;
    }

    public static function getStore($server = null, $segment = null)
    {
        if (is_null(static::$store)) {
            if (is_null($server)) {
                $server = $_SERVER;
            }
            if (is_null($segment)) {
                $segment = new Session\Segment('core');
            }
            $code = $segment->get('store') ?: (isset($_COOKIE['store']) ?
                    $_COOKIE['store'] : (isset($server['store']) ?: null));
            if (is_string($code)) {
                $store = new Store();
                $store->load($code, 'code');
                if ($store->getId()) {
                    static::$store = $store;
                }
            }
            if (is_null(static::$store)) {
                static::$store = static::getMerchant($server, $segment)->getStore();
                $code = static::$store['code'];
            }
            $segment->set('store', $code);
            if (!isset($_COOKIE['store']) || $_COOKIE['store'] !== $code) {
                static::getContainer()->get('response')->withCookie('store', ['value' => $code, 'path' => '/']);
            }
        }
        return static::$store;
    }

    public static function getMerchant($server = null, $segment = null)
    {
        if (is_null(static::$merchant)) {
            if (is_null($server)) {
                $server = $_SERVER;
            }
            if (is_null($segment)) {
                $segment = new Session\Segment('core');
            }
            if (!is_null(static::$language) && static::$language['merchant_id']) {
                static::$merchant = new Merchant();
                static::$merchant->load(static::$language['merchant_id']);
            } elseif (!is_null(static::$store) && static::$store['merchant_id']) {
                static::$merchant = new Merchant();
                static::$merchant->load(static::$store['merchant_id']);
            } else {
                $code = $segment->get('merchant') ?: (isset($server['merchant']) ?: null);
                if (is_string($code)) {
                    $merchant = new Merchant();
                    $merchant->load($code, 'code');
                    if ($merchant->getId()) {
                        static::$merchant = $merchant;
                    }
                }
                if (is_null(static::$merchant)) {
                    static::$merchant = new Merchant();
                    static::$merchant->load(1, 'is_default');
                    $code = static::$merchant['code'];
                }
                $segment->set('merchant', $code);
            }
        }
        return static::$merchant;
    }

    /**
     * Mobile agent
     *
     * @return bool
     */
    public static function isMobile()
    {
        if (is_null(self::$isMobile) && isset($_SERVER['HTTP_USER_AGENT'])) {
            self::$isMobile = preg_match('/iPhone|iP[ao]d|BlackBerry|Palm|Googlebot-Mobile|Mobile|mobile|mobi|Windows Mobile|Safari Mobile|Android|Opera Mini|Fennec/', $_SERVER['HTTP_USER_AGENT']);
        }
        return self::$isMobile;
    }

    /**
     * Developer mode
     *
     * @return bool
     */
    public static function isDeveloperMode()
    {
        return self::$developerMode;
    }
}
