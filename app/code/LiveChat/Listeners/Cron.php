<?php

namespace Redseanet\LiveChat\Listeners;

use Symfony\Component\Finder\Finder;

class Cron
{
    use \Redseanet\Lib\Traits\Container;

    public function schedule()
    {
        $config = $this->getContainer()->get('config');
        if ($config['livechat/expiration'] && is_dir(BP . 'pub/upload/livechat')) {
            $finder = new Finder();
            $finder->files()->in(BP . 'pub/upload/livechat')->date('<= -' . $config['livechat/expiration'] . ' days');
            $ts = strtotime('-' . $config['livechat/expiration'] . ' days');
            foreach ($finder as $file) {
                if ($file->getATime() <= $ts) {
                    unlink($file->getRealPath());
                }
            }
        }
    }
}
