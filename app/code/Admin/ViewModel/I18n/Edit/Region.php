<?php

namespace Redseanet\Admin\ViewModel\I18n\Edit;

use Redseanet\Admin\ViewModel\Edit as PEdit;
use Redseanet\Lib\Source\Language;
use Redseanet\I18n\Source\Country;

class Region extends PEdit
{
    public function getSaveUrl()
    {
        return $this->getAdminUrl('i18n_region/save/');
    }

    public function getTitle()
    {
        return 'Edit Region';
    }

    protected function prepareElements($columns = [])
    {
        $languages = (new Language())->getSourceArray();
        $model = $this->getVariable('model');
        $columns = [
            'id' => [
                'type' => 'hidden',
            ],
            'csrf' => [
                'type' => 'csrf',
            ],
            'parent_id' => [
                'type' => 'jquerynoajaxselect2',
                'label' => 'Country',
                'options' => (new Country())->getSourceArrayId()
            ],
            'code' => [
                'type' => 'text',
                'label' => 'Code',
            ],
            'default_name' => [
                'type' => 'text',
                'label' => 'Default Name',
            ],
            'language_id[]' => [
                'type' => 'select',
                'label' => 'Language',
                'required' => 'required',
                'options' => $languages,
                'attrs' => [
                    'multiple' => 'multiple',
                ],
            ],
            'name' => [
                'type' => 'multitext',
                'label' => 'Name',
                'required' => 'required',
                'base' => '#language_id',
                'options' => $languages,
            ],
        ];

        return parent::prepareElements($columns);
    }
}
