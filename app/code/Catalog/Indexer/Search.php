<?php

namespace Redseanet\Catalog\Indexer;

use Redseanet\Catalog\Model\Collection\Product as Collection;
use Redseanet\Lib\Indexer\Handler\AbstractHandler;
use Redseanet\Lib\Indexer\Handler\Database;
use Redseanet\Lib\Indexer\Provider;
use Redseanet\Lib\Model\Collection\Eav\Attribute;
use Redseanet\Lib\Model\Collection\Language;
use Redseanet\Search\Model\Factory;

class Search implements Provider
{
    use \Redseanet\Lib\Traits\Container;

    protected $engine;

    public const DELIMITER = ' ';

    public function provideStructure(AbstractHandler $handler)
    {
        if ($handler instanceof Database) {
            $this->engine = (new Factory())->getSearchEngineHandler();
            $this->engine->createIndex('catalog_search');
        } else {
            $handler->buildStructure([]);
            $this->engine = (new Factory())->getSearchEngineHandler('MongoDB');
            $this->engine->createIndex('catalog_search');
        }
        return true;
    }

    public function provideData(AbstractHandler $handler)
    {
        $attributes = new Attribute();
        $attributes->join('eav_entity_type', 'eav_entity_type.id=eav_attribute.type_id', [], 'left')
                ->where(['eav_entity_type.code' => Collection::ENTITY_TYPE, 'searchable' => 1]);
        $languages = new Language();
        $languages->columns(['id']);
        foreach ($languages as $language) {
            $data = [$language['id'] => []];
            $collection = new Collection($language['id']);
            $collection->where(['status' => 1])->limit(50);
            for ($i = 0; ; $i++) {
                $collection->reset('offset')->offset(50 * $i);
                $collection->load(false);
                if (!$collection->count()) {
                    break;
                }
                foreach ($collection as $product) {
                    $text = static::DELIMITER;
                    foreach ($attributes as $attribute) {
                        $text .= $this->getOption($product, $attribute['code'], in_array($attribute['input'], ['select', 'radio', 'checkbox', 'multiselect']) ? $attribute : false);
                    }
                    $data[$language['id']][] = [
                        'id' => $product['id'],
                        'store_id' => $product['store_id'],
                        'data' => preg_replace('/' . (static::DELIMITER === ' ' ? ' ' : '\\' . static::DELIMITER) . '{2,}/', static::DELIMITER, $text)
                    ];
                }
            }
            $this->engine->update('catalog_search', $data);
        }
        return true;
    }

    private function getOption($product, $code, $attribute = false)
    {
        $text = '';
        if (is_array($product[$code])) {
            foreach ($product[$code] as $value) {
                $text .= ($attribute ? $attribute->getOption($value) : $value) . static::DELIMITER;
            }
        } else {
            $text .= ($attribute ? $attribute->getOption($product[$code]) : $product[$code]) . static::DELIMITER;
        }
        return $text;
    }
}
