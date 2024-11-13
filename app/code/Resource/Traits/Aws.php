<?php

namespace Redseanet\Resource\Traits;

use Aws\S3\S3Client;
use Aws\Exception\AwsException;
use Redseanet\Lib\Bootstrap;
use Aws\Pinpoint\PinpointClient;
use Aws\Pinpoint\Exception;
use Aws\Ses\SesClient;

/**
 * Database handler
 */
trait Aws
{
    public function getAwsConfig()
    {
        $configR = [];
        $config = Bootstrap::getContainer()->get('config');

        $configR['key'] = $config['resource/awss3/key'];
        $configR['secret'] = $config['resource/awss3/secret'];
        $configR['bucket'] = $config['resource/awss3/bucket'];

        $configR['path'] = '';
        $configR['filename'] = '';
        $configR['bucketPath'] = '';

        $configR['policy'] = $config['resource/awss3/policy'];
        $configR['region'] = $config['resource/awss3/region'];
        $configR['ACL'] = $config['resource/awss3/ACL'];
        $configR['version'] = $config['resource/awss3/version'];

        $configR['pinpointapplicationid'] = $config['resource/awss3/pinpointapplicationid'];
        $configR['pinpointoriginationnumber'] = '+' . $config['resource/awss3/pinpointoriginationnumber'];
        $configR['pinpointsenderid'] = $config['resource/awss3/pinpointsenderid'];
        $configR['pinpointregisteredkeyword'] = $config['resource/awss3/pinpointregisteredkeyword'];

        return $configR;
    }

    public function s3MoveFile($config = [])
    {
        $results = ['error' => 1, 'message' => ''];
        //var_dump($config);
        if ($config['bucket'] == '') {
            $results = ['error' => 0, 'message' => 'S3 bucket name not allow null'];
            Bootstrap::getContainer()->get('log')->logException(json_decode($results));
            return $results;
        }

        if ($config['key'] == '') {
            $results = ['error' => 0, 'message' => 'S3 connectting key not allow null'];
            Bootstrap::getContainer()->get('log')->logException(json_decode($results));
            return $results;
        }
        if ($config['secret'] == '') {
            $results = ['error' => 0, 'message' => 'S3 connectting secret not allow null'];
            Bootstrap::getContainer()->get('log')->logException(json_decode($results));
            return $results;
        }
        if ($config['path'] == '') {
            $results = ['error' => 0, 'message' => 'S3 path not allow null'];
            Bootstrap::getContainer()->get('log')->logException(json_decode($results));
            return $results;
        }
        if ($config['filename'] == '') {
            $results = ['error' => 0, 'message' => 'S3 file name not allow null'];
            Bootstrap::getContainer()->get('log')->logException(json_decode($results));
            return $results;
        }
        $s3 = new S3Client([
            'version' => $config['version'],
            'region' => $config['region'],
            'credentials' => [
                'key' => $config['key'], //访问秘钥
                'secret' => $config['secret'] //私有访问秘钥
            ]
        ]);

        try {
            $result = $s3->putObject([
                'Bucket' => $config['bucket'],
                'Key' => $config['bucketPath'] . $config['filename'],
                'Body' => fopen($config['path'], $config['policy']),
                'ACL' => $config['ACL']
            ]);
            //   $result = $s3->putObject([
            //        'Bucket' =>'153resources',
            //        'Key'    =>'resources/image/dennytest.jpg',
            //        'Body'   => fopen('/var/www/html/server/pub/resource/image/dennytest.jpg', 'r'),
            //        'ACL'    => ''
            //    ]);
            //   echo $result->get('ObjectURL');
            $results = ['error' => 1, 'message' => $result->get('ObjectURL')];
        } catch (Aws\S3\Exception\S3Exception $e) {
            //    echo "There was an error uploading the file.\n";
            //    echo $e->getMessage();
            Bootstrap::getContainer()->get('log')->logException($e->getMessage());
            $results = ['error' => 0, 'message' => $e->getMessage()];
        }
        return $results;
    }

    public function s3MoveFileBase64($config = [])
    {
        $results = ['error' => 1, 'message' => ''];
        if ($config['bucket'] == '') {
            $results = ['error' => 0, 'message' => 'S3 bucket name not allow null'];
            Bootstrap::getContainer()->get('log')->logException(json_decode($results));
            return $results;
        }

        if ($config['key'] == '') {
            $results = ['error' => 0, 'message' => 'S3 connectting key not allow null'];
            Bootstrap::getContainer()->get('log')->logException(json_decode($results));
            return $results;
        }
        if ($config['secret'] == '') {
            $results = ['error' => 0, 'message' => 'S3 connectting secret not allow null'];
            Bootstrap::getContainer()->get('log')->logException(json_decode($results));
            return $results;
        }
        if ($config['path'] == '') {
            $results = ['error' => 0, 'message' => 'S3 path not allow null'];
            Bootstrap::getContainer()->get('log')->logException(json_decode($results));
            return $results;
        }
        if ($config['filename'] == '') {
            $results = ['error' => 0, 'message' => 'S3 file name not allow null'];
            Bootstrap::getContainer()->get('log')->logException(json_decode($results));
            return $results;
        }
        $s3 = new S3Client([
            'version' => $config['version'],
            'region' => $config['region'],
            'credentials' => [
                'key' => $config['key'], //访问秘钥
                'secret' => $config['secret'] //私有访问秘钥
            ]
        ]);
        try {
            $result = $s3->putObject([
                'Bucket' => $config['bucket'],
                'Key' => $config['bucketPath'] . $config['filename'],
                'Body' => $config['body'],
                'ContentType' => $config['ContentType'],
                'ACL' => $config['ACL']
            ]);
            $results = ['error' => 1, 'message' => $result->get('ObjectURL')];
        } catch (Aws\S3\Exception\S3Exception $e) {
            Bootstrap::getContainer()->get('log')->logException($e->getMessage());
            $results = ['error' => 0, 'message' => $e->getMessage()];
        }
        return $results;
    }

    public function s3DeleteFile($config = [])
    {
        $results = ['error' => 1, 'message' => ''];
        if ($config['bucket'] == '') {
            $results = ['error' => 0, 'message' => 'S3 bucket name not allow null'];
            Bootstrap::getContainer()->get('log')->logException(json_decode($results));
            return $results;
        }

        if ($config['key'] == '') {
            $results = ['error' => 0, 'message' => 'S3 connectting key not allow null'];
            Bootstrap::getContainer()->get('log')->logException(json_decode($results));
            return $results;
        }
        if ($config['secret'] == '') {
            $results = ['error' => 0, 'message' => 'S3 connectting secret not allow null'];
            Bootstrap::getContainer()->get('log')->logException(json_decode($results));
            return $results;
        }

        if ($config['filename'] == '') {
            $results = ['error' => 0, 'message' => 'S3 file name not allow null'];
            Bootstrap::getContainer()->get('log')->logException(json_decode($results));
            return $results;
        }
        $s3 = new S3Client([
            'version' => $config['version'],
            'region' => $config['region'],
            'credentials' => [
                'key' => $config['key'], //访问秘钥
                'secret' => $config['secret'] //私有访问秘钥
            ]
        ]);
        try {
            $s3->deleteObject([
                'Bucket' => $config['bucket'],
                'Key' => $config['bucketPath'] . $config['filename']
            ]);
            $results = ['error' => 1, 'message' => $result->get('ObjectURL')];
        } catch (Aws\S3\Exception\S3Exception $e) {
            Bootstrap::getContainer()->get('log')->logException($e->getMessage());
            $results = ['error' => 0, 'message' => $e->getMessage()];
        }
        return $results;
    }

    public function sendSmsMessage($destinationNumber = '', $message = '')
    {
        $destinationNumber = '+' . $destinationNumber;
        $awsConfig = $this->getAwsConfig();
        $client = PinpointClient::factory([
            //'profile'=>'CREDENTIAL_PROFILE', //Or you can provide the raw credentials as you did in your sample
            'region' => 'us-east-1',
            'version' => 'latest',
            'credentials' => ['key' => $awsConfig['key'], 'secret' => $awsConfig['secret']]
        ]);
        try {
            $sendResult = $client->sendMessages([
                'ApplicationId' => $awsConfig['pinpointapplicationid'], // REQUIRED
                'MessageRequest' => [// REQUIRED
                    'Addresses' => [
                        $destinationNumber => [
                            'ChannelType' => 'SMS'
                        ]
                    ],
                    'MessageConfiguration' => [
                        'SMSMessage' => [
                            'Body' => $message,
                            'Keyword' => $awsConfig['pinpointregisteredkeyword'],
                            'MessageType' => 'TRANSACTIONAL',
                            'OriginationNumber' => $awsConfig['pinpointoriginationnumber'],
                            'SenderId' => $awsConfig['pinpointsenderid'],
                            'destinationNumber' => $destinationNumber
                        ]
                    ]
                ]
            ]);
            return $sendResult['MessageResponse']['Result'][$destinationNumber];
        } catch (Exception $ex) {
            return ['DeliveryStatus' => 'FAILED', 'StatusCode' => $sendResult['StatusCode']];
        }
    }

    public function getPresignedUrl($files = [])
    {
        $configR = [];
        $config = Bootstrap::getContainer()->get('config');

        $configR['key'] = $config['resource/awss3/key'];
        $configR['secret'] = $config['resource/awss3/secret'];
        $configR['bucket'] = $config['resource/awss3/bucket'];

        $configR['path'] = '';
        $configR['filename'] = '';
        $configR['bucketPath'] = '';

        $configR['policy'] = $config['resource/awss3/policy'];
        $configR['region'] = $config['resource/awss3/region'];
        $configR['ACL'] = $config['resource/awss3/ACL'];
        $configR['version'] = $config['resource/awss3/version'];

        $configR['pinpointapplicationid'] = $config['resource/awss3/pinpointapplicationid'];
        $configR['pinpointoriginationnumber'] = '+' . $config['resource/awss3/pinpointoriginationnumber'];
        $configR['pinpointsenderid'] = $config['resource/awss3/pinpointsenderid'];
        $configR['pinpointregisteredkeyword'] = $config['resource/awss3/pinpointregisteredkeyword'];

        $s3Client = new S3Client([
            //'profile' => 'default',
            'region' => $configR['region'],
            'version' => $configR['version'],
            'credentials' => [
                'key' => $configR['key'], //访问秘钥
                'secret' => $configR['secret'] //私有访问秘钥
            ]
        ]);
        $presignedUrl = [];
        for ($i = 0; $i < count($files); $i++) {
            //var_dump($files[$i]);
            $cmd = $s3Client->getCommand('putObject', [
                'Bucket' => $configR['bucket'],
                'Key' => $files[$i]['key'],
                'ContentType' => $files[$i]['type']
            ]);
            $request = $s3Client->createPresignedRequest($cmd, '+30 minutes');
            $files[$i]['presigedurl'] = (string) $request->getUri();
            $presignedUrl[] = $files[$i];
        }

        return $presignedUrl;
    }
}
