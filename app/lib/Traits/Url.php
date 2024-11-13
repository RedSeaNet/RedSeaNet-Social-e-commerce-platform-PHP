<?php

namespace Redseanet\Lib\Traits;

use Redseanet\Lib\Bootstrap;
use Redseanet\Resource\Model\Resource;
use Redseanet\Resource\Lib\Factory as resourceFactory;

/**
 * Get url
 */
trait Url
{
    /**
     * @var string
     */
    protected $baseUrl = '';

    /**
     * @var string
     */
    protected $pubUrl = '';

    /**
     * @var array
     */
    protected static $cachedUrl = ['b' => [], 'a' => [], 'p' => [], 'u' => []];

    /**
     * Get url based on the website root
     *
     * @param string $path
     * @return string
     */
    public function getBaseUrl($path = '')
    {
        if (!isset(static::$cachedUrl['b'][$path])) {
            if ($this->baseUrl === '') {
                $config = $this->getContainer()->get('config');
                $this->baseUrl = $config['adapter']['base_url'] ?? $config['global/url/base_url'];
            }
            static::$cachedUrl['b'][$path] = $this->baseUrl . ltrim($path, '/');
        }
        return static::$cachedUrl['b'][$path];
    }

    /**
     * Get url based on the admin page
     *
     * @param string $path
     * @return string
     */
    public function getAdminUrl($path = '')
    {
        if (!isset(static::$cachedUrl['a'][$path])) {
            if (strpos($path, ':ADMIN') !== false) {
                static::$cachedUrl['a'][$path] = $this->getBaseUrl(str_replace(':ADMIN', $this->getContainer()->get('config')['global/url/admin_path'], $path));
            } else {
                static::$cachedUrl['a'][$path] = $this->getBaseUrl($this->getContainer()->get('config')['global/url/admin_path'] . '/' . ltrim($path, '/'));
            }
        }
        return static::$cachedUrl['a'][$path];
    }

    /**
     * Get static files url
     *
     * @param string $path
     * @return string
     */
    public function getPubUrl($path = '')
    {
        if (!isset(static::$cachedUrl['p'][$path])) {
            if ($this->pubUrl === '') {
                $config = $this->getContainer()->get('config');
                $base = $config['global/url/cookie_free_domain'];
                $mobile = Bootstrap::isMobile() ? 'mobile_' : '';
                $prefix = 'pub/theme/' . $config[is_callable([$this, 'isAdminPage']) && $this->isAdminPage() ?
                        'theme/backend/static' : 'theme/frontend/' . $mobile . 'static'] . '/';
                $this->pubUrl = $base ? ($base . $prefix) : $this->getBaseUrl($prefix);
            }
            static::$cachedUrl['p'][$path] = $this->pubUrl . ltrim($path, '/');
        }
        return static::$cachedUrl['p'][$path];
    }

    /**
     * Get resource url
     *
     * @param string $path
     * @return string
     */
    public function getResourceUrl($path = '', $needSign = false)
    {
        $config = Bootstrap::getContainer()->get('config');
        if (isset($config['resource/server/service']) && $config['resource/server/service'] == 'awss3') {
            return $config['resource/awss3/url'] . $path;
        } elseif (isset($config['resource/server/service']) && $config['resource/server/service'] == 'aliyunoss') {
            if ($needSign) {
                $urlResult = resourceFactory::aliYunOSSSignedUrl('pub/resource/' . $path, [], 3600);
                if ($urlResult['error'] == 0) {
                    return $urlResult['sigined_url'];
                } else {
                    return $this->getPubUrl('frontend/images/placeholder.png');
                }
            } else {
                $urlResult = resourceFactory::aliYunOSSUrl('pub/resource/' . $path, [], 3600);
                if ($urlResult['error'] == 0) {
                    return $urlResult['url'];
                } else {
                    return $this->getPubUrl('frontend/images/placeholder.png');
                }
            }
        } else {
            return $this->getBaseUrl('pub/resource/' . $path);
        }
    }

    public function getUploadedUrl($path = '', $needSign = false)
    {
        $config = Bootstrap::getContainer()->get('config');
        if (isset($config['resource/server/service']) && $config['resource/server/service'] == 'awss3') {
            $suffix = is_scalar($path) ? $path :
                    (is_callable([$path, '__toString']) ? $path->__toString() :
                    ($path instanceof Resource ? (substr($path['file_type'], 0, strpos($path['file_type'], '/')) . '/' . $path['real_name']) : ''));

            return $config['resource/awss3/url'] . 'pub/upload/' . $suffix;
        } elseif (isset($config['resource/server/service']) && $config['resource/server/service'] == 'aliyunoss') {
            if ($needSign) {
                $urlResult = resourceFactory::aliYunOSSSignedUrl('pub/upload/' . $path, [], 3600);
                if ($urlResult['error'] == 0) {
                    return $urlResult['sigined_url'];
                } else {
                    return $this->getPubUrl('frontend/images/placeholder.png');
                }
            } else {
                $urlResult = resourceFactory::aliYunOSSUrl('pub/upload/' . $path, [], 3600);
                if ($urlResult['error'] == 0) {
                    return $urlResult['url'];
                } else {
                    return $this->getPubUrl('frontend/images/placeholder.png');
                }
            }
        } else {
            $base = $this->getContainer()->get('config')['global/url/cookie_free_domain'];
            return $this->getBaseUrl('pub/upload/' . $path);
        }
    }
}
