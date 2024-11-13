<?php

namespace Redseanet\LiveChat\Controller;

use Redseanet\Customer\Controller\AuthActionController;

class UploadController extends AuthActionController
{
    public function indexAction()
    {
        if ($this->getRequest()->isPost()) {
            $file = $this->getRequest()->getUploadedFile();
            $class = new class () {
                use \Redseanet\Resource\Traits\Upload\Local;

                public static $options = [
                    'path' => 'pub/upload/livechat/',
                    'dir_mode' => 0755
                ];
                public $name;

                protected function setData($data)
                {
                    $this->name = $data['real_name'];
                }
            };
            if ($class->chunk($file['file'], $this->getRequest()->getHeader('HTTP_CONTENT_RANGE')['HTTP_CONTENT_RANGE'])) {
                return $this->getBaseUrl('pub/upload/livechat/' .
                        substr($file['file']->getClientMediaType(), 0, strpos($file['file']->getClientMediaType(), '/') + 1) . $class->name);
            }
        }
        exit();
    }
}
