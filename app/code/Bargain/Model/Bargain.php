<?php

namespace Redseanet\Bargain\Model;

use Redseanet\Customer\Model\Collection\Customer;
use Redseanet\Lib\Model\AbstractModel;
use Redseanet\Lib\Model\Increment;
use Redseanet\Lib\Session\Segment;
use Laminas\Db\Sql\Expression;
use Laminas\Db\Sql\Select;
use Redseanet\Resource\Model\Collection\Resource;
use Redseanet\Catalog\Model\Product\Option;

class Bargain extends AbstractModel
{
    protected function construct()
    {
        $this->init('bargain', 'id', ['id', 'product_id', 'thumbnail', 'stock', 'sold', 'images', 'start_time',
            'stop_time', 'price', 'min_price', 'num', 'bargain_max_price', 'bargain_min_price', 'bargain_num',
            'status', 'original_price', 'is_recommend', 'viewed', 'shared', 'sort_order', 'options', 'people_num', 'sku', 'store_id', 'weight', 'warehouse_id', 'free_shipping']);
    }

    protected function beforeSave()
    {
        $this->beginTransaction();
        parent::beforeSave();
    }

    protected function afterSave()
    {
        parent::afterSave();
        if (isset($this->storage['name'])) {
            $tableGateway = $this->getTableGateway('bargain_language');
            foreach ((array) $this->storage['name'] as $languageId => $name) {
                $this->upsert(['name' => $name], ['bargain_id' => $this->getId(), 'language_id' => $languageId], $tableGateway);
            }
            foreach ((array) $this->storage['content'] as $languageId => $content) {
                $this->upsert(['content' => $content], ['bargain_id' => $this->getId(), 'language_id' => $languageId], $tableGateway);
            }
            foreach ((array) $this->storage['description'] as $languageId => $description) {
                $this->upsert(['description' => $description], ['bargain_id' => $this->getId(), 'language_id' => $languageId], $tableGateway);
            }
        }
        $this->commit();
    }

    protected function beforeLoad($select)
    {
        $select->join('bargain_language', 'bargain_language.bargain_id=bargain.id', ['name', 'content', 'description'], 'left');
        $select->join('core_language', 'bargain_language.language_id=core_language.id', ['language_id' => 'id', 'language' => 'name'], 'left');
        parent::beforeLoad($select);
    }

    protected function afterLoad(&$result)
    {
        if (isset($result[0])) {
            $language = [];
            $name = [];
            $content = [];
            $description = [];
            foreach ($result as $item) {
                $language[$item['language_id']] = $item['language'];
                $name[$item['language_id']] = $item['name'];
                $content[$item['language_id']] = $item['content'];
                $description[$item['language_id']] = $item['description'];
            }
            $result[0]['language'] = $language;
            $result[0]['language_id'] = array_keys($language);
            $result[0]['name'] = $name;
            $result[0]['content'] = $content;
            $result[0]['description'] = $description;
            $result[0]['images'] = json_decode($result[0]['images'], true);
        }
        parent::afterLoad($result);
    }

    public function getImages()
    {
        if (!empty($this->storage['images'])) {
            $imageIds = is_array($this->storage['images']) ? $this->storage['images'] : json_decode($this->storage['images'], true);
            if (count($imageIds) > 0) {
                $collection = new Resource();
                $collection->where(['1' => 1])->where->in('id', $imageIds);
                return $collection->load(true, true);
            } else {
                return [];
            }
        }
        return [];
    }

    public function getThumbnail()
    {
        if (!empty($this->storage['thumbnail'])) {
            $collection = new Resource();
            $collection->where(['id' => $this->storage['thumbnail']]);
            return $collection->load(true, true);
        }
        return [];
    }

    public function randomFloat($price, $people, $type = false)
    {
        //按照人数计算保留金额
        $retainPrice = bcmul((string) $people, '0.01', 2);
        //实际剩余金额
        $price = bcsub((string) $price, $retainPrice, 2);
        //计算比例
        if ($type) {
            $percent = '0.5';
        } else {
            $percent = bcdiv((string) mt_rand(10, 30), '100', 2);
        }
        //实际砍掉金额
        $cutPrice = bcmul($price, $percent, 2);
        //如果计算出来为0，默认砍掉0.01
        return $cutPrice != '0.00' ? $cutPrice : '0.01';
    }

    public function getOptionsLabel()
    {
        if (!empty($this->storage['options'])) {
            $labelString = [];
            ;
            $options = json_decode($this->storage['options'], true);
            foreach ($options as $option => $value) {
                $optionObject = new Option();
                $optionObject->load($option);
                if (!empty($optionObject->getId())) {
                    $valueLabel = $optionObject->getValue($value);
                    $labelString[] = $valueLabel;
                }
            }

            return implode(',', $labelString);
        }
        return '';
    }
}
