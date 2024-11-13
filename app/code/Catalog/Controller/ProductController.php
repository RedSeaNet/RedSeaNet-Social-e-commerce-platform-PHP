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
}
