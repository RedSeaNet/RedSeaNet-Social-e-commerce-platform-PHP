<?php

namespace Redseanet\Resource\Controller;

use Redseanet\Lib\Controller\ActionController;

class ResizeController extends ActionController
{
    use \Redseanet\Resource\Traits\Resize;

    public function indexAction()
    {
        $retina = 1;
        $filename = rawurldecode($this->getOption('file'));
        if (preg_match('/@(?P<retina>[\d\.]+)x\./', $filename, $matches)) {
            $retina = (float) $matches['retina'];
            $filename = str_replace('@' . $retina . 'x', '', $filename);
        }
        $file = BP . 'pub/resource/image/' . $filename;
        if (!file_exists($file)) {
            return $this->notFoundAction();
        }
        $resized = BP . 'pub/resource/image/resized/' . $this->getOption('width') . 'x' . $this->getOption('height') . '/' . $filename;
        $path = dirname($resized);
        if (!is_dir($path)) {
            mkdir($path, 0777, true);
        }
        $image = $this->resize($file, (int) ($this->getOption('width') * $retina), (int) (($this->getOption('height') ?: 0) * $retina));
        $image->save($resized);
        $image->show(substr($resized, strrpos($resized, '.') + 1));
        exit;
    }
}
