<?php

namespace Redseanet\Catalog\Traits;

use DateTime;
use DateTimeZone;
use Exception;
use Redseanet\Cms\Model\Page;
use Redseanet\Lib\Model\Collection\Language;

trait Sitemap
{
    use \Redseanet\Lib\Traits\DB;

    public function generate()
    {
        $collection = new Language();
        $collection->where(['status' => 1]);
        $indexer = $this->getContainer()->get('indexer');
        $config = $this->getContainer()->get('config');
        $filename = BP . trim($config['catalog/sitemap/path'], '/') . DS . $config['catalog/sitemap/filename'];
        $baseurl = $this->getBaseUrl();
        $timezone = new DateTimeZone($config['global/locale/timezone']);
        $count = $collection->count();
        try {
            foreach ($collection as $language) {
                $xml = '<?xml version="1.0" encoding="UTF-8"?><urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';
                $offset = 0;
                do {
                    $result = $indexer->select('catalog_url', $language->getId(), [], ['limit' => 50, 'offset' => $offset]);
                    foreach ($result as $url) {
                        if ($url['product_id']) {
                            $model = $this->getTableGateway('product_entity')->select(['id' => $url['product_id']])->toArray()[0];
                        } else {
                            $model = $this->getTableGateway('category_entity')->select(['id' => $url['category_id']])->toArray()[0];
                        }
                        $xml .= '<url><loc><![CDATA[' . $baseurl . $url['path'] . '.html]]></loc><changefreq>daily</changefreq><priority>' . ($url['product_id'] ? '1.0' : '0.5') . '</priority><lastmod><![CDATA[' . (new DateTime($model['updated_at'] ?: $model['created_at'], $timezone))->format(DateTime::W3C) . ']]></lastmod></url>';
                    }
                    $offset += 50;
                } while ($result);
                $offset = 0;
                do {
                    $result = $indexer->select('cms_url', $language->getId(), [], ['limit' => 50, 'offset' => $offset]);
                    foreach ($result as $url) {
                        if ($url['page_id']) {
                            $model = new Page();
                            $model->load($url['page_id']);
                        }
                        $xml .= '<url><loc><![CDATA[' . $baseurl . $url['path'] . '.html]]></loc><changefreq>daily</changefreq><priority>0.2</priority><lastmod><![CDATA[' . (new DateTime($model['updated_at'] ?: $model['created_at'], $timezone))->format(DateTime::W3C) . ']]></lastmod></url>';
                    }
                    $offset += 50;
                } while ($result);
                $fp = fopen($filename . ($count === 1 ? '' : ('-' . $language->getId())) . '.xml', 'w');
                fwrite($fp, $xml . '</urlset>');
                fclose($fp);
            }
            return PHP_SAPI === 'cli' ? 'The sitemap has been generated.' : ['error' => 0, 'message' => [[
                'message' => $this->translate('The sitemap has been generated.'),
                'level' => 'success'
            ]]];
        } catch (Exception $e) {
            $this->getContainer()->get('log')->logException($e);
            return PHP_SAPI === 'cli' ? $e->getMessage() : ['error' => 1, 'message' => [[
                'message' => $this->translate('An error detected. Please try again later.'),
                'level' => 'danger'
            ]]];
        }
    }
}
