<?php

namespace Redseanet\Catalog\Controller;

use Exception;
use Redseanet\Catalog\Model\Product;
use Redseanet\Lib\Controller\ActionController;
use Redseanet\Lib\Session\Segment;
use Redseanet\Customer\Model\Media;
use Redseanet\Log\Model\SocialMedia as Log;
//use Laminas\Crypt\PublicKey\Rsa;
//use Laminas\Crypt\PublicKey\RsaOptions;
//use Laminas\Crypt\PublicKey\Rsa\PrivateKey;
//use Laminas\Crypt\PublicKey\Rsa\PublicKey;
use WeChatPay\Builder;
use WeChatPay\Crypto\Rsa;
use WeChatPay\Util\PemUtil;
use Endroid\QrCode\Color\Color;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel\ErrorCorrectionLevelLow;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Label\Label;
use Endroid\QrCode\Logo\Logo;
use Endroid\QrCode\RoundBlockSizeMode\RoundBlockSizeModeMargin;
use Endroid\QrCode\Writer\PngWriter;

class ProductController extends ActionController
{
    use \Redseanet\Catalog\Traits\Breadcrumb;

    public function indexAction()
    {
        if ($this->getOption('product_id')) {
            $product = new Product();
            $product->load($this->getOption('product_id'));
            if ($product->getId()) {
                if ($this->getOption('is_json')) {
                    return $product->toArray();
                } else {
                    (new Segment('catalog'))->set('product_id', $product->getId());
                    (new Segment('core'))->set('store', $product->getStore()->offsetGet('code'));
                    $root = $this->getLayout('catalog_product');
                    $root->getChild('head')->setTitle($product->offsetGet('meta_title') ?: $product->offsetGet('name'))
                            ->setDescription($product->offsetGet('meta_description'))
                            ->setKeywords($product->offsetGet('meta_keywords'));
                    $root->getChild('product', true)->setProduct($product);
                    $breadcrumb = $root->getChild('breadcrumb', true);
                    $this->generateCrumbs($breadcrumb, $this->getOption('category_id'));
                    $breadcrumb->addCrumb([
                        'label' => $product->offsetGet('name')
                    ]);
                    return $root;
                }
            }
        }
        return $this->notFoundAction();
    }

    public function shareAction()
    {
        $data = $this->getRequest()->getQuery();
        $url = !empty($this->getRequest()->getHeader('HTTP_REFERER')['HTTP_REFERER']) ? $this->getRequest()->getHeader('HTTP_REFERER')['HTTP_REFERER'] : '';
        if (isset($data['media_id']) && !empty($url)) {
            $media = new Media();
            $media->load($data['media_id']);
            $segment = new Segment('customer');
            if ($segment->get('hasLoggedIn') && !empty($data['product_id'])) {
                try {
                    $model = new Log();
                    $model->setData($data + ['customer_id' => $segment->get('customer')['id']])->save();
                } catch (Exception $e) {
                    if (strpos($e->getMessage(), 'Duplicate') === false) {
                        $this->getContainer()->get('log')->logException($e);
                    }
                }
                $url .= '?referer=' . $segment->get('customer')['increment_id'];
            }
            return $this->redirect($media->getUrl(['{url}' => !empty($url) ? rawurlencode($url) : ''], $data['product_id'] ?? 0));
        }
        return $this->redirectReferer();
    }

    public function testAction()
    {
        //        $rsaOption=new RsaOptions();
        //        $rsaOption->setBinaryOutput(false)->setOpensslPadding(OPENSSL_PKCS1_PADDING);
        //        $rsa = new Rsa($rsaOption);
        //        $tmpRsaString=$rsa->encrypt('denny', new PrivateKey('-----BEGIN PRIVATE KEY-----
        //MIIEvAIBADANBgkqhkiG9w0BAQEFAASCBKYwggSiAgEAAoIBAQCqs2s3L8SucLRe
        //+YRdLb2Tspyms9KiH4XIgWA4mC6rvSKuNWuf5It3jGCe+F1iU5LVN4FRiJKcdcso
        //+TVl9JRm6YTXX2ZsO42f6WOtXjefISOTAF/upIOM7ehbr/RPPzzH5DkpmDfg/M02
        //6SA+mE3atH8aiT44/kFzLF/y9sz76ZkppvP0P8erIsewyQYO0uYy/Vt+TR9uqGdj
        //rdjf7VAhzvnsYi60YTEWTFb0rKeYJcv5fnCzNnYf1i4WeApIzC/MGUAIaLDfRz8M
        //NbDJmRwV47GZzrEu30vHM3RGSJRz0tI4OF4h+0uqgUsLpmO080t156JyFy5dr1Ah
        //U8R7S1FJAgMBAAECggEAIZhCB5BtIu+nb/GnbTctUj4aumw1jhaqG7Xqm/jHZqFI
        //jASTc9pk4FhOQ7sZldpg0XmlB8jbIdPE8gFp0vD5q+9VZ5Ws8KwfyVMDfQFH7Rd2
        //it3OA4d5JGlGC5VrfZUyfxyZa2g7xxm5wY7L+8W5vMs9IJnWFx1jACnBkG2mFgWn
        //B8Zvp4C7Ey8br07RT0+IsOplJnicqwZKVEXEx9BvG5NuyAqXdClzQdet8jIJw/7v
        //BxbgXc4mEZnZ5rG7p767Nw7dKePLB2KcwEBNcxx6XxN23OSjp8kqdeCbFt9114mz
        //nJjZRaa/2GRKNusdAUoavUWSlAaWLDPPUYgYXOBOAQKBgQDS+A3jyPSCZx+rB2/0
        //818JReMP2n+ooJ/h3ZieTeIj4pJUyel7PzLfI3THzNVcehVmFOu5ZcsTYWVsiJwN
        //RWyLtC9fQLYC6E1XXDuI5n/jDP5L8a5mMizUvNmHINWcTf3Jngrb2loMeLnxMuXP
        //jwQqDE7UN2S+kAMZIYCL5iK0YQKBgQDPIwAV90jWb5hFx0TPP7fSmKXJQsAahMZm
        //lt9XHsglxr+ikJnRS1LFy7kJgF0R6ira4HJVt0dYHD5ua3M5M+oKypWE/N8pHkhN
        //VPkM3Z81EvFEdrbjkgR44qx5mfI4g9dup6pQ41FE7X+wAL1TPLKX6uunfm9i2Mm3
        //8fuYEndF6QKBgEId4WSA5zgzD3avRwQSfbdPQsEGLjnv1QuZQipiqDus10VhXh31
        //CYFrAD9Zz4hC7o/mgJXC9CjnvjyMd7OlDUafOrV0d1Zj7VEyo6nc6zmCKfQtEwYO
        //NYSahuXVgXyR6LaWJDsQrGX6M/QGioVJAfoXj2Ds11LtmTr4B+xQPJIhAoGAGDt9
        //ABTc5ZFKnCcyypgmtjF7e68ecDvGRiwyVqvYOGsm8iq+g/iu50rtC8qDmNvvRYnq
        //dlKpuwoa16okYbXMFJUcpuE6bkIHrVxagoHO5VOg/CRzQu5LLaU/Dj7PUoNCCcT9
        //rNbbJBgwzvNXMsywDAvEo+SrvUGlX6qkh2bpozkCgYAA5iQjgM3BGXoSzcCmQyQH
        //t1LSRyo1PcQVUEPt1hAS8mje1EN92JlrArBQT/7uBTcT9Iwja9WN5F4prDswUbik
        //cghotNzZkX04++ipszMPY8EudsNcyxleOzHa5Btj+utNOiuMiZNW0wbnSH2NEkwQ
        //6+xMBgHPspcuOt37h93M1A==
        //-----END PRIVATE KEY-----', 'Reseanet'));
        //echo $tmpRsaString;
        //        $rsa = Rsa::factory([
        //    'public_key'    => '-----BEGIN PUBLIC KEY-----
        //MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAqrNrNy/ErnC0XvmEXS29
        //k7KcprPSoh+FyIFgOJguq70irjVrn+SLd4xgnvhdYlOS1TeBUYiSnHXLKPk1ZfSU
        //ZumE119mbDuNn+ljrV43nyEjkwBf7qSDjO3oW6/0Tz88x+Q5KZg34PzNNukgPphN
        //2rR/Gok+OP5Bcyxf8vbM++mZKabz9D/HqyLHsMkGDtLmMv1bfk0fbqhnY63Y3+1Q
        //Ic757GIutGExFkxW9KynmCXL+X5wszZ2H9YuFngKSMwvzBlACGiw30c/DDWwyZkc
        //FeOxmc6xLt9LxzN0RkiUc9LSODheIftLqoFLC6ZjtPNLdeeichcuXa9QIVPEe0tR
        //SQIDAQAB
        //-----END PUBLIC KEY-----',
        //    'private_key'   => '-----BEGIN PRIVATE KEY-----
        //MIIEvAIBADANBgkqhkiG9w0BAQEFAASCBKYwggSiAgEAAoIBAQCqs2s3L8SucLRe
        //+YRdLb2Tspyms9KiH4XIgWA4mC6rvSKuNWuf5It3jGCe+F1iU5LVN4FRiJKcdcso
        //+TVl9JRm6YTXX2ZsO42f6WOtXjefISOTAF/upIOM7ehbr/RPPzzH5DkpmDfg/M02
        //6SA+mE3atH8aiT44/kFzLF/y9sz76ZkppvP0P8erIsewyQYO0uYy/Vt+TR9uqGdj
        //rdjf7VAhzvnsYi60YTEWTFb0rKeYJcv5fnCzNnYf1i4WeApIzC/MGUAIaLDfRz8M
        //NbDJmRwV47GZzrEu30vHM3RGSJRz0tI4OF4h+0uqgUsLpmO080t156JyFy5dr1Ah
        //U8R7S1FJAgMBAAECggEAIZhCB5BtIu+nb/GnbTctUj4aumw1jhaqG7Xqm/jHZqFI
        //jASTc9pk4FhOQ7sZldpg0XmlB8jbIdPE8gFp0vD5q+9VZ5Ws8KwfyVMDfQFH7Rd2
        //it3OA4d5JGlGC5VrfZUyfxyZa2g7xxm5wY7L+8W5vMs9IJnWFx1jACnBkG2mFgWn
        //B8Zvp4C7Ey8br07RT0+IsOplJnicqwZKVEXEx9BvG5NuyAqXdClzQdet8jIJw/7v
        //BxbgXc4mEZnZ5rG7p767Nw7dKePLB2KcwEBNcxx6XxN23OSjp8kqdeCbFt9114mz
        //nJjZRaa/2GRKNusdAUoavUWSlAaWLDPPUYgYXOBOAQKBgQDS+A3jyPSCZx+rB2/0
        //818JReMP2n+ooJ/h3ZieTeIj4pJUyel7PzLfI3THzNVcehVmFOu5ZcsTYWVsiJwN
        //RWyLtC9fQLYC6E1XXDuI5n/jDP5L8a5mMizUvNmHINWcTf3Jngrb2loMeLnxMuXP
        //jwQqDE7UN2S+kAMZIYCL5iK0YQKBgQDPIwAV90jWb5hFx0TPP7fSmKXJQsAahMZm
        //lt9XHsglxr+ikJnRS1LFy7kJgF0R6ira4HJVt0dYHD5ua3M5M+oKypWE/N8pHkhN
        //VPkM3Z81EvFEdrbjkgR44qx5mfI4g9dup6pQ41FE7X+wAL1TPLKX6uunfm9i2Mm3
        //8fuYEndF6QKBgEId4WSA5zgzD3avRwQSfbdPQsEGLjnv1QuZQipiqDus10VhXh31
        //CYFrAD9Zz4hC7o/mgJXC9CjnvjyMd7OlDUafOrV0d1Zj7VEyo6nc6zmCKfQtEwYO
        //NYSahuXVgXyR6LaWJDsQrGX6M/QGioVJAfoXj2Ds11LtmTr4B+xQPJIhAoGAGDt9
        //ABTc5ZFKnCcyypgmtjF7e68ecDvGRiwyVqvYOGsm8iq+g/iu50rtC8qDmNvvRYnq
        //dlKpuwoa16okYbXMFJUcpuE6bkIHrVxagoHO5VOg/CRzQu5LLaU/Dj7PUoNCCcT9
        //rNbbJBgwzvNXMsywDAvEo+SrvUGlX6qkh2bpozkCgYAA5iQjgM3BGXoSzcCmQyQH
        //t1LSRyo1PcQVUEPt1hAS8mje1EN92JlrArBQT/7uBTcT9Iwja9WN5F4prDswUbik
        //cghotNzZkX04++ipszMPY8EudsNcyxleOzHa5Btj+utNOiuMiZNW0wbnSH2NEkwQ
        //6+xMBgHPspcuOt37h93M1A==
        //-----END PRIVATE KEY-----',
        //    'pass_phrase'   => 'Reseanet',
        //    'binary_output' => false,
        //]);
        //        $tmpRsaString = $rsa->encrypt('redseanet');
        //        echo $tmpRsaString;
        //
        //        echo '<br /><br /><br /><br />';
        //
        //
        //        $decryptS = $rsa->decrypt($tmpRsaString);
        //        echo $decryptS;
        //        $rsaOption=new RsaOptions();
        //        $rsaOption->setBinaryOutput(false)->setOpensslPadding(OPENSSL_PKCS1_PADDING);
        //        $rsaOption->setPublicKey(new PublicKey('-----BEGIN PUBLIC KEY-----
        //MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAqrNrNy/ErnC0XvmEXS29
        //k7KcprPSoh+FyIFgOJguq70irjVrn+SLd4xgnvhdYlOS1TeBUYiSnHXLKPk1ZfSU
        //ZumE119mbDuNn+ljrV43nyEjkwBf7qSDjO3oW6/0Tz88x+Q5KZg34PzNNukgPphN
        //2rR/Gok+OP5Bcyxf8vbM++mZKabz9D/HqyLHsMkGDtLmMv1bfk0fbqhnY63Y3+1Q
        //Ic757GIutGExFkxW9KynmCXL+X5wszZ2H9YuFngKSMwvzBlACGiw30c/DDWwyZkc
        //FeOxmc6xLt9LxzN0RkiUc9LSODheIftLqoFLC6ZjtPNLdeeichcuXa9QIVPEe0tR
        //SQIDAQAB
        //-----END PUBLIC KEY-----'));
        //
        //        $rsa = new Rsa($rsaOption);
        //        echo $rsa->decrypt($tmpRsaString, new PrivateKey('-----BEGIN PRIVATE KEY-----
        //MIIEvAIBADANBgkqhkiG9w0BAQEFAASCBKYwggSiAgEAAoIBAQCqs2s3L8SucLRe
        //+YRdLb2Tspyms9KiH4XIgWA4mC6rvSKuNWuf5It3jGCe+F1iU5LVN4FRiJKcdcso
        //+TVl9JRm6YTXX2ZsO42f6WOtXjefISOTAF/upIOM7ehbr/RPPzzH5DkpmDfg/M02
        //6SA+mE3atH8aiT44/kFzLF/y9sz76ZkppvP0P8erIsewyQYO0uYy/Vt+TR9uqGdj
        //rdjf7VAhzvnsYi60YTEWTFb0rKeYJcv5fnCzNnYf1i4WeApIzC/MGUAIaLDfRz8M
        //NbDJmRwV47GZzrEu30vHM3RGSJRz0tI4OF4h+0uqgUsLpmO080t156JyFy5dr1Ah
        //U8R7S1FJAgMBAAECggEAIZhCB5BtIu+nb/GnbTctUj4aumw1jhaqG7Xqm/jHZqFI
        //jASTc9pk4FhOQ7sZldpg0XmlB8jbIdPE8gFp0vD5q+9VZ5Ws8KwfyVMDfQFH7Rd2
        //it3OA4d5JGlGC5VrfZUyfxyZa2g7xxm5wY7L+8W5vMs9IJnWFx1jACnBkG2mFgWn
        //B8Zvp4C7Ey8br07RT0+IsOplJnicqwZKVEXEx9BvG5NuyAqXdClzQdet8jIJw/7v
        //BxbgXc4mEZnZ5rG7p767Nw7dKePLB2KcwEBNcxx6XxN23OSjp8kqdeCbFt9114mz
        //nJjZRaa/2GRKNusdAUoavUWSlAaWLDPPUYgYXOBOAQKBgQDS+A3jyPSCZx+rB2/0
        //818JReMP2n+ooJ/h3ZieTeIj4pJUyel7PzLfI3THzNVcehVmFOu5ZcsTYWVsiJwN
        //RWyLtC9fQLYC6E1XXDuI5n/jDP5L8a5mMizUvNmHINWcTf3Jngrb2loMeLnxMuXP
        //jwQqDE7UN2S+kAMZIYCL5iK0YQKBgQDPIwAV90jWb5hFx0TPP7fSmKXJQsAahMZm
        //lt9XHsglxr+ikJnRS1LFy7kJgF0R6ira4HJVt0dYHD5ua3M5M+oKypWE/N8pHkhN
        //VPkM3Z81EvFEdrbjkgR44qx5mfI4g9dup6pQ41FE7X+wAL1TPLKX6uunfm9i2Mm3
        //8fuYEndF6QKBgEId4WSA5zgzD3avRwQSfbdPQsEGLjnv1QuZQipiqDus10VhXh31
        //CYFrAD9Zz4hC7o/mgJXC9CjnvjyMd7OlDUafOrV0d1Zj7VEyo6nc6zmCKfQtEwYO
        //NYSahuXVgXyR6LaWJDsQrGX6M/QGioVJAfoXj2Ds11LtmTr4B+xQPJIhAoGAGDt9
        //ABTc5ZFKnCcyypgmtjF7e68ecDvGRiwyVqvYOGsm8iq+g/iu50rtC8qDmNvvRYnq
        //dlKpuwoa16okYbXMFJUcpuE6bkIHrVxagoHO5VOg/CRzQu5LLaU/Dj7PUoNCCcT9
        //rNbbJBgwzvNXMsywDAvEo+SrvUGlX6qkh2bpozkCgYAA5iQjgM3BGXoSzcCmQyQH
        //t1LSRyo1PcQVUEPt1hAS8mje1EN92JlrArBQT/7uBTcT9Iwja9WN5F4prDswUbik
        //cghotNzZkX04++ipszMPY8EudsNcyxleOzHa5Btj+utNOiuMiZNW0wbnSH2NEkwQ
        //6+xMBgHPspcuOt37h93M1A==
        //-----END PRIVATE KEY-----', 'Reseanet'), Rsa::MODE_BASE64);
        $config = $this->getContainer()->get('config');

        //print_r($config["rabbitmq"]);
        //$testObj = new \Redseanet\Balance\Mq\Recalc();
        // echo '-------------rabbitmq-----------';
        //$this->getContainer()->get('log')->logException(new \Exception(json_encode($params)));
        //$result = $this->request($config['payment/wechat_pay/gateway'] . 'pay/unifiedorder', $params);
        // 商户号
        //        $merchantId = '1679608723';
        //
        //// 从本地文件中加载「商户API私钥」，「商户API私钥」会用来生成请求的签名
        //        $merchantPrivateKeyFilePath = 'file://' . BP . 'var/cert/wechatpay/apiclient_key.pem';
        //        $this->getContainer()->get('log')->logException(new \Exception($merchantPrivateKeyFilePath));
        //        $merchantPrivateKeyInstance = Rsa::from($merchantPrivateKeyFilePath, Rsa::KEY_TYPE_PRIVATE);
        //
        //// 「商户API证书」的「证书序列号」
        //        $merchantCertificateSerial = '360BE9AF9C05DBBF56BC2F9743EEFD1B23130F94';
        //
        //// 从本地文件中加载「微信支付平台证书」，用来验证微信支付应答的签名
        //        $platformCertificateFilePath = 'file://' . BP . 'var/cert/wechatpay/wechatpay_5E262DC9BBF51F3EDB0BC1566C4B4689C8D018FB.pem';
        //        $this->getContainer()->get('log')->logException(new \Exception($platformCertificateFilePath));
        //        $platformPublicKeyInstance = Rsa::from($platformCertificateFilePath, Rsa::KEY_TYPE_PUBLIC);
        //
        //// 从「微信支付平台证书」中获取「证书序列号」
        //        $platformCertificateSerial = PemUtil::parseCertificateSerialNo($platformCertificateFilePath);
        //
        //// 构造一个 APIv3 客户端实例
        //        $instance = Builder::factory([
        //                    'mchid' => $merchantId,
        //                    'serial' => $merchantCertificateSerial,
        //                    'privateKey' => $merchantPrivateKeyInstance,
        //                    'certs' => [
        //                        $platformCertificateSerial => $platformPublicKeyInstance,
        //                    ],
        //        ]);
        //        $result = [];
        //        try {
        //            $resp = $instance->chain('v3/pay/transactions/native')
        //                    ->post(['json' => [
        //                    'mchid' => $merchantId,
        //                    'out_trade_no' => 'native12177525012014070332312',
        //                    'appid' => 'wxa01b978931bbfbf0',
        //                    'description' => 'Image形象店-深圳腾大-QQ公仔',
        //                    'notify_url' => 'https://store.redseanet.com/payment/notify/',
        //                    'amount' => [
        //                        'total' => 1,
        //                        'currency' => 'CNY'
        //                    ],
        //            ]]);
        ////            $resp = $instance->chain('/v3/pay/transactions/jsapi')
        ////                    ->post(['json' => [
        ////                    'mchid' => $merchantId,
        ////                    'out_trade_no' => 'native12177525012014070332332',
        ////                    'appid' => 'wxa01b978931bbfbf0',
        ////                    'description' => 'Image形象店-深圳腾大-QQ公仔',
        ////                    'notify_url' => 'https://store.redseanet.com/payment/notify/',
        ////                    'amount' => [
        ////                        'total' => 1,
        ////                        'currency' => 'CNY'
        ////                    ],
        ////                ], 'debug' => true]);
        //            $this->getContainer()->get('log')->logException(new \Exception(json_encode($resp)));
        //            echo $resp->getStatusCode();
        //            echo $resp->getBody();
        //            $result['status_code'] = $resp->getStatusCode();
        //            $body = $resp->getBody();
        //            $result['body'] = json_decode((string) $body,true);
        //        } catch (\Exception $e) {
        //            $this->getContainer()->get('log')->logException($e);
        //            // 进行错误处理
        //            echo $e->getMessage();
        //            if ($e instanceof \GuzzleHttp\Exception\RequestException && $e->hasResponse()) {
        //                $r = $e->getResponse();
        //                echo $r->getStatusCode() . ' ' . $r->getReasonPhrase();
        //                echo $r->getBody();
        //                $result['status_code'] = $r->getStatusCode();
        //                $result['body'] = $r->getBody();
        //            }
        //            echo $e->getTraceAsString();
        //            $result['TraceAsString'] = $e->getTraceAsString();
        //            $result['message'] = $e->getMessage();
        //        }
        //        print_r($result);
        //
        //
        //        $writer = new PngWriter();
        //        $qrCode = QrCode::create($result['body']['code_url'])
        //                ->setEncoding(new Encoding('UTF-8'))
        //                ->setErrorCorrectionLevel(new ErrorCorrectionLevelLow())
        //                ->setSize(200)
        //                ->setMargin(2)
        //                ->setRoundBlockSizeMode(new RoundBlockSizeModeMargin())
        //                ->setForegroundColor(new Color(0, 0, 0))
        //                ->setBackgroundColor(new Color(255, 255, 255));
        //
        //        $logo = Logo::create(BP . 'pub/theme/blue/frontend/images/logo.png')
        //                ->setResizeToWidth(40);
        //        $label = Label::create("")
        //                ->setTextColor(new Color(255, 0, 0));
        //        $resultQrCode = $writer->write($qrCode, $logo, $label)->getDataUri();
        //
        //        echo ' <img src="'.$resultQrCode.'" alt="" />';
        //        $this->getContainer()->get('log')->logException(new \Exception(json_encode($result)));
        echo '-------';
        exit;
    }
}
