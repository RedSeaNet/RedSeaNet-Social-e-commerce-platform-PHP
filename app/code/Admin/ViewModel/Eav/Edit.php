<?php

namespace Redseanet\Admin\ViewModel\Eav;

use Redseanet\Admin\ViewModel\Edit as PEdit;
use Redseanet\Lib\Bootstrap;
use Redseanet\Lib\Model\Eav\Attribute as AttributeModel;
use Redseanet\Lib\Model\Collection\Eav\Attribute;
use Redseanet\Lib\Session\Segment;
use Redseanet\Lib\Source\Eav\Attribute\Set;
use Redseanet\Lib\Source\Store;
use Redseanet\Admin\Model\User;

abstract class Edit extends PEdit
{
    protected $group = false;
    protected $tabs = null;

    public function setGroup($group)
    {
        $this->group = $group;
        return $this;
    }

    public function getTabs()
    {
        if (is_null($this->tabs)) {
            $this->tabs = $this->getChild('tabs');
        }
        return $this->tabs;
    }

    protected function prepareElements($columns = [])
    {
        $attributes = new Attribute();
        $languageId = Bootstrap::getLanguage()->getId();
        $model = $this->getVariable('model');
        $attributes->withGroup()
                ->withSet()
                ->withLabel($languageId)
                ->join('eav_entity_type', 'eav_entity_type.id=eav_attribute.type_id', [], 'right')
                ->order('sort_order, eav_attribute.id')
                ->where([
                    'eav_entity_type.code' => $model::ENTITY_TYPE,
                    'attribute_set_id' => $this->getQuery('attribute_set', $model['attribute_set_id'])
                ])->where->notEqualTo('eav_attribute.input', 'password');
        if ($this->group) {
            $columns = [];
            $attributes->where(['eav_attribute_group.id' => $this->group]);
        } elseif (empty($columns)) {
            $userArray = (new Segment('admin'))->get('user');
            $user = new User();
            $user->load($userArray['id']);
            $columns = [
                'id' => [
                    'type' => 'hidden'
                ],
                'csrf' => [
                    'type' => 'csrf'
                ],
                'increment_id' => ($this->getQuery('id') ? [
                    'type' => 'label',
                    'label' => 'Human-Friendly ID'
                ] : [
                    'type' => 'hidden'
                ]),
                'attribute_set_id' => [
                    'type' => 'select',
                    'label' => 'Attribute Set',
                    'required' => 'required',
                    'options' => (new Set())->getSourceArray(),
                    'value' => $this->getQuery('attribute_set', $model['attribute_set_id']),
                    'attr' => [
                        'onchange' => 'location.href=\'' . $this->getUri()->withQuery(http_build_query($query = array_diff_key($this->getQuery(), ['attribute_set' => '']))) . (empty($query) ? '?' : '&') . 'attribute_set=\'+this.value;'
                    ]
                ],
                'store_id' => ($user->getStore() ? [
                    'type' => 'hidden',
                    'value' => $user->getStore()->getId()
                ] : [
                    'type' => 'select',
                    'options' => (new Store())->getSourceArray(),
                    'label' => 'Store',
                    'required' => 'required'
                ]),
                'status' => [
                    'type' => 'select',
                    'label' => 'Status',
                    'options' => [
                        1 => 'Enabled',
                        0 => 'Disabled'
                    ],
                    'required' => 'required'
                ]
            ];
        }
        $groups = [];
        foreach ($attributes as $attribute) {
            if (!$this->group && !in_array($attribute['attribute_group_id'], $groups)) {
                if (!$attribute['attribute_group_is_hidden']) {
                    $this->getTabs()->addTab('attribute_group_' . $attribute['attribute_group_id'], $attribute['attribute_group'], $attribute['attribute_group_id']);
                    $this->getTabs()->addChild('attribute_group_' . $attribute['attribute_group_id'], (new static())->setVariable('model', $model)->hasTitle(false)->setGroup($attribute['attribute_group_id']));
                }
                $groups[] = $attribute['attribute_group_id'];
            }
            if ($this->group && $attribute['attribute_group_id'] == $this->group) {
                $validation = $attribute['validation'] ? explode(' ', $attribute['validation']) : [];
                $attrs = [];
                $class = [];
                foreach ($validation as $v) {
                    if (strpos($v, ':')) {
                        list($key, $value) = explode(':', $v);
                        $attrs[$key] = $value;
                    } else {
                        $class[] = $v;
                    }
                }
                $columns[$attribute['code']] = [
                    'label' => $attribute['label'],
                    'type' => $attribute['input'],
                    'view_model' => $attribute['view_model'],
                    'class' => implode(' ', $class),
                    'attrs' => $attrs,
                    'default' => $attribute['default_value']
                ];
                if (in_array($attribute['input'], ['select', 'radio', 'checkbox', 'multiselect'])) {
                    $columns[$attribute['code']]['options'] = (new AttributeModel($attribute))->getOptions($languageId);
                }
                if ($attribute['is_required']) {
                    $columns[$attribute['code']]['required'] = 'required';
                }
                if ($attribute['type'] === 'varchar') {
                    $columns[$attribute['code']]['attrs'] = ['maxlength' => 127];
                }
            }
        }
        return parent::prepareElements($columns);
    }
}
