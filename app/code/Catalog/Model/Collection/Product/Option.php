<?php

namespace Redseanet\Catalog\Model\Collection\Product;

use Redseanet\Lib\Bootstrap;
use Redseanet\Lib\Model\AbstractCollection;
use Laminas\Db\Sql\Select;
use Laminas\Db\Sql\Expression;

class Option extends AbstractCollection
{
    protected $languageId;

    protected function construct()
    {
        $this->init('product_option');
    }

    public function withLabel($languageId = null)
    {
        if (is_null($languageId)) {
            $this->languageId = Bootstrap::getLanguage()->getId();
        } elseif (is_object($languageId)) {
            $this->languageId = $languageId['id'];
        } else {
            $this->languageId = $languageId;
        }
        //        $this->select->join('product_option_title', 'product_option_title.option_id=product_option.id', ['title'], 'left')
        //                ->where(['product_option_title.language_id' => $this->languageId]);
        $titleSubSelect = new Select();
        $titleSubSelect->from('product_option_title');
        $titleSubSelect->columns(['title']);
        $titleSubSelect->where('product_option_title.language_id=' . $this->languageId);
        $titleSubSelect->where('product_option_title.option_id=product_option.id');
        $titleSubSelect->limit(1);
        $defaltTitleSubSelect = new Select();
        $defaltTitleSubSelect->from('product_option_title');
        $defaltTitleSubSelect->columns(['title']);
        $defaltTitleSubSelect->where('product_option_title.option_id=product_option.id');
        $defaltTitleSubSelect->limit(1);
        $this->select->columns(['id', 'product_id', 'input', 'is_required', 'sku', 'price', 'is_fixed', 'sort_order', 'eav_attribute_id', 'title' => $titleSubSelect, 'default_title' => $defaltTitleSubSelect]);
        return $this;
    }

    public function afterLoad(&$result)
    {
        $tableGateway = $this->getTableGateway('product_option_value');
        foreach ($result as &$item) {
            if (in_array($item['input'], ['select', 'radio', 'checkbox', 'multiselect'])) {
                $select = $tableGateway->getSql()->select();
                $select->where(['option_id' => $item['id']]);
                if ($this->languageId) {
                    //                    $select->join('product_option_value_title', 'product_option_value.id=product_option_value_title.value_id', ['title'], 'left')
                    //                            ->where(['product_option_value_title.language_id' => $this->languageId]);
                    $titleSubSelect = new Select();
                    $titleSubSelect->from('product_option_value_title');
                    $titleSubSelect->columns(['title']);
                    $titleSubSelect->where('`product_option_value_title`.`language_id`=' . intval($this->languageId));
                    $titleSubSelect->where('`product_option_value_title`.`value_id`=`product_option_value`.`id`');
                    $titleSubSelect->limit(1);

                    $defaltTitleSubSelect = new Select();
                    $defaltTitleSubSelect->from('product_option_value_title');
                    $defaltTitleSubSelect->columns(['title']);
                    $defaltTitleSubSelect->where('`product_option_value_title`.`value_id`=`product_option_value`.`id`');
                    $defaltTitleSubSelect->limit(1);
                    $select->columns(['id', 'option_id', 'sku', 'price', 'is_fixed', 'sort_order', 'eav_attribute_option_id', 'title' => $titleSubSelect, 'default_title' => $defaltTitleSubSelect]);
                    //echo $select->getSqlString(Bootstrap::getContainer()->get("dbAdapter")->getPlatform());
                }
                $item['value'] = $tableGateway->selectWith($select)->toArray();
            } else {
                $item['value'] = [];
            }
        }
        parent::afterLoad($result);
    }
}
