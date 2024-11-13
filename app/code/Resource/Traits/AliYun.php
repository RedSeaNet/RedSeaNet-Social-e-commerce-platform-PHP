<?php

namespace Redseanet\Resource\Traits;

use Redseanet\Lib\Bootstrap;
use OSS\OssClient;
use OSS\Core\OssException;

/**
 * Database handler
 */
trait AliYun
{
    public function getAliYunOSSConfig()
    {
        $configR = [];
        $config = Bootstrap::getContainer()->get('config');

        $configR['key'] = $config['resource/server/aliyunossaccesskey'];
        $configR['secret'] = $config['resource/server/aliyunossaccessecret'];
        $configR['bucket'] = $config['resource/server/aliyunossbucket'];
        $configR['endpoint'] = $config['resource/server/aliyunossendpoint'];
        return $configR;
    }

    public function aliYunOSSMoveFile($config = [])
    {
        $results = ['error' => 1, 'message' => ''];
        //var_dump($config);
        if ($config['bucket'] == '') {
            $results = ['error' => 0, 'message' => 'aliyun oss bucket name not allow null'];
            Bootstrap::getContainer()->get('log')->logException(json_decode($results));
            return $results;
        }

        if ($config['key'] == '') {
            $results = ['error' => 0, 'message' => 'aliyun oss connectting key not allow null'];
            Bootstrap::getContainer()->get('log')->logException(json_decode($results));
            return $results;
        }
        if ($config['secret'] == '') {
            $results = ['error' => 0, 'message' => 'aliyun oss connectting secret not allow null'];
            Bootstrap::getContainer()->get('log')->logException(json_decode($results));
            return $results;
        }
        if ($config['endpoint'] == '') {
            $results = ['error' => 0, 'message' => 'aliyun oss connectting endpoint not allow null'];
            Bootstrap::getContainer()->get('log')->logException(json_decode($results));
            return $results;
        }
        if ($config['localfilepath'] == '') {
            $results = ['error' => 0, 'message' => 'aliyun oss connectting localfilepath not allow null'];
            Bootstrap::getContainer()->get('log')->logException(json_decode($results));
            return $results;
        }
        if ($config['ossobject'] == '') {
            $results = ['error' => 0, 'message' => 'aliyun oss connectting ossobject not allow null'];
            Bootstrap::getContainer()->get('log')->logException(json_decode($results));
            return $results;
        }
        //        $options = array(
        //            OssClient::OSS_HEADERS => array(
        //                'x-oss-object-acl' => 'private',
        //                'x-oss-meta-info' => 'your info'
        //            ),
        //        );
        try {
            $ossClient = new OssClient($config['key'], $config['secret'], $config['endpoint']);
            $ossClient->uploadFile($config['bucket'], $config['ossobject'], $config['localfilepath']);
        } catch (OssException $e) {
            $results = ['error' => 0, 'message' => $e->getMessage()];
            Bootstrap::getContainer()->get('log')->logException($e->getMessage());
        }
        return $results;
    }

    public function aliYunOSSSignedUrls($config = [], $objects = [], $options = [], $timeout = 3600)
    {
        $results = ['error' => 1, 'message' => ''];
        //var_dump($config);
        if ($config['bucket'] == '') {
            $results = ['error' => 0, 'message' => 'aliyun oss bucket name not allow null'];
            Bootstrap::getContainer()->get('log')->logException(json_decode($results));
            return $results;
        }

        if ($config['key'] == '') {
            $results = ['error' => 0, 'message' => 'aliyun oss connectting key not allow null'];
            Bootstrap::getContainer()->get('log')->logException(json_decode($results));
            return $results;
        }
        if ($config['secret'] == '') {
            $results = ['error' => 0, 'message' => 'aliyun oss connectting secret not allow null'];
            Bootstrap::getContainer()->get('log')->logException(json_decode($results));
            return $results;
        }
        if ($config['endpoint'] == '') {
            $results = ['error' => 0, 'message' => 'aliyun oss connectting endpoint not allow null'];
            Bootstrap::getContainer()->get('log')->logException(json_decode($results));
            return $results;
        }
        $ossClient = new OssClient($config['key'], $config['secret'], $config['endpoint']);
        $signedUrl = [];
        for ($o = 0; $o < count($objects); $o++) {
            $signedUrl[] = $ossClient->signUrl($config['bucket'], $objects[$o], $timeout, 'GET', $options);
        }
        $results = ['error' => 0, 'message' => '', 'sigined_urls' => $signedUrl];
        return $results;
    }

    public function aliYunOSSSignedUrl($config = [], $object, $options = [], $timeout = 3600)
    {
        $results = ['error' => 1, 'message' => ''];
        //var_dump($config);
        if ($config['bucket'] == '') {
            $results = ['error' => 0, 'message' => 'aliyun oss bucket name not allow null'];
            Bootstrap::getContainer()->get('log')->logException(json_decode($results));
            return $results;
        }

        if ($config['key'] == '') {
            $results = ['error' => 0, 'message' => 'aliyun oss connectting key not allow null'];
            Bootstrap::getContainer()->get('log')->logException(json_decode($results));
            return $results;
        }
        if ($config['secret'] == '') {
            $results = ['error' => 0, 'message' => 'aliyun oss connectting secret not allow null'];
            Bootstrap::getContainer()->get('log')->logException(json_decode($results));
            return $results;
        }
        if ($config['endpoint'] == '') {
            $results = ['error' => 0, 'message' => 'aliyun oss connectting endpoint not allow null'];
            Bootstrap::getContainer()->get('log')->logException(json_decode($results));
            return $results;
        }
        $ossClient = new OssClient($config['key'], $config['secret'], $config['endpoint']);
        $signedUrl = $ossClient->signUrl($config['bucket'], $object, $timeout, 'GET', $options);
        $results = ['error' => 0, 'message' => '', 'sigined_url' => $signedUrl];
        return $results;
    }
}
