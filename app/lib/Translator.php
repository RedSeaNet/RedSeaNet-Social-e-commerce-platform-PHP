<?php

namespace Redseanet\Lib;

use Locale;
use Redseanet\I18n\Model\Collection\Translation;
use Redseanet\Lib\Stdlib\Singleton;
use Redseanet\Lib\Translator\Category;
use SplFileObject;
use Symfony\Component\Finder\Finder;

/**
 * Translate service
 */
class Translator implements Singleton
{
    use \Redseanet\Lib\Traits\Container;

    public const DEFAULT_DOMAIN = 'default';
    public const CACHE_KEY = 'TRANSLATOR_PAIRS_';

    /**
     * @var Translator
     */
    protected static $instance = null;

    /**
     * @var array
     */
    protected $storage = [];

    /**
     * @var string
     */
    protected $locale = null;

    /**
     * @var string
     */
    protected static $defaultLocale = null;

    /**
     * @param Container $container
     */
    private function __construct($container = null)
    {
        if ($container instanceof Container) {
            $this->setContainer($container);
        }
    }

    public static function instance($container = null)
    {
        if (is_null(static::$instance)) {
            static::$instance = new static($container);
        }
        return static::$instance;
    }

    /**
     * @param string $locale
     */
    public static function setDefaultLocale($locale)
    {
        static::$defaultLocale = $locale;
    }

    /**
     * @return string
     */
    public static function getDefaultLocale()
    {
        if (is_null(static::$defaultLocale)) {
            static::$defaultLocale = Locale::getDefault();
        }
        return static::$defaultLocale;
    }

    /**
     * @param string $locale
     * @return Translator
     */
    public function setLocale($locale)
    {
        $this->locale = $locale;
        return $this;
    }

    /**
     * @return string
     */
    public function getLocale()
    {
        return $this->locale ?: static::getDefaultLocale();
    }

    /**
     * Load translate pairs from csv files
     *
     * @param string $locale
     * @return array of Category
     */
    protected function loadMessages($locale)
    {
        if (!isset($this->storage[$locale])) {
            $cache = $this->getContainer()->get('cache');
            $result = $cache->fetch($locale, static::CACHE_KEY);
            if ($result) {
                $this->storage[$locale] = $result;
                return $this->storage[$locale];
            }
            $this->storage[$locale] = [];
            $collection = new Translation();
            $collection->where(['status' => 1, 'locale' => $locale]);
            $result = [];
            foreach ($collection as $item) {
                $result[$item['string']] = $item['translate'];
            }
            $this->storage[$locale][static::DEFAULT_DOMAIN] = new Category($result);
            $finder = new Finder();
            $finder->files()->in(BP . 'app/i18n/' . $locale)->name('*.csv');
            foreach ($finder as $file) {
                if (is_readable($file->getRealPath())) {
                    $domain = str_replace('.csv', '', $file->getFilename());
                    $this->storage[$locale][$domain] = $this->readFile($file->getRealPath());
                    $this->storage[$locale][static::DEFAULT_DOMAIN]->merge($this->storage[$locale][$domain]);
                }
            }
            $cache->save($locale, $this->storage[$locale], static::CACHE_KEY);
        }
        return $this->storage[$locale];
    }

    /**
     * @param string $path
     * @return Category
     */
    protected function readFile($path)
    {
        $messages = new Category();

        $file = new SplFileObject($path, 'rb');
        $file->setFlags(SplFileObject::READ_CSV | SplFileObject::SKIP_EMPTY);

        while (!$file->eof()) {
            $line = trim($file->fgets());
            $data = str_getcsv($line);
            if (!empty($data[0]) && '#' !== substr($data[0], 0, 1) && isset($data[1]) && 2 === count($data)) {
                $messages->offsetSet($data[0], rtrim($data[1], '"'));
            }
        }

        return $messages;
    }

    /**
     * Translate messages
     *
     * @param string $message
     * @param array $parameters
     * @param string $domain
     * @param string $locale
     * @return string
     */
    public function translate($message, array $parameters = [], $domain = null, $locale = null)
    {
        if (is_null($locale)) {
            $locale = $this->getLocale();
        }
        if (!$message || !is_string($message)) {
            return '';
        }
        $messages = $this->loadMessages($locale);
        if (empty($messages)) {
            return vsprintf($message, $parameters);
        } elseif (!is_null($domain) && isset($messages[$domain]) && $messages[$domain]->offsetExists($message)) {
            return vsprintf($messages[$domain]->offsetGet($message), $parameters);
        } elseif ($messages[static::DEFAULT_DOMAIN]->offsetExists($message)) {
            return vsprintf($messages[static::DEFAULT_DOMAIN]->offsetGet($message), $parameters);
        } else {
            return vsprintf($message, $parameters);
        }
    }
}
