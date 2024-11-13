<?php

namespace Redseanet\Admin\Controller;

use Redseanet\Lib\Controller\AuthActionController;
use Symfony\Component\Finder\Finder;

class CacheController extends AuthActionController
{
    public function indexAction()
    {
        return $this->getLayout('admin_cache');
    }

    public function flushAction()
    {
        $code = $this->getRequest()->getQuery('code');
        $cache = $this->getContainer()->get('cache');
        $result = ['message' => [], 'error' => 0];
        $eventDispatcher = $this->getContainer()->get('eventDispatcher');
        if ($code) {
            $count = 0;
            foreach ((array) $code as $prefix) {
                $list = $cache->fetch('CACHE_LIST_' . $prefix);
                $eventDispatcher->trigger($prefix . '.cache.delete.before', ['prefix' => $prefix, 'list' => $list]);
                $cache->delete('', $prefix);
                $eventDispatcher->trigger($prefix . '.cache.delete.after', ['prefix' => $prefix]);
                $count++;
            }
            $result['message'][] = ['message' => $this->translate('%d cache(s) have been flushed successfully.', [$count]), 'level' => 'success'];
        } else {
            $eventDispatcher->trigger('allcache.delete.before');
            $cache->flushAll();
            $this->deleteImage(BP . 'pub/resource/image/resized');
            $eventDispatcher->trigger('allcache.delete.after');
            $result['message'][] = ['message' => $this->translate('All caches have been flushed successfully.'), 'level' => 'success'];
        }
        return $this->response($result, ':ADMIN/cache/');
    }

    private function deleteImage($dir)
    {
        if (is_dir($dir)) {
            if ($dp = opendir($dir)) {
                $dir = rtrim($dir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
                while (($file = readdir($dp)) !== false) {
                    if (is_dir($dir . $file)) {
                        if ($file != '.' && $file != '..') {
                            $this->deleteImage($dir . $file);
                        }
                    } else {
                        unlink($dir . $file);
                    }
                }
                closedir($dp);
            }
            rmdir($dir);
        }
    }

    public function pudgeImageAction()
    {
        $this->deleteImage(BP . 'pub/resource/image/resized');
        return $this->response(['error' => 0, 'message' => [['message' => $this->translate('Image Cache has been pudged successfully.'), 'level' => 'success']]], ':ADMIN/cache/');
    }

    public function pudgeCSSAction()
    {
        $finder = new Finder();
        $finder->files()->in(BP . 'pub/theme/')->name('/^[^\_].+\.(?:le|sc|sa)ss$/');
        foreach ($finder as $file) {
            $path = substr_replace($file->getRealPath(), 'css', -4);
            if (file_exists($path)) {
                unlink($path);
            }
        }
        return $this->response(['error' => 0, 'message' => [['message' => $this->translate('CSS Cache has been pudged successfully.'), 'level' => 'success']]], ':ADMIN/cache/');
    }

    public function listAction()
    {
        $code = $this->getRequest()->getQuery('code');
        $cache = $this->getContainer()->get('cache');
        $result = ['message' => [], 'error' => 0];
        $keys = $cache->getAllKeys();
        var_dump($keys);
        exit;
    }
}
