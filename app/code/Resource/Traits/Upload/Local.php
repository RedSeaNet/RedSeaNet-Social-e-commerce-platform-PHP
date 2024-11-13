<?php

namespace Redseanet\Resource\Traits\Upload;

use Redseanet\Lib\Http\UploadedFile;
use Redseanet\Lib\Session\Segment;
use Redseanet\Resource\Model\Collection\Resource as Collection;
use Redseanet\Resource\Lib\Factory as resourceFactory;

trait Local
{
    /**
     * @param UploadedFile $file
     * @return Resource
     * @throws Exception
     */
    public function moveFile($file)
    {
        $config = $this->getContainer()->get('config');
        //$newName = $file->getClientFilename();
        $newName = 'res-' . date('YmdHis') . '-' . mt_rand(10000, 99999) . '.' . strtolower(substr(strrchr($file->getClientFilename(), '.'), 1));
        $type = substr($file->getClientMediaType(), 0, strpos($file->getClientMediaType(), '/') + 1);
        $path = BP . static::$options['path'] . $type;
        if (!is_dir($path)) {
            mkdir($path, static::$options['dir_mode'], true);
        }
        $md5 = empty($file->getTmpFilename()) ? '' : md5_file($file->getTmpFilename());
        while (file_exists($path . $newName)) {
            $newName = preg_replace('/(\.[^\.]+$)/', random_int(0, 9) . '$1', $newName);
            if (strlen($newName) >= 120) {
                throw new Exception('The file is existed.');
            }
        }
        $file->moveTo($path . $newName);
        if (isset($config['resource/server/service']) && $config['resource/server/service'] == 'aliyunoss') {
            $aliyunConfig = resourceFactory::getAliYunOSSConfig();
            $aliyunConfig['localfilepath'] = $path . $newName;
            $aliyunConfig['ossobject'] = static::$options['path'] . $type . $newName;
            resourceFactory::aliYunOSSMoveFile($aliyunConfig);
        }
        $this->setData(['md5' => $md5, 'real_name' => rawurlencode($newName), 'size' => (int) $file->getSize()]);
        return $this;
    }

    /**
     * @param UploadedFile $file
     * @param string $contentRange
     * @return bool
     */
    public function chunk($file, $contentRange)
    {
        preg_match('/^bytes\s(?P<from>\d+)\-(?P<to>\d+)\/(?P<end>\d+)$/', trim($contentRange), $range);
        $path = BP . 'var/temp/';
        if (!is_dir($path)) {
            mkdir($path, static::$options['dir_mode'], true);
        }
        $name = $path . session_id() . $file->getClientFilename();
        while (file_exists($name) && $range['from'] != filesize($name)) {
            sleep(5);
        }
        $fp = fopen($name, file_exists($name) ? 'ab' : 'wb');
        fwrite($fp, $file->getStream()->getContents());
        fclose($fp);
        if ($range['to'] >= $range['end'] - 1) {
            $this->moveFile(new UploadedFile($name, $file->getClientFilename(), $file->getClientMediaType(), (int) $range['end']));
            return true;
        }
        return false;
    }
}
