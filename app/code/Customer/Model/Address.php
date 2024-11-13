<?php

namespace Redseanet\Customer\Model;

use Redseanet\I18n\Model\Locate;
use Redseanet\Lib\Bootstrap;
use Redseanet\Customer\Model\Collection\Address as Collection;
use Redseanet\Lib\Model\Collection\Eav\Attribute;
use Redseanet\Lib\Model\Eav\Entity;

class Address extends Entity
{
    public const ENTITY_TYPE = 'address';

    protected $string = [];

    protected function construct()
    {
        $this->init('id', ['id', 'type_id', 'attribute_set_id', 'store_id', 'customer_id', 'is_default', 'status']);
    }

    public function __toString()
    {
        return $this->display();
    }

    protected function afterSave()
    {
        if (isset($this->storage['is_default']) && $this->storage['is_default']) {
            $collection = new Collection();
            $collection->where(['is_default' => '1', 'customer_id' => $this->storage['customer_id']])
            ->where->notEqualTo('id', $this->getId());
            foreach ($collection as $item) {
                $address = new static($this->languageId, $item);
                $address->setData('is_default', '0')->save();
            }
        }
        parent::afterSave();
    }

    public function display($inOneLine = true)
    {
        if (!isset($this->string[(int) $inOneLine])) {
            $format = $this->getContainer()->get('config')[$inOneLine ? 'customer/address/one_line' : 'customer/address/multi_line'];
            $replace = [];
            $language = Bootstrap::getLanguage();
            $locate = new Locate();
            preg_match_all('#\{\{[^\}]+\}\}#', $format, $matches);
            foreach ($matches[0] as $match) {
                $src = trim($match, '{}');
                if (strpos($match, 'label:') === false) {
                    if (isset($this->storage[$src])) {
                        $target = $this->storage[$src];
                        if (is_numeric($this->storage[$src]) && in_array($src, ['country', 'region', 'city', 'county'])) {
                            $pid = $src === 'region' ? $this->storage['country'] :
                                    ($src === 'city' ? $this->storage['region'] :
                                    ($src === 'county' ? $this->storage['city'] : ''));
                            if (is_numeric($pid)) {
                                $label = $locate->getLabel($src, $this->storage[$src], $pid);
                                if (count($label)) {
                                    $target = $label[$this->storage[$src]]->getName($language->offsetGet('code'));
                                }
                            } elseif ($src === 'country') {
                                $label = $locate->getLabel('country', $this->storage['country']);
                                if (count($label)) {
                                    $target = $label[$this->storage['country']]->getName($language->offsetGet('code'));
                                }
                            }
                        }
                    } else {
                        $target = '';
                    }
                } else {
                    $attribute = new Attribute();
                    $attribute->withLabel($language->getId())
                            ->join('eav_entity_type', 'eav_entity_type.id=eav_attribute.type_id', [], 'left')
                            ->where(['eav_attribute.code' => substr($src, 6), 'eav_entity_type.code' => self::ENTITY_TYPE]);
                    $target = $attribute->count() ? $attribute[0]['label'] : '';
                }
                $replace[$match] = $target;
            }
            $this->string[(int) $inOneLine] = str_replace(array_keys($replace), array_values($replace), $format);
        }
        return $this->string[(int) $inOneLine];
    }
}
