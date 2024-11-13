<?php

namespace Redseanet\Resource\Lib;

use Redseanet\Lib\Bootstrap;
use OSS\OssClient;
use OSS\Core\OssException;
use Aws\S3\S3Client;
use Aws\Exception\AwsException;
use Aws\Pinpoint\PinpointClient;
use Aws\Pinpoint\Exception;
use Aws\Ses\SesClient;

abstract class Factory
{
    public static function uploadFileToClound($localPath, $objectUrl, $fileName)
    {
        $config = Bootstrap::getContainer()->get('config');
        if (isset($config['resource/server/service']) && $config['resource/server/service'] == 'aliyunoss') {
            $aliyunConfig = self::getAliYunOSSConfig();
            $aliyunConfig['localfilepath'] = $localPath;
            $aliyunConfig['ossobject'] = $objectUrl;
            self::aliYunOSSMoveFile($aliyunConfig);
        } elseif (isset($config['resource/server/service']) && $config['resource/server/service'] == 'awss3') {
            $awss3Config = self::getAwsS3Config();
            $awss3Config['localfilepath'] = $localPath;
            $awss3Config['filename'] = $fileName;
            $awss3Config['ossobject'] = $objectUrl;
            self::awss3MoveFile($awss3Config);
        }
    }

    public static function getAliYunOSSConfig()
    {
        $configR = [];
        $config = Bootstrap::getContainer()->get('config');

        $configR['key'] = $config['resource/server/aliyunossaccesskey'];
        $configR['secret'] = $config['resource/server/aliyunossaccessecret'];
        $configR['bucket'] = $config['resource/server/aliyunossbucket'];
        $configR['endpoint'] = $config['resource/server/aliyunossendpoint'];
        $configR['aliyunossendurl'] = $config['resource/server/aliyunossendurl'];
        return $configR;
    }

    public static function aliYunOSSMoveFile($config = [])
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
            print_r($e->getMessage());
            Bootstrap::getContainer()->get('log')->logException($e->getMessage());
        }
        return $results;
    }

    public static function aliYunOSSSignedUrls($config = [], $objects = [], $options = [], $timeout = 3600)
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

    public static function aliYunOSSSignedUrl($object, $options = [], $timeout = 3600)
    {
        $config = self::getAliYunOSSConfig();

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

    public static function aliYunOSSUrl($object)
    {
        $config = self::getAliYunOSSConfig();
        $results = ['error' => 1, 'message' => ''];
        if ($config['endpoint'] == '') {
            $results = ['error' => 0, 'message' => 'aliyun oss connectting endpoint not allow null'];
            Bootstrap::getContainer()->get('log')->logException(json_decode($results));
            return $results;
        }
        $results = ['error' => 0, 'message' => '', 'url' => $config['aliyunossendurl'] . $object];
        return $results;
    }

    public static function aliYunOSSObjectDelete($object)
    {
        $config = self::getAliYunOSSConfig();
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
        $deleteResult = $ossClient->deleteObject($config['bucket'], $object);
        return true;
    }

    public static function getAwsS3Config()
    {
        $configR = [];
        $config = Bootstrap::getContainer()->get('config');
        $configR['key'] = $config['resource/server/awss3accesskey'];
        $configR['secret'] = $config['resource/server/awss3accessecret'];
        $configR['bucket'] = $config['resource/server/awss3bucket'];
        $configR['path'] = '';
        $configR['filename'] = '';
        $configR['bucketPath'] = '';
        $configR['policy'] = $config['resource/server/awss3policy'];
        $configR['region'] = $config['resource/server/awss3region'];
        $configR['ACL'] = $config['resource/server/awss3acl'];
        $configR['version'] = $config['resource/server/awss3version'];
        return $configR;
    }

    public static function awss3MoveFile($config = [])
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
        if (!isset($config['ossobject']) || $config['ossobject'] == '') {
            $results = ['error' => 0, 'message' => 'S3 ossobject not allow null'];
            Bootstrap::getContainer()->get('log')->logException(json_decode($results));
            return $results;
        }
        if (!isset($config['filename']) && $config['filename'] == '') {
            $results = ['error' => 0, 'message' => 'S3 file name not allow null'];
            Bootstrap::getContainer()->get('log')->logException(json_decode($results));
            return $results;
        }
        $credentials = new Credentials($config['key'], $config['secret']);
        $s3Client = new S3Client([
            'version' => $config['version'],
            'region' => $config['region'],
            //'debug' => true,
            'credentials' => $credentials
        ]);
        try {
            $result = $s3Client->putObject([
                'Bucket' => $config['bucket'],
                'Key' => $config['ossobject'],
                'Body' => fopen($config['localfilepath'], 'r')
                //'ACL' => 'public-read'
            ]);
            $results = ['error' => 1, 'message' => $result->get('ObjectURL')];
        } catch (Aws\S3\Exception\S3Exception $e) {
            Bootstrap::getContainer()->get('log')->logException($e->getMessage());
            $results = ['error' => 0, 'message' => $e->getMessage()];
        }
        return $results;
    }

    public static function getAwsS3PresignedUrl($files = [])
    {
        $configR = [];
        $config = Bootstrap::getContainer()->get('config');

        $configR['key'] = $config['resource/server/awss3accesskey'];
        $configR['secret'] = $config['resource/server/awss3accessecret'];
        $configR['bucket'] = $config['resource/server/awss3bucket'];
        $configR['path'] = '';
        $configR['filename'] = '';
        $configR['bucketPath'] = '';
        $configR['policy'] = $config['resource/server/awss3policy'];
        $configR['region'] = $config['resource/server/awss3region'];
        $configR['ACL'] = $config['resource/server/awss3acl'];
        $configR['version'] = $config['resource/server/awss3version'];
        $credentials = new Credentials($configR['key'], $configR['secret']);

        $s3Client = new S3Client([
            'version' => $configR['version'],
            'region' => $configR['region'],
            //'debug' => true,
            'credentials' => $credentials
        ]);
        $presignedUrl = [];
        for ($i = 0; $i < count($files); $i++) {
            $cmd = $s3Client->getCommand('putObject', [
                'Bucket' => $configR['bucket'],
                'Key' => $files[$i]['key'],
                'ContentType' => $files[$i]['type']
            ]);
            $request = $s3Client->createPresignedRequest($cmd, '+60 minutes');
            $files[$i]['presigedurl'] = (string) $request->getUri();
            $presignedUrl[] = $files[$i];
        }
        return $presignedUrl;
    }

    public static function awsS3DeleteObject($deleteFileKey)
    {
        $configR = [];
        $config = Bootstrap::getContainer()->get('config');
        $configR['key'] = $config['resource/server/awss3accesskey'];
        $configR['secret'] = $config['resource/server/awss3accessecret'];
        $configR['bucket'] = $config['resource/server/awss3bucket'];
        $configR['path'] = '';
        $configR['filename'] = '';
        $configR['bucketPath'] = '';
        $configR['policy'] = $config['resource/server/awss3policy'];
        $configR['region'] = $config['resource/server/awss3region'];
        $configR['ACL'] = $config['resource/server/awss3acl'];
        $configR['version'] = $config['resource/server/awss3version'];
        $s3Client = new S3Client([
            'region' => $configR['region'],
            'version' => $configR['version'],
            'credentials' => [
                'key' => $configR['key'],
                'secret' => $configR['secret']
            ]
        ]);
        try {
            $result = $s3Client->deleteObject([
                'Bucket' => $configR['bucket'],
                'Key' => $deleteFileKey,
            ]);
        } catch (S3Exception $e) {
            echo $e->getMessage() . "\n";
        }
    }

    public static function awsCheckObject($objectkey)
    {
        $configR = [];
        $config = Bootstrap::getContainer()->get('config');
        $configR['key'] = $config['resource/server/awss3accesskey'];
        $configR['secret'] = $config['resource/server/awss3accessecret'];
        $configR['bucket'] = $config['resource/server/awss3bucket'];
        $configR['path'] = '';
        $configR['filename'] = '';
        $configR['bucketPath'] = '';
        $configR['policy'] = $config['resource/server/awss3policy'];
        $configR['region'] = $config['resource/server/awss3region'];
        $configR['ACL'] = $config['resource/server/awss3acl'];
        $configR['version'] = $config['resource/server/awss3version'];
        $s3Client = new S3Client([
            'region' => $configR['region'],
            'version' => $configR['version'],
            'credentials' => [
                'key' => $configR['key'],
                'secret' => $configR['secret']
            ]
        ]);
        $response = $s3Client->doesObjectExist($configR['bucket'], $objectkey);
        return $response;
    }

    public static function awsGetobjectAndSave($key, $savepath)
    {
        $configR = [];
        $config = Bootstrap::getContainer()->get('config');
        $configR['key'] = $config['resource/server/awss3accesskey'];
        $configR['secret'] = $config['resource/server/awss3accessecret'];
        $configR['bucket'] = $config['resource/server/awss3bucket'];
        $configR['path'] = '';
        $configR['filename'] = '';
        $configR['bucketPath'] = '';
        $configR['policy'] = $config['resource/server/awss3policy'];
        $configR['region'] = $config['resource/server/awss3region'];
        $configR['ACL'] = $config['resource/server/awss3acl'];
        $configR['version'] = $config['resource/server/awss3version'];
        $s3Client = new S3Client([
            'region' => $configR['region'],
            'version' => $configR['version'],
            'credentials' => [
                'key' => $configR['key'],
                'secret' => $configR['secret']
            ]
        ]);
        $saveResult = $s3Client->getObject([
            'Bucket' => $configR['bucket'],
            'Key' => $key,
            'SaveAs' => $savepath
        ]);
        return $saveResult;
    }

    public static function getAwsS3PresignedGetObjectUrl($fileKey)
    {
        $configR = [];
        $config = Bootstrap::getContainer()->get('config');
        $configR['key'] = $config['resource/server/awss3accesskey'];
        $configR['secret'] = $config['resource/server/awss3accessecret'];
        $configR['bucket'] = $config['resource/server/awss3bucket'];
        $configR['path'] = '';
        $configR['filename'] = '';
        $configR['bucketPath'] = '';
        $configR['policy'] = $config['resource/server/awss3policy'];
        $configR['region'] = $config['resource/server/awss3region'];
        $configR['ACL'] = $config['resource/server/awss3acl'];
        $configR['version'] = $config['resource/server/awss3version'];
        $s3Client = new S3Client([
            'region' => $configR['region'],
            'version' => $configR['version'],
            'credentials' => [
                'key' => $configR['key'],
                'secret' => $configR['secret']
            ]
        ]);
        $cmd = $s3Client->getCommand('GetObject', [
            'Bucket' => $configR['bucket'],
            'Key' => $fileKey
        ]);
        $request = $s3Client->createPresignedRequest($cmd, '+288 minutes');
        $presignedUrl = (string) $request->getUri();
        return $presignedUrl;
    }

    public static function getSignedUrl($resourceKey)
    {
        $configR = [];
        $config = Bootstrap::getContainer()->get('config');
        $configR['key'] = $config['resource/server/awss3accesskey'];
        $configR['secret'] = $config['resource/server/awss3accessecret'];
        $configR['bucket'] = $config['resource/server/awss3bucket'];
        $configR['path'] = '';
        $configR['filename'] = '';
        $configR['bucketPath'] = '';
        $configR['policy'] = $config['resource/server/awss3policy'];
        $configR['region'] = $config['resource/server/awss3region'];
        $configR['ACL'] = $config['resource/server/awss3acl'];
        $configR['version'] = $config['resource/server/awss3version'];
        $expires = time() + 3600; // 5 minutes (5 * 60 seconds) from now.
        $privateKey = $config['resource/server/awss3urlsignfilepath'];
        $keyPairId = $config['resource/server/awss3urlsignpairkeyid'];
        $cloudFrontClient = new CloudFrontClient([
            'profile' => 'default',
            'version' => $configR['version'],
            'region' => $configR['region']
        ]);
        $result = $cloudFrontClient->getSignedUrl([
            'url' => $resourceKey,
            'expires' => $expires,
            'private_key' => $privateKey,
            'key_pair_id' => $keyPairId
        ]);
        $url = '';
        if ($result != '') {
            $url = $result;
        } else {
            $url = $this->getPubUrl('frontend/images/video-placeholder.jpeg');
        }
        return $url;
    }

    public static function awsMediaConvertClient($customer_id, $file_name, $userNameImage, $timer_title = '')
    {
        $configR = [];
        $config = Bootstrap::getContainer()->get('config');
        $configR['key'] = $config['resource/server/awss3accesskey'];
        $configR['secret'] = $config['resource/server/awss3accessecret'];
        $configR['bucket'] = $config['resource/server/awss3bucket'];
        $configR['path'] = '';
        $configR['filename'] = '';
        $configR['bucketPath'] = '';
        $configR['policy'] = $config['resource/server/awss3policy'];
        $configR['region'] = $config['resource/server/awss3region'];
        $configR['ACL'] = $config['resource/server/awss3acl'];
        $configR['version'] = $config['resource/server/awss3version'];
        $configR['queue'] = $config['resource/server/awss3videoconvertqueue'];
        $configR['role'] = $config['resource/server/awss3videoconvertrole'];
        $configR['endpoint'] = $config['resource/server/awss3videoconvertendpoint'];
        $jobSetting = [
            'OutputGroups' => [
                [
                    'Name' => 'File Group',
                    'OutputGroupSettings' => [
                        'Type' => 'FILE_GROUP_SETTINGS',
                        'FileGroupSettings' => [
                            'Destination' => 's3://' . $configR['bucket'] . '/pub/upload/forum/' . $customer_id . '/videos/'
                        ]
                    ],
                    'Outputs' => [
                        [
                            'VideoDescription' => [
                                'Width' => 720,
                                'ScalingBehavior' => 'DEFAULT',
                                'TimecodeInsertion' => 'DISABLED',
                                'AntiAlias' => 'ENABLED',
                                'Sharpness' => 50,
                                'CodecSettings' => [
                                    'Codec' => 'H_264',
                                    'H264Settings' => [
                                        'InterlaceMode' => 'PROGRESSIVE',
                                        'NumberReferenceFrames' => 3,
                                        'Syntax' => 'DEFAULT',
                                        'Softness' => 0,
                                        'GopClosedCadence' => 1,
                                        'GopSize' => 90,
                                        'Slices' => 1,
                                        'GopBReference' => 'DISABLED',
                                        'SlowPal' => 'DISABLED',
                                        'SpatialAdaptiveQuantization' => 'ENABLED',
                                        'TemporalAdaptiveQuantization' => 'ENABLED',
                                        'FlickerAdaptiveQuantization' => 'DISABLED',
                                        'EntropyEncoding' => 'CABAC',
                                        'MaxBitrate' => 5000000,
                                        'FramerateControl' => 'SPECIFIED',
                                        'RateControlMode' => 'QVBR',
                                        'CodecProfile' => 'MAIN',
                                        'Telecine' => 'NONE',
                                        'MinIInterval' => 0,
                                        'AdaptiveQuantization' => 'HIGH',
                                        'CodecLevel' => 'AUTO',
                                        'FieldEncoding' => 'PAFF',
                                        'SceneChangeDetect' => 'TRANSITION_DETECTION',
                                        'QualityTuningLevel' => 'SINGLE_PASS',
                                        'FramerateConversionAlgorithm' => 'DUPLICATE_DROP',
                                        'UnregisteredSeiTimecode' => 'DISABLED',
                                        'GopSizeUnits' => 'FRAMES',
                                        'ParControl' => 'SPECIFIED',
                                        'NumberBFramesBetweenReferenceFrames' => 2,
                                        'RepeatPps' => 'DISABLED',
                                        'FramerateNumerator' => 30,
                                        'FramerateDenominator' => 1,
                                        'ParNumerator' => 1,
                                        'ParDenominator' => 1
                                    ]
                                ],
                                'AfdSignaling' => 'NONE',
                                'DropFrameTimecode' => 'ENABLED',
                                'RespondToAfd' => 'NONE',
                                'ColorMetadata' => 'INSERT',
                                'VideoPreprocessors' => [
                                    'TimecodeBurnin' => [
                                        'Prefix' => $timer_title . ' ',
                                        'FontSize' => 16,
                                        'Position' => 'TOP_LEFT'
                                    ]
                                ]
                            ],
                            'AudioDescriptions' => [
                                [
                                    'AudioTypeControl' => 'FOLLOW_INPUT',
                                    'CodecSettings' => [
                                        'Codec' => 'AAC',
                                        'AacSettings' => [
                                            'AudioDescriptionBroadcasterMix' => 'NORMAL',
                                            'RateControlMode' => 'CBR',
                                            'CodecProfile' => 'LC',
                                            'CodingMode' => 'CODING_MODE_2_0',
                                            'RawFormat' => 'NONE',
                                            'SampleRate' => 48000,
                                            'Specification' => 'MPEG4',
                                            'Bitrate' => 64000
                                        ]
                                    ],
                                    'LanguageCodeControl' => 'FOLLOW_INPUT',
                                    'AudioSourceName' => 'Audio Selector 1'
                                ]
                            ],
                            'ContainerSettings' => [
                                'Container' => 'MP4',
                                'Mp4Settings' => [
                                    'CslgAtom' => 'INCLUDE',
                                    'FreeSpaceBox' => 'EXCLUDE',
                                    'MoovPlacement' => 'PROGRESSIVE_DOWNLOAD'
                                ]
                            ]
                        ]
                    ]
                ]
            ],
            'AdAvailOffset' => 0,
            'Inputs' => [
                [
                    'AudioSelectors' => [
                        'Audio Selector 1' => [
                            'Offset' => 0,
                            'DefaultSelection' => 'DEFAULT',
                            'ProgramSelection' => 1,
                            'SelectorType' => 'TRACK'
                        ]
                    ],
                    'VideoSelector' => [
                        'ColorSpace' => 'FOLLOW'
                    ],
                    'FilterEnable' => 'AUTO',
                    'PsiControl' => 'USE_PSI',
                    'FilterStrength' => 0,
                    'DeblockFilter' => 'DISABLED',
                    'DenoiseFilter' => 'DISABLED',
                    'TimecodeSource' => 'EMBEDDED',
                    'FileInput' => 's3://' . $configR['bucket'] . "/pub/upload/forum/$customer_id/original-videos/$file_name"
                ]
            ],
            'TimecodeConfig' => [
                'Source' => 'EMBEDDED'
            ]
        ];
        $credentials = new Credentials($configR['key'], $configR['secret']);
        $single_endpoint_url = $configR['endpoint'];
        $mediaConvertClient = new MediaConvertClient([
            'version' => $configR['version'],
            'region' => $configR['region'],
            'credentials' => $credentials,
            'endpoint' => $single_endpoint_url
            //'debug' => true
        ]);
        try {
            $result = $mediaConvertClient->createJob([
                'Settings' => $jobSetting, //JobSettings structure
                'Queue' => $configR['queue'],
                'Role' => $configR['role'],
                'UserMetadata' => [
                    'Customer' => 'Testing'
                ]
            ]);
        } catch (AwsException $e) {
            // output error message if fails
            echo $e->getMessage();
            echo "\n";
            echo 'output error message if fails';
            //exit('==');
        }
    }
}
