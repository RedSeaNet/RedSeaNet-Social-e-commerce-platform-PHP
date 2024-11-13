<?php

namespace Redseanet\Catalog\Model;

use Redseanet\Catalog\Model\Collection\Category as Categories;
use Redseanet\Catalog\Model\Collection\Product as Collection;
use Redseanet\Catalog\Model\Collection\Product\Option as OptionCollection;
use Redseanet\Catalog\Model\Collection\Warehouse as WarehouseCollection;
use Redseanet\Catalog\Model\Product\Option as OptionModel;
use Redseanet\Catalog\Model\Warehouse;
use Redseanet\Lib\Model\Collection\Eav\Attribute as AttributeCollection;
use Redseanet\Lib\Model\Eav\Entity;
use Redseanet\Lib\Model\Eav\Attribute;
use Redseanet\I18n\Model\Currency;
use Redseanet\Lib\Model\Store;
use Redseanet\Resource\Model\Resource;
use Laminas\Db\Sql\Predicate\In;
use Redseanet\Lib\Bootstrap;
use Redseanet\Lib\Model\Collection\Language;

class Product extends Entity
{
    use \Redseanet\Lib\Traits\Url;

    public const ENTITY_TYPE = 'product';

    protected function construct()
    {
        $this->init('id', ['id', 'type_id', 'attribute_set_id', 'store_id', 'product_type_id', 'status']);
    }

    public function isVirtual()
    {
        return isset($this->storage['product_type_id']) && $this->storage['product_type_id'] == 2;
    }

    public function isNew()
    {
        $time = time();
        return !empty($this->storage['new_start']) &&
                strtotime($this->storage['new_start']) <= $time &&
                (empty($this->storage['new_end']) || strtotime($this->storage['new_end']) >= $time);
    }

    public function getOptions($constraint = [], $language = null)
    {
        if ($this->getId()) {
            $options = new OptionCollection();
            $options->withLabel($language)
                    ->where(['product_id' => $this->getId()] + $constraint)
                    ->order('sort_order ASC');
            //echo $options->getSqlString(Bootstrap::getContainer()->get("dbAdapter")->getPlatform()); exit('-----');
            return $options;
        }
        return [];
    }

    public function getOption($id, $value = null, $language = null)
    {
        if ($this->getId()) {
            $options = $this->getOptions(['id' => $id], $language);
            if ($options->count()) {
                $option = $options[0];
                if (!is_null($value)) {
                    return $option->getValue($value, true, $language);
                }
                return $option;
            }
        }
        return null;
    }

    public function getStore()
    {
        if ($this->getId()) {
            $store = new Store();
            $store->load($this->storage['store_id']);
            return $store;
        }
        return null;
    }

    public function getCategories()
    {
        if ($this->getId()) {
            $category = new Categories($this->languageId);
            $tableGateway = $this->getTableGateway('product_in_category');
            $result = $tableGateway->select(['product_id' => $this->getId()])->toArray();
            $valueSet = [];
            array_walk($result, function ($item) use (&$valueSet) {
                $valueSet[] = $item['category_id'];
            });
            if (count($valueSet)) {
                $category->where(new In('id', $valueSet));
            } else {
                return [];
            }
            return $category;
        }
        return [];
    }

    public function getAttributes()
    {
        $result = [];
        if ($this->getId()) {
            $attributes = new AttributeCollection();
            $attributes->withLabel()->withSet()->columns(['id', 'code', 'input'])->where([
                'eav_attribute_set.id' => $this->storage['attribute_set_id']
            ])->where->notIn('code', [
                'images', 'default_image', 'thumbnail', 'uri_key',
                'description', 'short_description', 'taxable'
            ])->notLike('code', '%price%')->notLike('code', 'meta%')->notEqualTo('type', 'datetime');
            $getValue = function ($attribute, $value) {
                return in_array($attribute['input'], ['select', 'radio', 'checked', 'multiselect']) ? $attribute->getOption($value) : $value;
            };
            foreach ($attributes as $attribute) {
                if (is_array($this->storage[$attribute->offsetGet('code')])) {
                    $result[$attribute->offsetGet('label')] = '';
                    foreach ($this->storage[$attribute->offsetGet('code')] as $value) {
                        $result[$attribute->offsetGet('label')] .= $getValue($attribute, $value) . ',';
                    }
                    $result[$attribute->offsetGet('label')] = trim($result[$attribute->offsetGet('label')], ',');
                } else {
                    $result[$attribute->offsetGet('label')] = $getValue($attribute, $this->storage[$attribute->offsetGet('code')]);
                }
            }
        }
        return $result;
    }

    public function getAttribute($idOrCode, $option = null)
    {
        if ($this->getId()) {
            $attribute = new Attribute();
            $attribute->load($idOrCode, is_numeric($idOrCode) ? 'id' : 'code');
            if ($attribute->getId()) {
                if (!is_null($option)) {
                    return $attribute->getOption($option, $this->languageId);
                }
                return $attribute;
            }
        }
        return null;
    }

    public function getInventory($warehouse = null, $sku = null)
    {
        if (is_null($sku)) {
            $sku = $this->storage['sku'];
        }
        if (is_null($warehouse)) {
            $warehouses = new WarehouseCollection();
            $warehouses->where(['status' => 1]);
            $result = 0;
            foreach ($warehouses as $warehouse) {
                $inventory = $warehouse->getInventory($this->getId(), $sku);
                if ($inventory) {
                    $result += $inventory['qty'];
                }
            }
            return $result;
        } elseif (is_numeric($warehouse)) {
            $warehouse = (new Warehouse())->setId($warehouse);
        }
        return $warehouse->getInventory($this->getId(), $sku);
    }

    public function getLinkedProducts($type)
    {
        if ($this->getId()) {
            $products = new Collection($this->languageId);
            $products->join('product_link', 'product_link.linked_product_id=id', [], 'right')
                    ->where([
                        'product_link.type' => $type[0],
                        'product_link.product_id' => $this->getId()
                    ])->order('product_link.sort_order');
            return $products;
        }
        return [];
    }

    public function getRelatedProducts()
    {
        return $this->getLinkedProducts('r');
    }

    public function getUpSells()
    {
        return $this->getLinkedProducts('u');
    }

    public function getCrossSells()
    {
        return $this->getLinkedProducts('c');
    }

    public function getUrl($category = null)
    {
        $constraint = ['product_id' => $this->getId()];
        if (is_object($category) || is_array($category)) {
            $constraint['category_id'] = $category['id'];
        }
        if (is_null($category) && isset($this->storage['path'][0])) {
            return $this->getBaseUrl($this->storage['path'][0]);
        } elseif (isset($constraint['category_id']) && isset($this->storage['path'][$constraint['category_id']])) {
            return $this->getBaseUrl($this->storage['path'][$constraint['category_id']]);
        }
        $result = $this->getContainer()->get('indexer')->select('catalog_url', $this->languageId, $constraint);
        if (!count($result)) {
            return '#';
        }
        if (is_null($category)) {
            $this->storage['path'][0] = $result[0]['path'] . '.html';
        } elseif (isset($constraint['category_id'])) {
            $this->storage['path'][$constraint['category_id']] = $result[0]['path'] . '.html';
        } else {
            $this->storage['path'][$result[0]['category_id']] = $result[0]['path'] . '.html';
        }
        return $this->getBaseUrl($result[0]['path'] . '.html');
    }

    public function getImages()
    {
        $images = json_decode($this->storage['images'] ?? '[]', true);
        $result = [];
        foreach ($images as $image) {
            $resource = new Resource();
            $resource->load($image['id']);
            $result[] = $image + ['src' => $resource['real_name']];
        }
        return $result;
    }

    public function getThumbnail($options = null, $resized = '')
    {
        if (!is_null($options)) {
            $images = $this->storage['images'] ?? [];
            if ($images) {
                foreach ($options as $id => $value) {
                    $value = $this->getOption($id, $value);
                    foreach ($images as $image) {
                        if ($image['group'] == $value) {
                            $resource = new Resource();
                            $resource->load($image['id']);
                            return $this->getResourceUrl('image/' . $resource['real_name']);
                        }
                    }
                }
            }
        }
        if (!empty($this->storage['thumbnail'])) {
            $resource = new Resource();
            $resource->load($this->storage['thumbnail']);
            if (empty($resized)) {
                return $this->getResourceUrl('image/' . $resource['real_name']);
            } else {
                return $this->getResourceUrl('image/resized/' . $resized . '/' . $resource['real_name']);
            }
        }
        return $this->getPubUrl('frontend/images/placeholder.png');
    }

    public function getVideo()
    {
        $result = [];
        if (!empty($this->storage['video'])) {
            $resource = new Resource();
            $resource->load($this->storage['video']);
            $result = $resource->toArray() + ['src' => $this->getResourceUrl('video/' . $resource['real_name'])];
        }
        return $result;
    }

    public function getDefaultImage()
    {
        if (!empty($this->storage['default_image'])) {
            $resource = new Resource();
            $resource->load($this->storage['default_image']);
            return $resource['real_name'];
        }
        return $this->getPubUrl('frontend/images/placeholder.png');
    }

    public function getFinalPrice($qty = 1, $convert = true)
    {
        if (empty($this->storage['prices'])) {
            $this->storage['prices'] = [];
            $this->storage['base_prices'] = [];
            $this->getEventDispatcher()->trigger('product.price.calc', [
                'product' => $this, 'qty' => $qty
            ]);
        }
        return $convert ? min($this->storage['prices']) : min($this->storage['base_prices']);
    }

    protected function afterLoad(&$result)
    {
        if (isset($result[0]) && !empty($result[0]['images'])) {
            if (!is_array($result[0]['images'])) {
                $result[0]['images'] = json_decode($result[0]['images'], true);
            }
            $images = $result[0]['images'];
            $result[0]['images'] = [];
            foreach ($images as $item) {
                $resourceName = (new Resource())->load($item['id'])['real_name'];
                $result[0]['images'][] = $item + ['src' => $this->getResourceUrl('image/' . $resourceName), 'real_name' => $resourceName];
            }
        } elseif (!empty($result['images'])) {
            if (!is_array($result['images'])) {
                $result['images'] = json_decode($result['images'], true);
            }
            $images = $result['images'];
            $result['images'] = [];
            foreach ($images as $item) {
                $resourceName = (new Resource())->load($item['id'])['real_name'];
                $result['images'][] = $item + ['src' => $this->getResourceUrl('image/' . $resourceName), 'real_name' => $resourceName];
            }
        }
        parent::afterLoad($result);
    }

    protected function beforeSave()
    {
        if (isset($this->storage['images']) && is_array($this->storage['images']) && count($this->storage['images']) > 0) {
            $images = [];
            foreach ($this->storage['images'] as $order => $id) {
                if (is_array($id)) {
                    break;
                }
                if ($id && !isset($images[$id])) {
                    $images[$id] = [
                        'id' => $id,
                        'label' => $this->storage['images-label'][$order],
                        'group' => $this->storage['images-group'][$order],
                        'name' => $this->storage['images-src'][$order]
                    ];
                }
            }
            $images = array_values($images);
            if (empty($this->storage['default_image']) && !empty($images)) {
                $this->storage['default_image'] = $images[0]['id'];
            }
            if (empty($this->storage['thumbnail']) && !empty($images)) {
                $this->storage['thumbnail'] = $images[0]['id'];
            }
            $this->storage['images'] = json_encode($images);
        }
        if (isset($this->storage['additional']) && is_array($this->storage['additional'])) {
            $this->storage['additional'] = json_encode(array_combine($this->storage['additional']['key'], $this->storage['additional']['value']));
        }
        parent::beforeSave();
    }

    protected function afterSave()
    {
        $changed = isset($this->storage['_changed_fields']) ? explode(',', $this->storage['_changed_fields']) : [];
        if (!empty($this->storage['category']) || in_array('category', $changed)) {
            $tableGateway = $this->getTableGateway('product_in_category');
            $tableGateway->delete(['product_id' => $this->getId()]);
            if (!empty($this->storage['category'])) {
                $maxCount = (int) $this->getContainer()->get('config')['catalog/product/count_in_category'];
                foreach ((array) $this->storage['category'] as $category) {
                    if ($maxCount--) {
                        $tableGateway->insert(['product_id' => $this->getId(), 'category_id' => $category]);
                    }
                    if ($maxCount === 0) {
                        break;
                    }
                }
            }
        }
        if (!empty($this->storage['inventory'])) {
            $warehouse = new Warehouse();
            $tableGateway = $this->getTableGateway('warehouse_inventory');
            foreach ($this->storage['inventory'] as $warehouseId => $inventory) {
                $tableGateway->delete([
                    'warehouse_id' => $warehouseId,
                    'product_id' => $this->getId()
                ]);
                $this->getContainer()->get('log')->logException(new \Exception(json_encode($inventory)));
                foreach ($inventory['qty'] as $order => $qty) {
                    if (empty($inventory['sku'][$order]) || $inventory['sku'][$order] !== $this->storage['sku']) {
                        $warehouse->setInventory([
                            'warehouse_id' => $warehouseId,
                            'product_id' => $this->getId(),
                            'sku' => empty($inventory['sku'][$order]) ? $this->storage['sku'] : $inventory['sku'][$order],
                            'barcode' => $inventory['barcode'][$order - 1] ?? '',
                            'qty' => empty($inventory['sku'][$order]) && count($inventory['qty']) > 1 ? array_sum($inventory['qty']) - $qty : $qty,
                            'reserve_qty' => $inventory['reserve_qty'][$order] ?? ($inventory['reserve_qty'][0] ?? null),
                            'min_qty' => $inventory['min_qty'][$order] ?? ($inventory['min_qty'][0] ?? null),
                            'max_qty' => $inventory['max_qty'][$order] ?? ($inventory['max_qty'][0] ?? null),
                            'is_decimal' => $inventory['is_decimal'][$order] ?? ($inventory['is_decimal'][0] ?? null),
                            'backorders' => $inventory['backorders'][$order] ?? ($inventory['backorders'][0] ?? null),
                            'increment' => $inventory['increment'][$order] ?? ($inventory['increment'][0] ?? null),
                            'status' => $inventory['status'][$order] ?? ($inventory['status'][0] ?? null)
                        ]);
                    }
                }
            }
        }
        if (isset($this->storage['options'])) {
            //$this->getTableGateway('product_option')->delete(['product_id' => $this->getId()]);
            if (is_array($this->storage['options'])) {
                $oldOptions = $this->getOptions();
                $oldOptionIds = [];
                if (count($oldOptions) > 0) {
                    foreach ($oldOptions as $oldOption) {
                        $oldOption->toArray();
                        $oldOptionIds[] = $oldOption['id'];
                    }
                }
                //var_dump($oldOptionIds);                exit('----');
                if (count($oldOptionIds) > 0) {
                    $updateOptions = [];
                    foreach ($this->storage['options']['label'] as $id => $label) {
                        $option = new OptionModel();
                        $OptionId = null;
                        if (in_array($id, $oldOptionIds)) {
                            $OptionId = $id;
                            $updateOptions[] = $id;
                        }
                        $option->setData([
                            'id' => $OptionId,
                            'product_id' => $this->getId(),
                            'label' => $label,
                            'input' => $this->storage['options']['input'][$id],
                            'is_required' => $this->storage['options']['is_required'][$id],
                            'sort_order' => $this->storage['options']['sort_order'][$id],
                            'price' => (float) $this->storage['options']['price'][$id],
                            'is_fixed' => $this->storage['options']['is_fixed'][$id],
                            'sku' => $this->storage['options']['sku'][$id],
                            'value' => $this->storage['options']['value'][$id] ?? null,
                            'eav_attribute_id' => $this->storage['options']['eavattributeid'][$id] ?? null
                        ])->save();
                    }
                    foreach ($oldOptionIds as $optionKey => $optionId) {
                        if (in_array($optionId, $updateOptions)) {
                            unset($oldOptionIds[$optionKey]);
                        }
                    }
                    if (count($oldOptionIds) > 0) {
                        $this->getTableGateway('product_option')->delete(['product_id' => $this->getId(), new In('id', $oldOptionIds)]);
                    }
                } else {
                    $this->getTableGateway('product_option')->delete(['product_id' => $this->getId()]);
                    foreach ($this->storage['options']['label'] as $id => $label) {
                        $option = new OptionModel();
                        $option->setData([
                            'id' => null,
                            'product_id' => $this->getId(),
                            'label' => $label,
                            'input' => $this->storage['options']['input'][$id],
                            'is_required' => $this->storage['options']['is_required'][$id],
                            'sort_order' => $this->storage['options']['sort_order'][$id],
                            'price' => (float) $this->storage['options']['price'][$id],
                            'is_fixed' => $this->storage['options']['is_fixed'][$id],
                            'sku' => $this->storage['options']['sku'][$id],
                            'value' => $this->storage['options']['value'][$id] ?? null
                        ])->save();
                    }
                }
            }
            $this->flushList('product_option');
        }
        parent::afterSave();
    }

    public function serialize()
    {
        unset($this->storage['prices']);
        return parent::serialize();
    }

    public function getCurrency()
    {
        if (isset($this->storage['currency'])) {
            return (new Currency())->load($this->storage['currency'], 'code');
        }
        return $this->getContainer()->get('currency');
    }

    public function canSold()
    {
        return !empty($this->storage['status']) && $this->getStore()->offsetGet('status');
    }

    public function getOptionsAndValues($constraint = [], $language = null)
    {
        if ($this->getId()) {
            $optionsVolues = [];
            $options = new OptionCollection();
            $options->withLabel($language)
                    ->where(['product_id' => $this->getId()] + $constraint)
                    ->order('sort_order ASC');
            //echo $options->getSqlString(Bootstrap::getContainer()->get("dbAdapter")->getPlatform()); exit('-----');
            foreach ($options as $option) {
                $input = $option->offsetGet('input');
                $is_required = $option->offsetGet('is_required');
                $tmpOptions = ['title' => $option->offsetGet('title'), 'id' => $option->getId(), 'product_id' => $option->getId(), 'input' => $input, 'is_required' => $is_required, 'sku' => $option->offsetGet('sku'), 'price' => $option->offsetGet('price'), 'is_fixed' => $option->offsetGet('is_fixed'), 'sort_order' => $option->offsetGet('sort_order')];
                switch ($input) {
                    case 'textarea':
                        $tmpOptions['value'] = '';
                        break;
                    case 'select':
                    case 'multiselect':
                        $multiselectValue = [];
                        foreach ($option->offsetGet('value') as $value) {
                            $multiselectValue[] = ['id' => $value['id'], 'price' => $value['price'], 'sku' => $value['sku'], 'title' => $value['title']];
                        }
                        $tmpOptions['value'] = $multiselectValue;
                        break;
                    case 'bool':
                        $boolValue = [];
                        $boolValue[] = ['id' => $value['id'], 'price' => $value['price'], 'sku' => $value['sku'], 'title' => $value['title']];
                        $boolValue[] = ['id' => $value['id'], 'price' => $value['price'], 'sku' => $value['sku'], 'title' => $value['title']];
                        $tmpOptions['value'] = $boolValue;
                        break;
                    case 'radio':
                    case 'checkbox':
                        $radioValue = [];
                        foreach ($option->offsetGet('value') as $value) {
                            $radioValue[] = ['id' => $value['id'], 'price' => $value['price'], 'sku' => $value['sku'], 'title' => $value['title']];
                        }
                        $tmpOptions['value'] = $radioValue;
                        break;
                    default:
                        $tmpOptions['value'] = '';
                }
                $optionsVolues[] = $tmpOptions;
            }
            return $optionsVolues;
        }
        return $optionsVolues;
    }

    public function getOptionsAndValueHtml($constraint = [], $language = null)
    {
        if ($this->getId()) {
            $optionsVolues = [];
            $options = new OptionCollection();
            $options->withLabel($language)
                    ->where(['product_id' => $this->getId()] + $constraint)
                    ->order('sort_order ASC');

            $optionHtml = '';
            foreach ($options as $option) {
                $optionHtml .= '<div class="input-box ' . $option->offsetGet('input') . '">';
                $optionHtml .= '<label for="option-' . $option->getId() . '" class="col-form-label">' . $option->offsetGet('title') . '</label>';
                $optionHtml .= '<div class="cell">';
                switch ($option->offsetGet('input')) {
                    case 'textarea':
                        $optionHtml .= '<textarea name="options[' . $option->getId() . ']" class="form-control';
                        if ($option->offsetGet('is_required')) {
                            $optionHtml .= ' required';
                        }
                        $optionHtml .= '"';
                        if ((float) $option->offsetGet('price')) {
                            $optionHtml .= ' data-price="' . $option->offsetGet('price') . '"';
                        }
                        $optionHtml .= ' data-sku="' . $option->offsetGet('sku') . '"';
                        $optionHtml .= ' data-msg-required="' . $this->translate('Please choose ') . $option->offsetGet('title') . '">';
                        if (isset($values[$option->getId()])) {
                            $optionHtml .= $values[$option->getId()];
                        }
                        $optionHtml .= '</textarea>';
                        break;
                    case 'select':
                    case 'multiselect':
                        $optionHtml .= '<select name="options[' . $option->getId() . ']" class="form-control';
                        if ($option->offsetGet('is_required')) {
                            $optionHtml .= ' required';
                        }
                        $optionHtml .= '" ';
                        if ($option->offsetGet('input') === 'multiselect') {
                            $optionHtml .= ' multiple="multiple"';
                        }
                        $optionHtml .= 'data-msg-required="' . $this->translate('Please choose ') . $option->offsetGet('title') . '">';

                        if (!$option->offsetGet('is_required') && $option->offsetGet('input') !== 'multiselect') {
                            $optionHtml .= '<option value=""></option>';
                        }
                        foreach ($option->offsetGet('value') as $value) {
                            $optionHtml .= '<option value="' . $value['id'] . '"';
                            if (isset($values[$option->getId()]) && $values[$option->getId()] == $value['id']) {
                                $optionHtml .= 'selected="selected"';
                            }
                            $optionHtml .= ' data-price="' . $value['price'] . '" data-sku="' . $value['sku'] . '">' . $value['title'] . '</option>';
                        }
                        $optionHtml .= '</select>';
                        break;
                    case 'bool':
                        $optionHtml .= '<select name="options[' . $option->getId() . '" class="form-control';
                        if ($option->offsetGet('is_required')) {
                            $optionHtml .= ' required';
                        }
                        $optionHtml .= '"';
                        $optionHtml .= 'data-msg-required="' . $this->translate('Please choose ') . $option->offsetGet('title') . '">';
                        if (!$option->offsetGet('is_required')) {
                            $optionHtml .= '<option value=""></option>';
                        }
                        $optionHtml .= '<option value="0"';
                        if (isset($values[$option->getId()]) && $values[$option->getId()] == 0) {
                            $optionHtml .= ' selected="selected"';
                        }
                        if ((float) $option->offsetGet('price')) {
                            $optionHtml .= 'data-price="0"';
                        }
                        $optionHtml .= 'data-sku="' . $option['sku'] . '">' . $this->translate('No') . '</option>';
                        $optionHtml .= '<option value="1"';
                        if (isset($values[$option->getId()]) && $values[$option->getId()] == 1) {
                            $optionHtml .= ' selected="selected"';
                        }
                        if ((float) $option->offsetGet('price')) {
                            $optionHtml .= 'data-price="' . $value['price'] . '"';
                        }
                        $optionHtml .= 'data-sku="' . $option['sku'] . '">' . $this->translate('Yes') . '</option>';
                        $optionHtml .= '</select>';
                        break;
                    case 'radio':
                    case 'checkbox':
                        foreach ($option->offsetGet('value') as $value) {
                            $optionHtml .= '<input type="' . $option->offsetGet('input') . '"';
                            if ($option->offsetGet('is_required')) {
                                $optionHtml .= ' class="required"';
                            }
                            if (isset($values[$option->getId()]) && $values[$option->getId()] == $value['id']) {
                                $optionHtml .= ' checked="checked"';
                            }
                            $optionHtml .= ' name="options[' . $option->getId() . ']" id="options-' . $option->getId() . '-' . $value['id'] . '" value="' . $value['id'] . '"';
                            $optionHtml .= ' data-price="' . $value['price'] . '"';
                            $optionHtml .= 'data-sku="' . $value['sku'] . '" data-msg-required="' . $this->translate('Please choose ') . $option->offsetGet('title') . '" />';
                            $optionHtml .= '<label for="options-' . $option->getId() . '-' . $value['id'] . '" title="' . $value['title'] . '">' . $value['title'] . '</label>';
                        }
                        break;
                    default:
                        $optionHtml .= '<input type="' . $option->offsetGet('input') . '" name="options[' . $option->getId() . ']" class="form-control';
                        if ($option->offsetGet('is_required')) {
                            $optionHtml .= ' required';
                        }
                        $optionHtml .= '"';
                        if ((float) $option->offsetGet('price')) {
                            $optionHtml .= ' data-price="' . $option->offsetGet('price') . '"';
                        }
                        $optionHtml .= ' data-sku="' . $option->offsetGet('sku') . '" value="';
                        if (isset($values[$option->getId()])) {
                            $optionHtml .= $values[$option->getId()];
                        }
                        $optionHtml .= '"';
                        $optionHtml .= ' data-msg-required="' . $this->translate('Please choose ') . $option->offsetGet('title') . '" />';
                }
                $optionHtml .= '</div>';
                $optionHtml .= '</div>';
            }
            return $optionHtml;
        }
    }
}
